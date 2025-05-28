<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('category', 50)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('registration_open')->nullable();
            $table->dateTime('registration_close')->nullable();
            $table->integer('max_participants')->nullable();
            $table->boolean('members_only')->default(false)->after('max_participants');
            $table->boolean('requires_medical_certificate')->default(false)->after('members_only');
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->onDelete('set null');
            $table->foreignId('organizer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('location', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
