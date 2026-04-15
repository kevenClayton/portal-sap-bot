<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->json('emails_notificacao')->nullable()->after('horario_fim');
            $table->boolean('modo_teste')->default(false)->after('emails_notificacao');
            $table->json('cidades_origem')->nullable()->after('modo_teste');
            $table->json('cidades_destino')->nullable()->after('cidades_origem');
            $table->decimal('peso_max', 12, 2)->nullable()->after('peso_min');
        });
    }

    public function down(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->dropColumn([
                'emails_notificacao',
                'modo_teste',
                'cidades_origem',
                'cidades_destino',
                'peso_max',
            ]);
        });
    }
};
