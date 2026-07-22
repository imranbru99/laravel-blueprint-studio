<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_studio_history', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);
            $table->string('resource', 150);
            $table->json('payload')->nullable();
            $table->json('files')->nullable();
            $table->string('status', 30)->default('success');
            $table->text('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index('resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_studio_history');
    }
};
