<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->json('whatsapp_numeros')->nullable()->after('emails_notificacao');
        });
    }

    public function down(): void
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->dropColumn('whatsapp_numeros');
        });
    }
};
