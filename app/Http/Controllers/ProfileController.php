<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($user->google_id && isset($data['email']) && mb_strtolower(trim($data['email'])) !== mb_strtolower(trim($user->email))) {
            return response()->json([
                'message' => 'Email tidak dapat diubah selama akun Google terhubung.',
                'errors' => ['email' => ['Putuskan hubungan Google terlebih dahulu sebelum mengubah email.']],
            ], 422);
        }

        $oldAvatar = $user->avatar;
        $newAvatar = null;
        try {
            if ($request->hasFile('avatar')) {
                $newAvatar = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $newAvatar;
            }
            $user->update($data);
        } catch (\Throwable $error) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $error;
        }
        if ($newAvatar && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user->fresh(),
        ]);
    }
}
