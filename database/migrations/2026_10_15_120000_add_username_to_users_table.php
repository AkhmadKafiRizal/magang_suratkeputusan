<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
        });

        $used = [];

        DB::table('users')
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$used): void {
                $emailLocalPart = Str::before((string) $user->email, '@');
                $base = $this->normalizeUsername($emailLocalPart);

                if ($base === '') {
                    $base = $this->normalizeUsername((string) $user->name);
                }

                if ($base === '' || strlen($base) < 3) {
                    $base = 'pegawai'.(int) $user->id;
                }

                $base = substr($base, 0, 50);
                $username = $base;
                $suffix = 2;

                while (isset($used[$username])) {
                    $suffixText = (string) $suffix++;
                    $username = substr($base, 0, 50 - strlen($suffixText)).$suffixText;
                }

                $used[$username] = true;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function normalizeUsername(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '.', $value) ?? '';

        return trim(preg_replace('/[._-]{2,}/', '.', $value) ?? '', '._-');
    }
};
