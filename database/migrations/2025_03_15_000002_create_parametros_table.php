<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->string('origem')->nullable();
            $table->string('destino')->nullable();
            $table->decimal('peso_min', 12, 2)->nullable();
            $table->decimal('distancia_max', 12, 2)->nullable();
            $table->decimal('distancia_min', 12, 2)->nullable();
            $table->decimal('custo_min', 12, 2)->nullable();
            $table->string('tipo_veiculo')->nullable(); // ZZTRUCK, ZZBITRUCK, ZZCARRETA, ZZRODOTREM
            $table->unsignedInteger('intervalo_busca')->default(60);
            $table->time('horario_inicio')->nullable();
            $table->time('horario_fim')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
