<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertTimestamps('UTC', 'Asia/Jakarta');
    }

    public function down(): void
    {
        $this->convertTimestamps('Asia/Jakarta', 'UTC');
    }

    private function convertTimestamps(string $fromTimezone, string $toTimezone): void
    {
        DB::table('surats')
            ->select(['id', 'ditugaskan_pada', 'selesai_pada', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($surats) use ($fromTimezone, $toTimezone): void {
                foreach ($surats as $surat) {
                    $updates = [];

                    foreach (['ditugaskan_pada', 'selesai_pada', 'created_at', 'updated_at'] as $field) {
                        if ($surat->{$field} !== null) {
                            $updates[$field] = Carbon::parse($surat->{$field}, $fromTimezone)
                                ->setTimezone($toTimezone)
                                ->format('Y-m-d H:i:s');
                        }
                    }

                    if ($updates !== []) {
                        DB::table('surats')->where('id', $surat->id)->update($updates);
                    }
                }
            });
    }
};
