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
            $table->dateTime('publish_date');
            $table->text('content');
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->string('title', 255)->after('id');
            $table->text('excerpt')->nullable()->after('title');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade')->after('content');
            $table->boolean('is_pinned')->default(false)->after('event_id');
            $table->enum('status', ['draft', 'published', 'archived'])->default('published')->after('is_pinned');
            $table->integer('views_count')->default(0)->after('status');
            $table->foreignId('file_id')->nullable()->change();
            $table->dropForeign(['file_id']);
            $table->foreign('file_id')->references('id')->on('files')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
        });
    }
};
