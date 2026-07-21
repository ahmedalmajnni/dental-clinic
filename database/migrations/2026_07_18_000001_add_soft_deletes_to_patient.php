<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `deleted_at` column to the patient table so patients can be *archived*
 * (soft-deleted) instead of permanently removed. Archiving keeps all their
 * invoices, payments and history intact — it just hides them from the lists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            $table->softDeletes(); // nullable deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
