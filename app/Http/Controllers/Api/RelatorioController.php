<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    /**
     * Agregações estratégicas (uma linha por RFQ — deduplicado por rfq_uuid).
     */
    public function resumo(Request $request): JsonResponse
    {
        $q = Carga::query();

        if ($request->filled('data_inicio')) {
            $q->whereDate('updated_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $q->whereDate('updated_at', '<=', $request->data_fim);
        }

        $base = clone $q;

        $porOrigem = (clone $base)
            ->select('origem', DB::raw('COUNT(*) as total'))
            ->whereNotNull('origem')
            ->where('origem', '!=', '')
            ->groupBy('origem')
            ->orderByDesc('total')
            ->orderBy('origem')
            ->get();

        $porDestino = (clone $base)
            ->select('destino', DB::raw('COUNT(*) as total'))
            ->whereNotNull('destino')
            ->where('destino', '!=', '')
            ->groupBy('destino')
            ->orderByDesc('total')
            ->orderBy('destino')
            ->get();

        $porRota = (clone $base)
            ->select('origem', 'destino', DB::raw('COUNT(*) as total'))
            ->whereNotNull('origem')
            ->whereNotNull('destino')
            ->where('origem', '!=', '')
            ->where('destino', '!=', '')
            ->groupBy('origem', 'destino')
            ->orderByDesc('total')
            ->limit(200)
            ->get();

        $porStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $pesoPorOrigem = (clone $base)
            ->select(
                'origem',
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(peso), 0) as peso_total')
            )
            ->whereNotNull('origem')
            ->where('origem', '!=', '')
            ->groupBy('origem')
            ->orderByDesc('peso_total')
            ->limit(50)
            ->get();

        return response()->json([
            'total_cargas' => $base->count(),
            'por_origem' => $porOrigem,
            'por_destino' => $porDestino,
            'por_rota' => $porRota,
            'por_status' => $porStatus,
            'peso_por_origem' => $pesoPorOrigem,
        ]);
    }
}
