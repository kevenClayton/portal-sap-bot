<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tempo_resposta_ms foi criado por engano como timestamp; o worker envia milissegundos (inteiro).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE cargas MODIFY tempo_resposta_ms INT UNSIGNED NULL');

            return;
        }

        Schema::table('cargas', function (Blueprint $table) {
            $table->unsignedInteger('tempo_resposta_ms')->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE cargas MODIFY tempo_resposta_ms TIMESTAMP NULL');

            return;
        }

        Schema::table('cargas', function (Blueprint $table) {
            $table->timestamp('tempo_resposta_ms')->nullable()->change();
        });
    }
};
