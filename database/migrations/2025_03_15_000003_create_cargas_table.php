<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargas', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_uuid')->nullable()->index();
            $table->string('rfq_id')->nullable()->index();
            $table->string('origem')->nullable();
            $table->string('destino')->nullable();
            $table->decimal('peso', 12, 2)->nullable();
            $table->decimal('distancia', 12, 2)->nullable();
            $table->string('status')->default('analisada'); // analisada, capturada, ignorada, erro
            $table->json('dados_json')->nullable();
            $table->unsignedInteger('tempo_resposta_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargas');
    }
};
