<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('excerpt')->nullable();
            $table->dateTime('publish_date');
            $table->text('content');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_post')->default(false);
            $table->json('metadata')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->integer('views_count')->default(0);
            $table->foreignId('file_id')->nullable()->constrained('files')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
