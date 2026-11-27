<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGmailConnectionJob;
use App\Models\EmailConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GmailPushWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('services.gmail.enabled')) {
            return response()->json(['message' => 'Gmail integration is disabled.'], 404);
        }

        if (! $this->validPubSubToken($request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $encoded = $request->input('message.data');
        if (! is_string($encoded) || $encoded === '') {
            return response()->json(['message' => 'Invalid notification'], 400);
        }
        $payload = json_decode(base64_decode(strtr($encoded, '-_', '+/')) ?: '', true);
        $email = strtolower(trim((string) ($payload['emailAddress'] ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid mailbox'], 400);
        }

        EmailConnection::where('provider', 'gmail')->where('email', $email)->where('status', '!=', 'reauthorize')
            ->orderBy('id')->get()->each(fn (EmailConnection $connection) => SyncGmailConnectionJob::dispatch($connection->id));

        return response()->json(['ok' => true]);
    }

    private function validPubSubToken(?string $token): bool
    {
        $audience = config('services.gmail.pubsub_audience');
        $serviceAccount = config('services.gmail.pubsub_service_account');
        if (! $token || ! $audience || ! $serviceAccount) {
            return false;
        }

        $response = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $token]);
        if (! $response->successful()) {
            return false;
        }
        $claims = $response->json();

        return hash_equals((string) $audience, (string) ($claims['aud'] ?? ''))
            && hash_equals((string) $serviceAccount, (string) ($claims['email'] ?? ''))
            && in_array((string) ($claims['email_verified'] ?? ''), ['true', '1'], true);
    }
}
