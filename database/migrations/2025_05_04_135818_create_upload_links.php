<?php

// database/migrations/2024_05_xx_000000_create_upload_links.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('upload_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('token', 80)->unique();          // URL-safe
            $table->string('title', 100)->nullable();        // titre imposé ou libre
            $table->timestamp('expires_at');                 // +1 j / +1 sem / +3 sem
            $table->timestamp('used_at')->nullable();        // null ⇒ pas encore utilisé

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_links');
    }
};
