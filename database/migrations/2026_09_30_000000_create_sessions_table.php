<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La tabla "sessions" del esqueleto de Laravel se había quedado fuera de
// create_users_table, así que una instalación desde cero con
// SESSION_DRIVER=database (lo que trae .env.example) caía con
// "no such table: sessions" en cualquier página que abriera sesión.
// El hasTable() la salta en las bases que ya la tienen.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
