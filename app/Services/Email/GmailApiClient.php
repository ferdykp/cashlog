<?php

namespace App\Services\Email;

use App\Models\EmailConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GmailApiClient
{
    public function request(EmailConnection $connection, string $method, string $path, array $query = [], ?array $body = null): array
    {
        $response = Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->timeout(20)
            ->send($method, 'https://gmail.googleapis.com/gmail/v1/users/me/'.ltrim($path, '/'), [
                'query' => $query,
                'json' => $body,
            ]);

        if ($response->status() === 401) {
            $connection->forceFill(['access_token' => null, 'access_token_expires_at' => null])->save();
            $response = Http::withToken($this->accessToken($connection->fresh()))
                ->acceptJson()->timeout(20)
                ->send($method, 'https://gmail.googleapis.com/gmail/v1/users/me/'.ltrim($path, '/'), [
                    'query' => $query,
                    'json' => $body,
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Gmail API returned HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }

    public function accessToken(EmailConnection $connection): string
    {
        if ($connection->access_token && $connection->access_token_expires_at?->gt(now()->addMinute())) {
            return $connection->access_token;
        }

        if (! $connection->refresh_token) {
            throw new RuntimeException('Gmail refresh token is missing. Reconnect the mailbox.');
        }

        $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.gmail.client_id'),
            'client_secret' => config('services.gmail.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new RuntimeException('Gmail authorization expired or was revoked. Reconnect the mailbox.');
        }

        $connection->forceFill([
            'access_token' => $response->json('access_token'),
            'access_token_expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
            'status' => 'connected',
            'last_error' => null,
        ])->save();

        return $connection->access_token;
    }
}
