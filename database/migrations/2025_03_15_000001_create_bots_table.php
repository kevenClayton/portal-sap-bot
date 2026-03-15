<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('inativo'); // inativo, ativo
            $table->unsignedInteger('intervalo')->default(60); // segundos entre buscas
            $table->time('horario_inicio')->nullable();
            $table->time('horario_fim')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
