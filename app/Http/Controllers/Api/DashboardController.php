<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Carga;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $bot = Bot::first();
        $hoje = today();

        $cargasAnalisadasHoje = Carga::hoje()->count();
        $cargasCapturadasHoje = Carga::hoje()->capturadas()->count();
        $cargasSimuladasHoje = Carga::hoje()->simuladas()->count();
        $totalAnalisadas = Carga::count();
        $totalCapturadas = Carga::capturadas()->count();
        $taxaSucesso = $totalAnalisadas > 0
            ? round(($totalCapturadas / $totalAnalisadas) * 100, 1)
            : 0;

        return response()->json([
            'bot' => $bot ? [
                'id' => $bot->id,
                'status' => $bot->status,
                'intervalo' => $bot->intervalo,
                'horario_inicio' => $bot->horario_inicio ? substr($bot->horario_inicio, 0, 5) : null,
                'horario_fim' => $bot->horario_fim ? substr($bot->horario_fim, 0, 5) : null,
            ] : null,
            'cargas_analisadas_hoje' => $cargasAnalisadasHoje,
            'cargas_capturadas_hoje' => $cargasCapturadasHoje,
            'cargas_simuladas_hoje' => $cargasSimuladasHoje,
            'cargas_capturadas_total' => $totalCapturadas,
            'taxa_sucesso' => $taxaSucesso,
        ]);
    }
}
