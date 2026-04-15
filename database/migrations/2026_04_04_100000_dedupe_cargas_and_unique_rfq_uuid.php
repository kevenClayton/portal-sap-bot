<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $keepIds = DB::table('cargas')
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('rfq_uuid')
            ->where('rfq_uuid', '!=', '')
            ->groupBy('rfq_uuid')
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            DB::table('cargas')
                ->whereNotNull('rfq_uuid')
                ->where('rfq_uuid', '!=', '')
                ->whereNotIn('id', $keepIds->all())
                ->delete();
        }

        Schema::table('cargas', function (Blueprint $table) {
            $table->unique('rfq_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('cargas', function (Blueprint $table) {
            $table->dropUnique(['rfq_uuid']);
        });
    }
};
