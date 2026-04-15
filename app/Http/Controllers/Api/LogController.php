<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotLog;
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

        $query = BotLog::with('bot')->orderByDesc('created_at');
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }
        if ($request->has('nivel')) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->has('evento')) {
            $query->where('evento', $request->evento);
        }
        $logs = $query->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Últimas N linhas em ordem cronológica (para abrir o terminal).
     */
    private function terminalInit(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('limit', 200), 1), 500);
        $query = BotLog::query()->with('bot');
        if ($request->filled('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }
        $logs = $query->orderByDesc('id')->limit($limit)->get()->reverse()->values();

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
        $query = BotLog::query()->with('bot')->where('id', '>', $afterId);
        if ($request->filled('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }
        $logs = $query->orderBy('id')->limit(150)->get();

        return response()->json(['data' => $logs]);
    }
}
