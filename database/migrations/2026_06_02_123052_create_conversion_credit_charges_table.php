<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_credit_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversion_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('estimated_amount');
            $table->unsignedInteger('captured_amount')->default(0);
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->string('status');
            $table->json('breakdown_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('conversion_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_credit_charges');
    }
};
