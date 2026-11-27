<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EmailAccountMapping;
use App\Models\EmailConnection;
use App\Models\EmailTransactionImport;
use App\Services\Email\GmailSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailIntegrationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $connections = EmailConnection::where('user_id', $request->user()->id)->with('mappings.account')->orderBy('id')->get();
        $pending = EmailTransactionImport::where('user_id', $request->user()->id)->where('status', 'needs_review')->count();

        return response()->json(['data' => [
            'provider' => 'gmail',
            'configured' => (bool) (config('services.gmail.enabled') && config('services.gmail.client_id') && config('services.gmail.client_secret')),
            'push_enabled' => (bool) (config('services.gmail.enabled') && config('services.gmail.pubsub_topic')),
            'pending_count' => $pending,
            'connections' => $connections->map(fn (EmailConnection $connection) => [
                'id' => $connection->id,
                'email' => $connection->email,
                'status' => $connection->status,
                'last_sync_at' => $connection->last_sync_at,
                'watch_expires_at' => $connection->watch_expires_at,
                'last_error' => $connection->last_error,
                'mappings' => $connection->mappings->map(fn (EmailAccountMapping $mapping) => [
                    'id' => $mapping->id,
                    'bank_code' => $mapping->bank_code,
                    'account_id' => $mapping->account_id,
                    'account_name' => $mapping->account?->name,
                    'account_provider' => $mapping->account?->provider,
                ])->values(),
            ])->values(),
        ]]);
    }

    public function imports(Request $request): JsonResponse
    {
        $imports = EmailTransactionImport::where('user_id', $request->user()->id)
            ->where('status', 'needs_review')->with('connection:id,email')->orderByDesc('created_at')->limit(100)->get();

        return response()->json(['data' => $imports->map(fn (EmailTransactionImport $import) => [
            'id' => $import->id,
            'connection_id' => $import->email_connection_id,
            'email' => $import->connection?->email,
            'bank_code' => $import->bank_code,
            'bank_name' => $import->bank_name,
            'amount' => $import->amount,
            'type' => $import->type,
            'date' => $import->transaction_date?->format('Y-m-d'),
            'note' => $import->note,
            'reason' => $import->review_reason,
        ])->values()]);
    }

    public function saveMapping(Request $request, EmailConnection $connection): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'bank_code' => ['required', Rule::in(['bca', 'blu', 'mandiri', 'seabank'])],
            'account_id' => ['required', 'integer'],
        ]);
        $account = Account::where('user_id', $request->user()->id)->where('is_archived', false)->whereKey($data['account_id'])->firstOrFail();
        if ($account->provider && $account->provider !== 'other' && $account->provider !== $data['bank_code']) {
            abort(422, 'Penyedia wallet tidak sesuai dengan bank yang dipetakan.');
        }

        $mapping = EmailAccountMapping::updateOrCreate([
            'email_connection_id' => $connection->id,
            'bank_code' => $data['bank_code'],
        ], [
            'account_id' => $account->id,
        ]);

        return response()->json(['message' => 'Pemetaan akun email tersimpan.', 'data' => ['id' => $mapping->id]]);
    }

    public function confirm(Request $request, EmailTransactionImport $import, GmailSyncService $sync): JsonResponse
    {
        abort_unless($import->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'account_id' => ['required', 'integer'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        Account::where('user_id', $request->user()->id)->whereKey($data['account_id'])->firstOrFail();

        $transaction = $sync->postImport(
            $import->connection,
            $import,
            (int) $data['account_id'],
            $data['type'],
            (string) $data['amount'],
            $data['date'],
            $data['note'] ?? $import->note ?? 'Transaksi dari Gmail',
        );

        return response()->json(['message' => 'Transaksi berhasil dicatat.', 'data' => ['transaction_id' => $transaction->id]]);
    }

    public function ignore(Request $request, EmailTransactionImport $import): JsonResponse
    {
        abort_unless($import->user_id === $request->user()->id, 404);
        abort_unless($import->status === 'needs_review', 409);
        $import->forceFill(['status' => 'ignored'])->save();

        return response()->json(['message' => 'Email diabaikan.']);
    }

    public function disconnect(Request $request, EmailConnection $connection, GmailSyncService $sync): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()->id, 404);
        $sync->stopWatch($connection);
        $revoked = $sync->revoke($connection);
        $connection->delete();

        return response()->json(['message' => $revoked
            ? 'Koneksi email diputus dan izin Google dicabut. Transaksi yang sudah dicatat tetap tersimpan.'
            : 'Koneksi email dan token lokal dihapus. Jika perlu, cabut akses Ledger juga dari pengaturan keamanan Google. Transaksi yang sudah dicatat tetap tersimpan.']);
    }
}
