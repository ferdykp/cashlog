<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        // Always return a neutral response to prevent account enumeration.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password telah dikirim. Periksa inbox dan folder spam.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, $password) use ($request) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
            $this->revokeDatabaseSessions($user);

            if ((int) Auth::guard('web')->id() === (int) $user->id) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Password berhasil direset. Silakan login kembali.']);
    }

    public function change(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers(), 'different:current_password'],
        ]);

        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();
        $currentSessionId = (int) Auth::guard('web')->id() === (int) $user->id
            ? $request->session()->getId()
            : null;
        $this->revokeDatabaseSessions($user, $currentSessionId);

        return response()->json(['message' => 'Password berhasil diubah. Sesi pada perangkat lain telah dikeluarkan.']);
    }

    private function revokeDatabaseSessions(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
                ->delete();
        }

        $user->setRememberToken(Str::random(60));
        $user->save();
    }
}
