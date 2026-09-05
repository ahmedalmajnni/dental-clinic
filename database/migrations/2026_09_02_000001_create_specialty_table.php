<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialty', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 120)->unique();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("INSERT INTO specialty (name) SELECT DISTINCT specialty FROM employee WHERE specialty IS NOT NULL AND TRIM(specialty) <> ''");
    }

    public function down(): void
    {
        Schema::dropIfExists('specialty');
    }
};
