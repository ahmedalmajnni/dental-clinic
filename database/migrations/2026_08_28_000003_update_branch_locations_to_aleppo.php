<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('branch')->where('name', 'Main Clinic — Mazzeh')->update([
            'name' => 'Main Clinic — Aleppo Center',
            'address' => 'Al-Jdeideh, Aleppo',
        ]);
        DB::table('branch')->where('name', 'Malki Branch')->update([
            'name' => 'Azizieh Branch',
            'address' => 'Al-Azizieh, Aleppo',
        ]);
        DB::table('branch')->where('name', 'Imaging Studio')->update([
            'name' => 'Imaging & Diagnostics Branch',
            'type' => 'clinic',
            'address' => 'Al-Jdeideh, Aleppo (ground floor)',
        ]);
        DB::table('branch')->where('name', 'Baramkeh Branch')->update([
            'name' => 'Midan Branch',
            'address' => 'Al-Midan, Aleppo',
        ]);
    }

    public function down(): void
    {
        DB::table('branch')->where('name', 'Main Clinic — Aleppo Center')->update([
            'name' => 'Main Clinic — Mazzeh',
            'address' => 'Mazzeh Villat Gharbiya, Damascus',
        ]);
        DB::table('branch')->where('name', 'Azizieh Branch')->update([
            'name' => 'Malki Branch',
            'address' => 'Al-Malki, Damascus',
        ]);
        DB::table('branch')->where('name', 'Imaging & Diagnostics Branch')->update([
            'name' => 'Imaging Studio',
            'type' => 'studio',
            'address' => 'Mazzeh Villat Gharbiya, Damascus (ground floor)',
        ]);
        DB::table('branch')->where('name', 'Midan Branch')->update([
            'name' => 'Baramkeh Branch',
            'address' => 'Baramkeh, Damascus',
        ]);
    }
};
