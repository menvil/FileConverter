<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversion_credit_charges', function (Blueprint $table) {
            $table->dropIndex('conversion_credit_charges_conversion_job_id_index');
            $table->unique('conversion_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversion_credit_charges', function (Blueprint $table) {
            $table->dropUnique('conversion_credit_charges_conversion_job_id_unique');
            $table->index('conversion_job_id');
        });
    }
};
