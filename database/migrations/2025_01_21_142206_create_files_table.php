<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Storage};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fileable_id')->nullable();
            $table->string('fileable_type')->nullable();

            $table->string('name', 100);
            $table->char('extension', 5);
            $table->string('mimetype', 50)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('hash', 64)->after('size');

            $table->text('path');
            $table->string('disk')->default('public');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $objects = Storage::disk('s3')->allFiles('certificate');

        // 2. Supprime-les en bloc (Multi-Object Delete en arrière-plan) 
        Storage::disk('s3')->delete($objects);

        // 3. Supprime la table
        Schema::dropIfExists('files');
    }
};
