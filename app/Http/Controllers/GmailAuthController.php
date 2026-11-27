<?php

namespace App\Http\Controllers;

use App\Models\EmailConnection;
use App\Services\Email\GmailSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GmailAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        if (! $user) {
            return redirect('/login?email=login-required');
        }
        if (! config('services.gmail.enabled') || ! config('services.gmail.client_id') || ! config('services.gmail.client_secret')) {
            return redirect('/settings?email=not-configured');
        }

        $state = Str::random(48);
        $request->session()->put('gmail_oauth', ['state' => $state, 'user_id' => $user->id]);
        $query = http_build_query([
            'client_id' => config('services.gmail.client_id'),
            'redirect_uri' => config('services.gmail.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email https://www.googleapis.com/auth/gmail.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request, GmailSyncService $sync): RedirectResponse
    {
        if (! config('services.gmail.enabled')) {
            return redirect('/settings/email?email=not-configured');
        }

        $oauth = $request->session()->pull('gmail_oauth');
        if (! is_array($oauth) || ! hash_equals((string) ($oauth['state'] ?? ''), (string) $request->query('state', ''))) {
            return redirect('/settings?email=invalid-state');
        }

        $user = Auth::guard('web')->user();
        if (! $user || (int) $user->id !== (int) ($oauth['user_id'] ?? 0)) {
            return redirect('/login?email=login-required');
        }
        if ($request->query('error') || ! $request->filled('code')) {
            return redirect('/settings?email=cancelled');
        }

        try {
            $tokens = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.gmail.client_id'),
                'client_secret' => config('services.gmail.client_secret'),
                'redirect_uri' => config('services.gmail.redirect_uri'),
                'grant_type' => 'authorization_code',
            ])->throw()->json();

            $identity = Http::withToken($tokens['access_token'] ?? '')->timeout(15)
                ->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
            abort_unless(($identity['email_verified'] ?? false) && filter_var($identity['email'] ?? null, FILTER_VALIDATE_EMAIL), 401);

            $connection = EmailConnection::firstOrNew([
                'user_id' => $user->id,
                'provider' => 'gmail',
                'email' => strtolower(trim($identity['email'])),
            ]);
            $refreshToken = $tokens['refresh_token'] ?? $connection->refresh_token;
            if (! $refreshToken) {
                return redirect('/settings?email=refresh-token-required');
            }

            $connection->fill([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'status' => 'connected',
                'last_error' => null,
            ])->save();

            $sync->initialize($connection);

            return redirect('/settings/email?email=connected');
        } catch (Throwable $exception) {
            Log::warning('Gmail OAuth callback failed.', ['exception' => class_basename($exception)]);

            return redirect('/settings?email=failed');
        }
    }
}
