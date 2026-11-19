<?php

namespace App\Http\Controllers;

use App\Models\GoogleOtpChallenge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $intent = $request->query('intent', 'login');
        abort_unless(in_array($intent, ['login', 'link'], true), 400);

        if ($intent === 'link' && ! $request->user('sanctum')) {
            return redirect('/login?google=login-required');
        }

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect(($intent === 'link' ? '/settings' : '/login').'?google=not-configured');
        }

        $state = Str::random(48);
        $request->session()->put('google_oauth', [
            'state' => $state,
            'intent' => $intent,
            'user_id' => $intent === 'link' ? $request->user('sanctum')->id : null,
        ]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $oauth = $request->session()->pull('google_oauth');
        if (! is_array($oauth) || ! hash_equals((string) ($oauth['state'] ?? ''), (string) $request->query('state', ''))) {
            return redirect('/login?google=invalid-state');
        }

        if ($request->query('error') || ! $request->filled('code')) {
            return redirect(($oauth['intent'] === 'link' ? '/settings' : '/login').'?google=cancelled');
        }

        try {
            $tokenResponse = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->query('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect_uri'),
                'grant_type' => 'authorization_code',
            ])->throw()->json();

            $identity = $this->verifyGoogleIdentity($tokenResponse['id_token'] ?? '');
        } catch (Throwable $exception) {
            Log::warning('Google OAuth authentication failed.', ['exception' => class_basename($exception)]);

            return redirect(($oauth['intent'] === 'link' ? '/settings' : '/login').'?google=failed');
        }

        $email = $this->normalizeEmail($identity['email']);

        if ($oauth['intent'] === 'link') {
            $user = $request->user('sanctum');
            if (! $user || (int) $user->id !== (int) ($oauth['user_id'] ?? 0)) {
                return redirect('/login?google=login-required');
            }

            if ($email !== $this->normalizeEmail($user->email)) {
                return redirect('/settings?google=email-mismatch');
            }

            $linkedUser = User::where('google_id', $identity['sub'])->first();
            if ($linkedUser && $linkedUser->id !== $user->id) {
                return redirect('/settings?google=already-linked');
            }

            if ($user->google_id && $user->google_id !== $identity['sub']) {
                return redirect('/settings?google=already-linked');
            }

            if ($user->google_id === $identity['sub']) {
                return redirect('/settings?google=already-linked');
            }

            return $this->beginOtp($request, $identity, $email, 'link', $user);
        }

        $linkedUser = User::where('google_id', $identity['sub'])->first();
        if ($linkedUser) {
            if ($this->normalizeEmail($linkedUser->email) !== $email) {
                return redirect('/login?google=email-mismatch');
            }

            Auth::guard('web')->login($linkedUser);
            $request->session()->regenerate();

            return redirect('/dashboard');
        }

        $existingUser = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingUser) {
            return redirect('/login?google=manual-account');
        }

        return $this->beginOtp($request, $identity, $email, 'register');
    }

    public function pending(Request $request): JsonResponse
    {
        $challenge = $this->sessionChallenge($request);
        if (! $challenge) {
            return response()->json(['message' => 'Sesi verifikasi tidak ditemukan atau sudah kedaluwarsa.'], 410);
        }

        return response()->json([
            'email' => $this->maskEmail($challenge->email),
            'purpose' => $challenge->purpose,
            'expires_at' => $challenge->expires_at,
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $challenge = $this->sessionChallenge($request);
        if (! $challenge) {
            return response()->json(['message' => 'Sesi verifikasi tidak ditemukan atau sudah kedaluwarsa.'], 410);
        }

        if ($challenge->last_sent_at->gt(now()->subSeconds(60))) {
            return response()->json(['message' => 'Tunggu sebentar sebelum meminta kode baru.'], 429);
        }

        $code = (string) random_int(100000, 999999);
        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ])->save();

        $this->sendCode($challenge->email, $code);

        return response()->json(['message' => 'Kode verifikasi baru sudah dikirim.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $challenge = $this->sessionChallenge($request);
        if (! $challenge) {
            return response()->json(['message' => 'Sesi verifikasi tidak ditemukan atau sudah kedaluwarsa.'], 410);
        }

        return DB::transaction(function () use ($request, $challenge): JsonResponse {
            $challenge = GoogleOtpChallenge::query()->lockForUpdate()->find($challenge->id);
            if (! $challenge || $challenge->consumed_at || $challenge->expires_at->isPast()) {
                return response()->json(['message' => 'Kode verifikasi sudah tidak berlaku.'], 410);
            }

            if ($challenge->attempts >= 5) {
                return response()->json(['message' => 'Terlalu banyak percobaan. Minta kode baru.'], 429);
            }

            if (! Hash::check($request->string('code')->toString(), $challenge->code_hash)) {
                $challenge->increment('attempts');

                return response()->json([
                    'message' => 'Kode verifikasi salah atau sudah kedaluwarsa.',
                    'errors' => ['code' => ['Kode verifikasi salah atau sudah kedaluwarsa.']],
                ], 422);
            }

            if ($challenge->purpose === 'link') {
                $user = $request->user('sanctum');
                if (! $user || $user->id !== $challenge->user_id || $this->normalizeEmail($user->email) !== $challenge->email) {
                    return response()->json(['message' => 'Akun aktif tidak cocok dengan permintaan pengaitan.'], 403);
                }

                $linkedUser = User::where('google_id', $challenge->google_id)->first();
                if ($linkedUser && $linkedUser->id !== $user->id) {
                    return response()->json(['message' => 'Akun Google sudah terhubung ke akun lain.'], 409);
                }

                $user->forceFill([
                    'google_id' => $challenge->google_id,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                if (User::whereRaw('LOWER(email) = ?', [$challenge->email])->exists()) {
                    return response()->json(['message' => 'Email sudah memiliki akun. Silakan masuk dan hubungkan Google dari Pengaturan.'], 409);
                }

                $user = User::create([
                    'name' => $challenge->name,
                    'email' => $challenge->email,
                    'password' => Str::random(64),
                    'avatar' => $challenge->avatar,
                    'google_id' => $challenge->google_id,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $challenge->forceFill(['consumed_at' => now()])->save();
            $request->session()->forget('google_otp_challenge');
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json(['message' => 'Verifikasi berhasil.', 'data' => ['user' => $user->fresh()]]);
        });
    }

    private function beginOtp(Request $request, array $identity, string $email, string $purpose, ?User $user = null): RedirectResponse
    {
        GoogleOtpChallenge::where('expires_at', '<', now()->subDay())->delete();

        $code = (string) random_int(100000, 999999);
        $challenge = GoogleOtpChallenge::create([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'google_id' => $identity['sub'],
            'name' => $identity['name'] ?? Str::before($email, '@'),
            'avatar' => $identity['picture'] ?? null,
            'user_id' => $user?->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        try {
            $this->sendCode($email, $code);
        } catch (Throwable $exception) {
            Log::warning('Google OTP email delivery failed.', ['exception' => class_basename($exception)]);
            $challenge->delete();

            return redirect(($purpose === 'link' ? '/settings' : '/login').'?google=email-failed');
        }

        $request->session()->put('google_otp_challenge', $challenge->id);

        return redirect('/verify-google');
    }

    private function verifyGoogleIdentity(string $idToken): array
    {
        abort_if($idToken === '', 401);

        $claims = Http::timeout(8)
            ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken])
            ->throw()
            ->json();

        $validIssuer = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $verifiedEmail = in_array($claims['email_verified'] ?? false, [true, 'true'], true);

        abort_unless(
            ($claims['aud'] ?? null) === config('services.google.client_id')
                && $validIssuer
                && $verifiedEmail
                && filled($claims['sub'] ?? null)
                && filled($claims['email'] ?? null),
            401,
            'Google identity token is invalid.'
        );

        return [
            'sub' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'] ?? null,
            'picture' => $claims['picture'] ?? null,
        ];
    }

    private function sessionChallenge(Request $request): ?GoogleOtpChallenge
    {
        $id = $request->session()->get('google_otp_challenge');
        if (! $id) {
            return null;
        }

        $challenge = GoogleOtpChallenge::find($id);
        if (! $challenge || $challenge->consumed_at || $challenge->expires_at->isPast()) {
            $request->session()->forget('google_otp_challenge');

            return null;
        }

        return $challenge;
    }

    private function sendCode(string $email, string $code): void
    {
        Mail::raw("Kode verifikasi Google Cashlog Anda: {$code}\n\nKode berlaku selama 10 menit dan hanya dapat digunakan satu kali. Jika Anda tidak meminta kode ini, abaikan email ini.", function ($message) use ($email): void {
            $message->to($email)->subject('Kode verifikasi akun Cashlog');
        });
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, 1);

        return $visible.str_repeat('*', max(1, mb_strlen($name) - 1)).'@'.$domain;
    }
}
