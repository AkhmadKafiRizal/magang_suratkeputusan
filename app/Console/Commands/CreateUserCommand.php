<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    /**
     * Nama command yang dijalankan dari terminal.
     */
    protected $signature = 'user:create';

    /**
     * Deskripsi command.
     */
    protected $description = 'Membuat akun pegawai baru untuk SIARKA Bapenda Kabupaten Jember';

    /**
     * Jalankan command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('=== Tambah Akun Pegawai ===');
        $this->newLine();

        // =========================
        // NAMA LENGKAP
        // =========================
        do {
            $name = trim((string) $this->ask('Nama lengkap'));

            $validator = Validator::make(
                ['name' => $name],
                [
                    'name' => ['required', 'string', 'max:255'],
                ],
                [
                    'name.required' => 'Nama lengkap wajib diisi.',
                    'name.max' => 'Nama lengkap maksimal 255 karakter.',
                ]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());
            }
        } while ($validator->fails());

        // =========================
        // USERNAME
        // =========================
        do {
            $username = strtolower(trim((string) $this->ask('Username')));

            $validator = Validator::make(
                ['username' => $username],
                [
                    'username' => [
                        'required',
                        'string',
                        'max:50',
                        'regex:/^[a-z0-9._]+$/',
                        'unique:users,username',
                    ],
                ],
                [
                    'username.required' => 'Username wajib diisi.',
                    'username.max' => 'Username maksimal 50 karakter.',
                    'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, dan underscore.',
                    'username.unique' => 'Username tersebut sudah digunakan.',
                ]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());
            }
        } while ($validator->fails());

        // =========================
        // EMAIL
        // =========================
        do {
            $email = strtolower(trim((string) $this->ask('Email/Gmail')));

            $validator = Validator::make(
                ['email' => $email],
                [
                    'email' => [
                        'required',
                        'email',
                        'max:255',
                        'unique:users,email',
                    ],
                ],
                [
                    'email.required' => 'Email wajib diisi.',
                    'email.email' => 'Format email tidak valid.',
                    'email.max' => 'Email maksimal 255 karakter.',
                    'email.unique' => 'Email tersebut sudah digunakan.',
                ]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());
            }
        } while ($validator->fails());

        // =========================
        // PASSWORD
        // =========================
        while (true) {
            // secret() membuat password tidak terlihat saat diketik.
            $password = (string) $this->secret('Password');
            $passwordConfirmation = (string) $this->secret('Konfirmasi password');

            $validator = Validator::make(
                [
                    'password' => $password,
                    'password_confirmation' => $passwordConfirmation,
                ],
                [
                    'password' => [
                        'required',
                        'string',
                        'min:8',
                        'confirmed',
                    ],
                ],
                [
                    'password.required' => 'Password wajib diisi.',
                    'password.min' => 'Password minimal 8 karakter.',
                    'password.confirmed' => 'Konfirmasi password tidak sama.',
                ]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());
                $this->newLine();
                continue;
            }

            break;
        }

        // =========================
        // RINGKASAN
        // =========================
        $this->newLine();
        $this->info('Data akun yang akan dibuat:');
        $this->newLine();

        $this->table(
            ['Data', 'Isi'],
            [
                ['Nama', $name],
                ['Username', $username],
                ['Email', $email],
                ['Role', 'pegawai'],
            ]
        );

        $this->newLine();

        // =========================
        // KONFIRMASI
        // =========================
        if (! $this->confirm('Buat akun ini?', true)) {
            $this->warn('Pembuatan akun dibatalkan.');

            return self::SUCCESS;
        }

        // =========================
        // BUAT USER
        // =========================
        try {
            $user = new User();

            $user->name = $name;
            $user->username = $username;
            $user->email = $email;
            $user->role = 'pegawai';

            /*
             * Password di-hash menggunakan Laravel.
             * Password asli TIDAK disimpan ke database.
             */
            $user->password = Hash::make($password);

            $user->save();

            $this->newLine();
            $this->info('✓ Akun pegawai berhasil dibuat.');
            $this->newLine();

            $this->table(
                ['Data', 'Isi'],
                [
                    ['Nama', $user->name],
                    ['Username', $user->username],
                    ['Email', $user->email],
                    ['Role', $user->role],
                ]
            );

            $this->newLine();
            $this->comment('Password telah disimpan dalam bentuk hash.');
            $this->comment('Password tidak ditampilkan kembali demi keamanan.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Akun gagal dibuat.');
            $this->error('Pesan: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
