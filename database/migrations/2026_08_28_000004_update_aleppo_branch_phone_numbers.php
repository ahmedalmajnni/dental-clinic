<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('branch')
            ->whereIn('name', [
                'Main Clinic — Aleppo Center',
                'Azizieh Branch',
                'Imaging & Diagnostics Branch',
                'Midan Branch',
            ])
            ->where('phone', 'like', '+963 11%')
            ->update(['phone' => DB::raw("replace(phone, '+963 11', '+963 21')")]);
    }

    public function down(): void
    {
        DB::table('branch')
            ->whereIn('name', [
                'Main Clinic — Aleppo Center',
                'Azizieh Branch',
                'Imaging & Diagnostics Branch',
                'Midan Branch',
            ])
            ->where('phone', 'like', '+963 21%')
            ->update(['phone' => DB::raw("replace(phone, '+963 21', '+963 11')")]);
    }
};
