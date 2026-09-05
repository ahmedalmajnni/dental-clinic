<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_request', function (Blueprint $table) {
            $table->time('preferred_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_request', function (Blueprint $table) {
            $table->dropColumn('preferred_time');
        });
    }
};