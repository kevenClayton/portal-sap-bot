<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargas', function (Blueprint $table): void {
            $table->string('ordem_frete')->nullable()->after('rfq_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table): void {
            $table->dropIndex(['ordem_frete']);
            $table->dropColumn('ordem_frete');
        });
    }
};
