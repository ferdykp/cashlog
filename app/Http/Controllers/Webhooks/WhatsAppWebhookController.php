<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\EvolutionProvider;
use App\Services\WhatsApp\WhatsAppMessageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        WhatsAppMessageProcessor $processor,
        EvolutionProvider $provider
    ): JsonResponse {
        $requestStarted = microtime(true);

        $jwtSecret = (string) config('services.evolution.webhook_jwt_secret');
        $sharedSecret = (string) config('services.evolution.webhook_secret');
        $providerJwt = null;

        if ($jwtSecret !== '') {
            $providerJwt = $this->providerJwtContext($request, $jwtSecret);
            abort_unless($providerJwt !== null, 401);
        } else {
            abort_unless(
                config('services.evolution.webhook_allow_shared_secret'),
                503,
                'WhatsApp webhook memerlukan JWT provider di lingkungan produksi.'
            );
            abort_if($sharedSecret === '', 503, 'WhatsApp webhook belum dikonfigurasi.');
            abort_unless(
                hash_equals($sharedSecret, (string) $request->header('X-Ledger-Webhook-Secret')),
                401
            );
        }

        $payload = $request->all();
        $event = data_get($payload, 'event');
        $normalizedEvent = is_string($event)
            ? strtolower(str_replace('_', '.', $event))
            : null;

        $fromMe = data_get($payload, 'data.key.fromMe')
            ?? data_get($payload, 'data.key.from_me')
            ?? data_get($payload, 'data.fromMe')
            ?? data_get($payload, 'data.from_me')
            ?? data_get($payload, 'key.fromMe')
            ?? data_get($payload, 'key.from_me')
            ?? data_get($payload, 'fromMe')
            ?? data_get($payload, 'from_me');

        $isOutbound = filter_var(
            $fromMe,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) === true;

        $remote = data_get($payload, 'data.key.remoteJid')
            ?? data_get($payload, 'data.sender')
            ?? data_get($payload, 'sender');

        if (
            $isOutbound
            || (
                $normalizedEvent !== null
                && $normalizedEvent !== 'messages.upsert'
            )
        ) {
            return response()->json([
                'ok' => true,
                'ignored' => true,
            ]);
        }

        if (
            ! is_string($remote)
            || ! preg_match(
                '/^\+?(\d{9,15})(?:@s\.whatsapp\.net)?$/',
                $remote,
                $match
            )
        ) {
            return response()->json([
                'ok' => true,
                'ignored' => true,
            ]);
        }

        $text = data_get($payload, 'data.message.conversation')
            ?? data_get($payload, 'data.message.extendedTextMessage.text')
            ?? data_get($payload, 'data.text');

        if (! is_string($text) || trim($text) === '') {
            return response()->json([
                'ok' => true,
                'ignored' => true,
            ]);
        }

        $id = data_get($payload, 'data.key.id')
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'id');

        abort_unless(
            is_string($id)
                && $id !== ''
                && strlen($id) <= 255,
            422,
            'ID pesan wajib tersedia.'
        );

        abort_if(
            mb_strlen($text) > 4000,
            422,
            'Pesan terlalu panjang.'
        );

        if ($providerJwt !== null) {
            $ttl = max(1, $providerJwt['expires_at'] - now()->timestamp);
            $replayKey = 'whatsapp:webhook-jwt:'.$providerJwt['fingerprint'];

            if (! Cache::add($replayKey, $id, $ttl)) {
                abort_unless(Cache::get($replayKey) === $id, 401);
            }
        }

        $message = WhatsAppMessage::firstOrCreate(
            ['provider_message_id' => $id],
            [
                'phone_number' => $match[1],
                'message_type' => 'text',
                'body' => $text,
            ]
        );

        $processingStarted = microtime(true);

        DB::transaction(function () use ($message, $processor) {
            $message = WhatsAppMessage::whereKey($message->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($message->processed_at) {
                return;
            }

            $reply = $processor->handle(
                $message->phone_number,
                $message->body
            );

            $message->update([
                'processed_at' => now(),
                'reply_text' => $reply,
            ]);
        }, 3);

        $processingMs = round(
            (microtime(true) - $processingStarted) * 1000
        );

        $sendingStarted = microtime(true);

        $sent = Cache::lock(
            'whatsapp:reply:'.$message->id,
            30
        )->get(function () use ($message, $provider) {
            $message = $message->fresh();

            if ($message->replied_at || ! $message->reply_text) {
                return true;
            }

            if (! $provider->sendText(
                $message->phone_number,
                $message->reply_text
            )) {
                return false;
            }

            $message->update([
                'replied_at' => now(),
            ]);

            return true;
        });

        $sendingMs = round(
            (microtime(true) - $sendingStarted) * 1000
        );

        $totalMs = round(
            (microtime(true) - $requestStarted) * 1000
        );

        Log::info('WhatsApp performance', [
            'processing_ms' => $processingMs,
            'sending_ms' => $sendingMs,
            'total_ms' => $totalMs,
            'sent' => $sent,
        ]);

        return response()->json(
            [
                'ok' => $sent,
                'reply_pending' => ! $sent,
            ],
            $sent ? 200 : 502
        );
    }

    private function providerJwtContext(Request $request, string $secret): ?array
    {
        $authorization = (string) $request->header('Authorization');
        if (! preg_match('/^Bearer\s+([A-Za-z0-9_.-]+)$/i', $authorization, $matches)) {
            return null;
        }

        $parts = explode('.', $matches[1]);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode($this->decodeBase64Url($encodedHeader), true);
        $claims = json_decode($this->decodeBase64Url($encodedPayload), true);

        if (! is_array($header) || ($header['alg'] ?? null) !== 'HS256' || ! is_array($claims)) {
            return null;
        }

        $expectedSignature = rtrim(strtr(base64_encode(hash_hmac(
            'sha256',
            $encodedHeader.'.'.$encodedPayload,
            $secret,
            true
        )), '+/', '-_'), '=');

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            return null;
        }

        $now = now()->timestamp;
        $issuedAt = filter_var($claims['iat'] ?? null, FILTER_VALIDATE_INT);
        $expiresAt = filter_var($claims['exp'] ?? null, FILTER_VALIDATE_INT);

        $valid = $issuedAt !== false
            && $expiresAt !== false
            && $issuedAt <= $now + 30
            && $issuedAt >= $now - 600
            && $expiresAt > $now
            && $expiresAt > $issuedAt
            && $expiresAt - $issuedAt <= 600;

        return $valid ? [
            'expires_at' => $expiresAt,
            'fingerprint' => hash('sha256', $matches[1]),
        ] : null;
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        return $decoded === false ? '' : $decoded;
    }
}
