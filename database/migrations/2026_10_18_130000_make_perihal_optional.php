<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surats', function (Blueprint $table): void {
            $table->text('perihal')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('surats')
            ->whereNull('perihal')
            ->update(['perihal' => 'Tidak dicantumkan']);

        Schema::table('surats', function (Blueprint $table): void {
            $table->text('perihal')->nullable(false)->change();
        });
    }
};
