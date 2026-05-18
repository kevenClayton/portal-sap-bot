<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametro_regras', function (Blueprint $table) {
            $table->string('regra_grupo', 36)->nullable()->after('aplica_a');
            $table->index(['parametro_id', 'regra_grupo']);
        });
    }

    public function down(): void
    {
        Schema::table('parametro_regras', function (Blueprint $table) {
            $table->dropIndex(['parametro_id', 'regra_grupo']);
            $table->dropColumn('regra_grupo');
        });
    }
};
