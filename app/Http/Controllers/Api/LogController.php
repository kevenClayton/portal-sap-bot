<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BotLogBuffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('terminal_init')) {
            return $this->terminalInit($request);
        }
        if ($request->filled('after_id')) {
            return $this->terminalTail($request);
        }

        $porPagina = min(max((int) $request->get('per_page', 50), 1), 200);
        $paginaAtual = max((int) $request->get('page', 1), 1);
        $botId = $request->filled('bot_id') ? (int) $request->bot_id : null;
        $nivel = $request->filled('nivel') ? (string) $request->nivel : null;
        $evento = $request->filled('evento') ? (string) $request->evento : null;
        $logs = BotLogBuffer::paginate($porPagina, $paginaAtual, $botId, $nivel, $evento);

        return response()->json($logs);
    }

    /**
     * Últimas N linhas em ordem cronológica (para abrir o terminal).
     */
    private function terminalInit(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('limit', 200), 1), 500);
        $botId = $request->filled('bot_id') ? (int) $request->bot_id : null;
        $logs = BotLogBuffer::terminalInit($botId, $limit);

        return response()->json(['data' => $logs]);
    }

    /**
     * Novas linhas após after_id, em ordem cronológica (polling).
     */
    private function terminalTail(Request $request): JsonResponse
    {
        $afterId = (int) $request->get('after_id', 0);
        if ($afterId < 1) {
            return response()->json(['data' => []]);
        }
        $botId = $request->filled('bot_id') ? (int) $request->bot_id : null;
        $logs = BotLogBuffer::terminalTail($botId, $afterId, 150);

        return response()->json(['data' => $logs]);
    }
}
