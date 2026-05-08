<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Database\Eloquent\Builder;
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
        $this->applyListFilters($request, $query);
        $cargas = $query->paginate($request->get('per_page', 20));

        return response()->json($cargas);
    }

    public function aceitas(Request $request): JsonResponse
    {
        $query = Carga::query()->aceitas()->orderByDesc('created_at');
        if ($request->filled('status')) {
            $st = $request->status;
            if (in_array($st, ['capturada', 'simulada'], true)) {
                $query->where('status', $st);
            }
        }
        $this->applyListFilters($request, $query);
        $cargas = $query->paginate($request->get('per_page', 20));

        return response()->json($cargas);
    }

    public function analisadas(Request $request): JsonResponse
    {
        $query = Carga::analisadas()->orderByDesc('created_at');
        $this->applyListFilters($request, $query);
        $cargas = $query->paginate($request->get('per_page', 20));

        return response()->json($cargas);
    }

    public function capturadas(Request $request): JsonResponse
    {
        $query = Carga::capturadas()->orderByDesc('created_at');
        $this->applyListFilters($request, $query);
        $cargas = $query->paginate($request->get('per_page', 20));

        return response()->json($cargas);
    }

    public function simuladas(Request $request): JsonResponse
    {
        $query = Carga::simuladas()->orderByDesc('created_at');
        $this->applyListFilters($request, $query);
        $cargas = $query->paginate($request->get('per_page', 20));

        return response()->json($cargas);
    }

    private function applyListFilters(Request $request, Builder $query): void
    {
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $term = trim((string) $request->busca);
            if ($term !== '') {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $q) use ($like) {
                    $q->where('origem', 'like', $like)
                        ->orWhere('destino', 'like', $like)
                        ->orWhere('rfq_id', 'like', $like)
                        ->orWhere('rfq_uuid', 'like', $like)
                        ->orWhere('ordem_frete', 'like', $like);
                });
            }
        }
    }
}
