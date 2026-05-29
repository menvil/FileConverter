<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path')->unique();
            $table->string('mime_type')->nullable();
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('status', 30)->default('uploaded');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['extension']);
            $table->index(['status']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
