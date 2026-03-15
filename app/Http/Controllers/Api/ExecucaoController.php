<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Execucao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecucaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Execucao::with('bot')->orderByDesc('created_at');
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }
        $execucoes = $query->paginate($request->get('per_page', 20));
        return response()->json($execucoes);
    }
}
