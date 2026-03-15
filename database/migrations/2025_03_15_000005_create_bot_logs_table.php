<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->string('nivel'); // info, warning, error
            $table->string('evento'); // login, consulta, carga_encontrada, carga_aceita, erro_api, erro_auth
            $table->text('mensagem')->nullable();
            $table->json('contexto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_logs');
    }
};
