<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->nullable();
            $table->string('firstname', 50);
            $table->string('lastname', 50)->nullable();
            $table->string('phone', 15);
            $table->string('email', 100)->nullable();
            $table->string('relation', 50)->nullable();
            $table->integer('priority')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
