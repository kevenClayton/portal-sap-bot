<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro_cidades_aceitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametro_id')->constrained('parametros')->cascadeOnDelete();
            $table->string('tipo', 16);
            $table->string('cidade', 255);
            $table->timestamps();
            $table->index(['parametro_id', 'tipo']);
        });

        Schema::create('parametro_regras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametro_id')->constrained('parametros')->cascadeOnDelete();
            $table->string('aplica_a', 16);
            $table->string('cidade', 255);
            $table->decimal('peso_min_kg', 14, 2)->nullable();
            $table->decimal('peso_max_kg', 14, 2)->nullable();
            $table->decimal('valor_carga_min', 18, 2)->nullable();
            $table->decimal('valor_carga_max', 18, 2)->nullable();
            $table->timestamps();
            $table->index(['parametro_id', 'aplica_a']);
        });

        foreach (DB::table('parametros')->cursor() as $row) {
            $co = $row->cidades_origem ?? null;
            if (is_string($co)) {
                $co = json_decode($co, true) ?: [];
            }
            if (! is_array($co)) {
                $co = [];
            }
            foreach ($co as $c) {
                $c = is_string($c) ? trim($c) : '';
                if ($c === '') {
                    continue;
                }
                DB::table('parametro_cidades_aceitas')->insert([
                    'parametro_id' => $row->id,
                    'tipo' => 'origem',
                    'cidade' => $c,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $cd = $row->cidades_destino ?? null;
            if (is_string($cd)) {
                $cd = json_decode($cd, true) ?: [];
            }
            if (! is_array($cd)) {
                $cd = [];
            }
            foreach ($cd as $c) {
                $c = is_string($c) ? trim($c) : '';
                if ($c === '') {
                    continue;
                }
                DB::table('parametro_cidades_aceitas')->insert([
                    'parametro_id' => $row->id,
                    'tipo' => 'destino',
                    'cidade' => $c,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('parametros', function (Blueprint $table) {
            $table->dropColumn(['cidades_origem', 'cidades_destino']);
        });
    }

    public function down(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->json('cidades_origem')->nullable()->after('modo_teste');
            $table->json('cidades_destino')->nullable()->after('cidades_origem');
        });

        Schema::dropIfExists('parametro_regras');
        Schema::dropIfExists('parametro_cidades_aceitas');
    }
};
