<?php

namespace App\Services\Email;

use App\Models\Account;
use App\Models\EmailAccountMapping;
use App\Models\EmailConnection;
use App\Models\EmailTransactionImport;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GmailSyncService
{
    public function __construct(
        private readonly GmailApiClient $gmail,
        private readonly BankNotificationParser $parser,
        private readonly TransactionService $transactions,
    ) {}

    public function initialize(EmailConnection $connection): void
    {
        $profile = $this->gmail->request($connection, 'GET', 'profile');
        $connection->forceFill(['history_id' => $profile['historyId'] ?? null])->save();
        try {
            $this->renewWatch($connection->fresh());
        } catch (Throwable $exception) {
            $connection->forceFill(['status' => 'error', 'last_error' => 'Push Gmail belum aktif; sinkronisasi berkala tetap berjalan.'])->save();
            Log::warning('Gmail watch setup failed.', ['connection_id' => $connection->id, 'exception' => class_basename($exception)]);
        }
    }

    public function sync(EmailConnection $connection): void
    {
        if (! config('services.gmail.enabled')) {
            return;
        }

        $lock = Cache::lock('gmail-sync:'.$connection->id, 120);
        if (! $lock->get()) {
            return;
        }

        try {
            $this->syncLocked($connection->fresh() ?? $connection);
        } finally {
            $lock->release();
        }
    }

    private function syncLocked(EmailConnection $connection): void
    {
        try {
            if (config('services.gmail.pubsub_topic') && (! $connection->watch_expires_at || $connection->watch_expires_at->lt(now()->addDay()))) {
                try {
                    $this->renewWatch($connection);
                } catch (Throwable $exception) {
                    $connection->forceFill(['last_error' => 'Push Gmail belum aktif; sinkronisasi berkala tetap berjalan.'])->save();
                    Log::warning('Gmail watch renewal failed.', ['connection_id' => $connection->id, 'exception' => class_basename($exception)]);
                }
            }

            $historyId = $connection->history_id;
            if (! $historyId) {
                $profile = $this->gmail->request($connection, 'GET', 'profile');
                $connection->forceFill(['history_id' => $profile['historyId'] ?? null])->save();
                $historyId = $profile['historyId'] ?? null;
            }

            if (! $historyId) {
                $connection->forceFill(['last_sync_at' => now()])->save();

                return;
            }

            $ids = [];
            $pageToken = null;
            try {
                do {
                    $query = ['startHistoryId' => $historyId, 'historyTypes' => 'messageAdded'];
                    if ($pageToken) {
                        $query['pageToken'] = $pageToken;
                    }
                    $history = $this->gmail->request($connection, 'GET', 'history', $query);
                    foreach ($history['history'] ?? [] as $item) {
                        foreach ($item['messagesAdded'] ?? [] as $addition) {
                            $messageId = $addition['message']['id'] ?? null;
                            if ($messageId) {
                                $ids[$messageId] = true;
                            }
                        }
                    }
                    $pageToken = $history['nextPageToken'] ?? null;
                    $newHistoryId = $history['historyId'] ?? null;
                } while ($pageToken);
            } catch (RuntimeException $exception) {
                if (! str_contains($exception->getMessage(), 'HTTP 404')) {
                    throw $exception;
                }
                $ids = $this->recentMessageIds($connection);
                $profile = $this->gmail->request($connection, 'GET', 'profile');
                $newHistoryId = $profile['historyId'] ?? $historyId;
            }

            foreach (array_keys($ids) as $messageId) {
                $this->processMessage($connection, $messageId);
            }

            $connection->forceFill([
                'history_id' => $newHistoryId ?: $historyId,
                'last_sync_at' => now(),
                'last_error' => null,
                'status' => 'connected',
            ])->save();
        } catch (Throwable $exception) {
            $reauthorize = str_contains(strtolower($exception->getMessage()), 'revoked')
                || str_contains(strtolower($exception->getMessage()), 'expired');
            $connection->forceFill([
                'last_sync_at' => now(),
                'last_error' => $reauthorize
                    ? 'Akses Gmail berakhir atau dicabut. Hubungkan ulang untuk melanjutkan.'
                    : 'Sinkronisasi Gmail gagal sementara. Coba lagi nanti.',
                'status' => $reauthorize ? 'reauthorize' : 'error',
            ])->save();
            Log::warning('Gmail sync failed.', ['connection_id' => $connection->id, 'exception' => class_basename($exception)]);
        }
    }

    public function renewWatch(EmailConnection $connection): void
    {
        $topic = config('services.gmail.pubsub_topic');
        if (! $topic) {
            return;
        }

        $watch = $this->gmail->request($connection, 'POST', 'watch', [], [
            'topicName' => $topic,
            'labelIds' => ['INBOX'],
            'labelFilterBehavior' => 'INCLUDE',
        ]);
        $connection->forceFill([
            'history_id' => $connection->history_id ?: ($watch['historyId'] ?? null),
            'watch_expires_at' => isset($watch['expiration']) ? now()->setTimestamp((int) floor(((int) $watch['expiration']) / 1000)) : null,
            'status' => 'connected',
            'last_error' => null,
        ])->save();
    }

    private function processMessage(EmailConnection $connection, string $messageId): void
    {
        if (EmailTransactionImport::where('email_connection_id', $connection->id)->where('gmail_message_id', $messageId)->exists()) {
            return;
        }

        $metadata = $this->gmail->request($connection, 'GET', 'messages/'.rawurlencode($messageId), [
            'format' => 'metadata',
            'metadataHeaders' => 'From Subject',
        ]);
        $headers = collect($metadata['payload']['headers'] ?? [])->mapWithKeys(fn ($header) => [strtolower($header['name'] ?? '') => (string) ($header['value'] ?? '')]);
        $bankCode = $this->parser->identifyBank((string) $headers->get('from', ''));
        if (! $bankCode) {
            return;
        }

        $message = $this->gmail->request($connection, 'GET', 'messages/'.rawurlencode($messageId), ['format' => 'full']);
        $parsed = $this->parser->parse($message);
        if (! $parsed) {
            return;
        }

        $mapping = EmailAccountMapping::where('email_connection_id', $connection->id)
            ->where('bank_code', $bankCode)->first();

        $reason = match (true) {
            ! $parsed['amount'] => 'Nominal belum terbaca.',
            ! $parsed['type'] => 'Jenis debit atau kredit belum terbaca.',
            ! $mapping => 'Hubungkan bank ini ke akun Cashlog.',
            default => null,
        };

        $import = EmailTransactionImport::create([
            'user_id' => $connection->user_id,
            'email_connection_id' => $connection->id,
            'gmail_message_id' => $messageId,
            'bank_code' => $bankCode,
            'bank_name' => $parsed['bank_name'],
            'amount' => $parsed['amount'],
            'type' => $parsed['type'],
            'transaction_date' => $parsed['transaction_date'],
            'note' => $parsed['note'],
            'status' => 'needs_review',
            'review_reason' => $reason,
        ]);
    }

    public function postImport(EmailConnection $connection, EmailTransactionImport $import, int $accountId, string $type, string $amount, string $date, string $note): Transaction
    {
        abort_unless($import->email_connection_id === $connection->id && $import->user_id === $connection->user_id, 404);

        return DB::transaction(function () use ($connection, $import, $accountId, $type, $amount, $date, $note) {
            $locked = EmailTransactionImport::where('user_id', $connection->user_id)
                ->where('email_connection_id', $connection->id)
                ->lockForUpdate()->findOrFail($import->id);
            if ($locked->transaction_id) {
                return Transaction::findOrFail($locked->transaction_id);
            }
            abort_unless($locked->status === 'needs_review', 409, 'Impor ini sudah diproses.');
            abort_unless(Account::where('user_id', $connection->user_id)->whereKey($accountId)->exists(), 404);

            $transaction = $this->transactions->createTransaction($connection->user, [
                'account_id' => $accountId,
                'type' => $type,
                'amount' => $amount,
                'date' => $date,
                'note' => $note,
                'idempotency_key' => 'gmail-'.$connection->id.'-'.$locked->gmail_message_id,
            ]);

            $locked->forceFill(['status' => 'posted', 'transaction_id' => $transaction->id, 'review_reason' => null])->save();

            return $transaction;
        }, 3);
    }

    public function stopWatch(EmailConnection $connection): void
    {
        try {
            $this->gmail->request($connection, 'POST', 'stop');
        } catch (Throwable $exception) {
            Log::info('Gmail watch stop skipped.', ['connection_id' => $connection->id, 'exception' => class_basename($exception)]);
        }
    }

    public function revoke(EmailConnection $connection): bool
    {
        $token = $connection->refresh_token;
        if (! $token) {
            return true;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', [
                'token' => $token,
            ]);

            return $response->successful();
        } catch (Throwable $exception) {
            Log::info('Gmail authorization revocation request failed.', ['connection_id' => $connection->id, 'exception' => class_basename($exception)]);

            return false;
        }
    }

    private function recentMessageIds(EmailConnection $connection): array
    {
        $ids = [];
        $pageToken = null;
        do {
            $query = ['q' => 'newer_than:14d', 'maxResults' => 100];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            $page = $this->gmail->request($connection, 'GET', 'messages', $query);
            foreach ($page['messages'] ?? [] as $message) {
                if (! empty($message['id'])) {
                    $ids[$message['id']] = true;
                }
                if (count($ids) >= 500) {
                    break;
                }
            }
            $pageToken = count($ids) < 500 ? ($page['nextPageToken'] ?? null) : null;
        } while ($pageToken);

        return array_keys($ids);
    }
}
