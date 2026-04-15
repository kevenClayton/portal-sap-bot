<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function index(): JsonResponse
    {
        $bots = Bot::with(['parametros.cidadesAceitas', 'parametros.regrasCidades'])->get();

        return response()->json($bots);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:ativo,inativo',
            'intervalo' => 'sometimes|integer|min:10|max:3600',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
        ]);
        $bot = Bot::create($validated);

        return response()->json($bot, 201);
    }

    public function show(Bot $bot): JsonResponse
    {
        $bot->load(['parametros.cidadesAceitas', 'parametros.regrasCidades']);

        return response()->json($bot);
    }

    public function update(Request $request, Bot $bot): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:ativo,inativo',
            'intervalo' => 'sometimes|integer|min:10|max:3600',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
        ]);
        $bot->update($validated);

        return response()->json($bot);
    }

    public function destroy(Bot $bot): JsonResponse
    {
        $bot->delete();

        return response()->json(null, 204);
    }

    public function iniciar(Bot $bot): JsonResponse
    {
        $bot->update(['status' => 'ativo']);

        return response()->json(['message' => 'Bot iniciado', 'bot' => $bot]);
    }

    public function parar(Bot $bot): JsonResponse
    {
        $bot->update(['status' => 'inativo']);

        return response()->json(['message' => 'Bot parado', 'bot' => $bot]);
    }

    public function reiniciar(Bot $bot): JsonResponse
    {
        $bot->update(['status' => 'inativo']);
        // Worker externo deve detectar e reiniciar; aqui apenas ciclo stop/start
        $bot->update(['status' => 'ativo']);

        return response()->json(['message' => 'Bot reiniciado', 'bot' => $bot]);
    }
}
