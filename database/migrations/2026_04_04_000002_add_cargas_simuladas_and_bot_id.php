<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execucoes', function (Blueprint $table) {
            $table->unsignedInteger('cargas_simuladas')->default(0)->after('cargas_ignoradas');
        });

        Schema::table('cargas', function (Blueprint $table) {
            $table->foreignId('bot_id')->nullable()->after('id')->constrained('bots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropForeign(['bot_id']);
            $table->dropColumn('bot_id');
        });

        Schema::table('execucoes', function (Blueprint $table) {
            $table->dropColumn('cargas_simuladas');
        });
    }
};
