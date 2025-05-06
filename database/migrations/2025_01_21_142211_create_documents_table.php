<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Storage};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->date('expiration_date')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $docObjects = Storage::disk('s3')->allFiles('certificate');
        Storage::disk('s3')->delete($docObjects);

        Schema::dropIfExists('documents');
    }
};
