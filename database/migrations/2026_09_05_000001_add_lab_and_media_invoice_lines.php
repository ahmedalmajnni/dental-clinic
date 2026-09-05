<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_line', function (Blueprint $table) {
            $table->uuid('lab_case_id')->nullable();
            $table->uuid('media_id')->nullable();
            $table->foreign('lab_case_id')->references('id')->on('lab_case')->restrictOnDelete();
            $table->foreign('media_id')->references('id')->on('media')->restrictOnDelete();
        });

        Schema::table('invoice_line', function (Blueprint $table) {
            $table->uuid('treatment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_line', function (Blueprint $table) {
            $table->dropForeign(['lab_case_id']);
            $table->dropForeign(['media_id']);
            $table->dropColumn(['lab_case_id', 'media_id']);
            $table->uuid('treatment_id')->nullable(false)->change();
        });
    }
};