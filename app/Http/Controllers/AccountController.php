<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $uploadingPhoto = $request->hasFile('photo');
        $removingPhoto = ! $uploadingPhoto && $request->boolean('remove_photo');
        if (is_string($request->input('username'))) {
            $request->merge(['username' => Str::lower(trim($request->input('username')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/\A[a-z0-9._-]+\z/', Rule::unique('users')->ignore($user->id)],
            'current_password' => [Rule::requiredIf($request->input('username') !== $user->username), 'nullable', 'current_password'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'username.unique' => 'Username sudah digunakan akun lain.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, garis bawah, atau tanda hubung.',
            'current_password.required' => 'Masukkan kata sandi saat ini untuk mengubah username.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'photo.image' => 'File harus berupa foto.',
            'photo.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);
        if (User::query()->whereKeyNot($user->id)->whereRaw('LOWER(TRIM(username)) = ?', [$data['username']])->exists()) {
            throw ValidationException::withMessages(['username' => 'Username sudah digunakan akun lain.']);
        }

        $oldPhoto = $user->profile_photo_path;
        if ($removingPhoto && ! $oldPhoto) {
            return to_route('account.edit')->with('warning', 'Foto profil belum tersedia untuk dihapus.');
        }

        if ($removingPhoto) {
            $data['name'] = $user->name;
            $data['username'] = $user->username;
        }

        $newPhoto = null;
        if ($uploadingPhoto) {
            $newPhoto = $request->file('photo')->store('profile-photos', 'local');
            abort_unless($newPhoto, 500, 'Foto belum dapat disimpan. Silakan coba lagi.');
            $user->profile_photo_path = $newPhoto;
        } elseif ($removingPhoto) {
            $user->profile_photo_path = null;
        }

        try {
            $user->fill(['name' => $data['name'], 'username' => $data['username']])->saveOrFail();
        } catch (\Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto);
            }
            throw $exception;
        }

        if ($oldPhoto && $oldPhoto !== $user->profile_photo_path && ! $this->deleteManagedProfilePhoto($oldPhoto)) {
            if ($removingPhoto) {
                try {
                    $user->forceFill(['profile_photo_path' => $oldPhoto])->saveOrFail();
                } catch (\Throwable $exception) {
                    report($exception);
                }

                return to_route('account.edit')->with('error', 'Foto profil belum dapat dihapus. Silakan coba lagi.');
            }

            report(new \RuntimeException('Foto profil lama tidak dapat dibersihkan dari penyimpanan privat.'));
        }

        if ($uploadingPhoto) {
            return to_route('account.edit')->with('success', 'Foto profil berhasil diperbarui.');
        }

        if ($removingPhoto) {
            return to_route('account.edit')->with('success', 'Foto profil berhasil dihapus.');
        }

        return to_route('account.edit')->with('success', 'Pengaturan akun berhasil disimpan. Username login Anda: '.$user->username.'.');
    }

    public function password(Request $request)
    {
        $data = $request->validateWithBag('passwordChange', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru harus sama.',
        ]);
        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->saveOrFail();
        $request->user()->passwordResetOtps()->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $request->session()->regenerate();

        return to_route('account.edit')->with('success', 'Kata sandi berhasil diperbarui. Gunakan kata sandi baru saat login berikutnya.');
    }

    public function photo(Request $request)
    {
        return $this->profilePhotoResponse($request->user());
    }

    public function userPhoto(User $user)
    {
        return $this->profilePhotoResponse($user);
    }

    private function profilePhotoResponse(User $user)
    {
        $path = $user->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function deleteManagedProfilePhoto(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $isManagedPath = Str::startsWith($normalizedPath, 'profile-photos/')
            && ! Str::contains($normalizedPath, ['../', '/..'])
            && basename($normalizedPath) !== '';

        if (! $isManagedPath) {
            return false;
        }

        $disk = Storage::disk('local');

        return ! $disk->exists($normalizedPath) || $disk->delete($normalizedPath);
    }
}
