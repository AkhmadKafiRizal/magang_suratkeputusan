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
        $newPhoto = null;
        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('profile-photos', 'local');
            abort_unless($newPhoto, 500, 'Foto belum dapat disimpan. Silakan coba lagi.');
            $user->profile_photo_path = $newPhoto;
        } elseif ($request->boolean('remove_photo')) {
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
        if ($oldPhoto && $oldPhoto !== $user->profile_photo_path) {
            Storage::disk('local')->delete($oldPhoto);
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
        $path = $request->user()->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
