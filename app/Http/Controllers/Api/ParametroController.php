<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Parametro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametroController extends Controller
{
    public function index(Bot $bot): JsonResponse
    {
        return response()->json($bot->parametros);
    }

    public function store(Request $request, Bot $bot): JsonResponse
    {
        $validated = $request->validate([
            'origem' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'peso_min' => 'nullable|numeric|min:0',
            'distancia_max' => 'nullable|numeric|min:0',
            'distancia_min' => 'nullable|numeric|min:0',
            'custo_min' => 'nullable|numeric|min:0',
            'tipo_veiculo' => 'nullable|string|in:ZZTRUCK,ZZBITRUCK,ZZCARRETA,ZZRODOTREM',
            'intervalo_busca' => 'nullable|integer|min:10|max:3600',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
        ]);
        $validated['bot_id'] = $bot->id;
        $parametro = Parametro::create($validated);
        return response()->json($parametro, 201);
    }

    public function show(Parametro $parametro): JsonResponse
    {
        return response()->json($parametro);
    }

    public function update(Request $request, Parametro $parametro): JsonResponse
    {
        $validated = $request->validate([
            'origem' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'peso_min' => 'nullable|numeric|min:0',
            'distancia_max' => 'nullable|numeric|min:0',
            'distancia_min' => 'nullable|numeric|min:0',
            'custo_min' => 'nullable|numeric|min:0',
            'tipo_veiculo' => 'nullable|string|in:ZZTRUCK,ZZBITRUCK,ZZCARRETA,ZZRODOTREM',
            'intervalo_busca' => 'nullable|integer|min:10|max:3600',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
        ]);
        $parametro->update($validated);
        return response()->json($parametro);
    }

    public function destroy(Parametro $parametro): JsonResponse
    {
        $parametro->delete();
        return response()->json(null, 204);
    }
}
