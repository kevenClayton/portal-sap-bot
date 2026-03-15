<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CargaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Carga::query()->orderByDesc('created_at');
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->has('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        $cargas = $query->paginate($request->get('per_page', 20));
        return response()->json($cargas);
    }

    public function analisadas(Request $request): JsonResponse
    {
        $cargas = Carga::analisadas()
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));
        return response()->json($cargas);
    }

    public function capturadas(Request $request): JsonResponse
    {
        $cargas = Carga::capturadas()
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));
        return response()->json($cargas);
    }
}
