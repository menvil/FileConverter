<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_file_id')->constrained('files')->restrictOnDelete();
            $table->foreignId('result_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('source_format', 20);
            $table->string('target_format', 20);
            $table->string('converter_key', 100);
            $table->json('options_json');
            $table->string('status', 30)->default(ConversionStatus::Queued->value);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_jobs');
    }
};
