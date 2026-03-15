<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execucoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->unsignedInteger('cargas_analisadas')->default(0);
            $table->unsignedInteger('cargas_capturadas')->default(0);
            $table->unsignedInteger('cargas_ignoradas')->default(0);
            $table->timestamp('inicio_execucao')->nullable();
            $table->timestamp('fim_execucao')->nullable();
            $table->string('status')->default('em_andamento'); // em_andamento, concluida, erro
            $table->text('mensagem_erro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucoes');
    }
};
