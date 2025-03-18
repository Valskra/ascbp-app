<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->boolean('manage_admin')->default(false);
            $table->boolean('manage_events')->default(false);
            $table->boolean('create_events')->default(false);
            $table->boolean('manage_members')->default(false);
            $table->boolean('manage_articles')->default(false);
            $table->boolean('create_articles')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
