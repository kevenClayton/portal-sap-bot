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
}
