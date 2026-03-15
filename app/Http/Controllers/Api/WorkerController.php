<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotLog;
use App\Models\Carga;
use App\Models\Execucao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function bot(): JsonResponse
    {
        $bot = Bot::where('status', 'ativo')->with('parametros')->first();
        if (! $bot) {
            return response()->json(['ativo' => false, 'bot' => null]);
        }
        $parametro = $bot->parametroAtual();
        return response()->json([
            'ativo' => true,
            'bot' => [
                'id' => $bot->id,
                'intervalo' => $bot->intervalo,
                'horario_inicio' => $bot->horario_inicio ? substr($bot->horario_inicio, 0, 5) : null,
                'horario_fim' => $bot->horario_fim ? substr($bot->horario_fim, 0, 5) : null,
            ],
            'parametros' => $parametro ? [
                'origem' => $parametro->origem,
                'destino' => $parametro->destino,
                'peso_min' => $parametro->peso_min,
                'distancia_max' => $parametro->distancia_max,
                'distancia_min' => $parametro->distancia_min,
                'custo_min' => $parametro->custo_min,
                'tipo_veiculo' => $parametro->tipo_veiculo,
                'intervalo_busca' => $parametro->intervalo_busca,
                'horario_inicio' => $parametro->horario_inicio ? substr($parametro->horario_inicio, 0, 5) : null,
                'horario_fim' => $parametro->horario_fim ? substr($parametro->horario_fim, 0, 5) : null,
            ] : null,
        ]);
    }

    public function storeCarga(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_uuid' => 'nullable|string',
            'rfq_id' => 'nullable|string',
            'origem' => 'nullable|string',
            'destino' => 'nullable|string',
            'peso' => 'nullable|numeric',
            'distancia' => 'nullable|numeric',
            'status' => 'required|in:analisada,capturada,ignorada,erro',
            'dados_json' => 'nullable|array',
            'tempo_resposta_ms' => 'nullable|integer',
        ]);
        $carga = Carga::create($validated);
        return response()->json($carga, 201);
    }

    public function storeExecucao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_id' => 'required|exists:bots,id',
            'cargas_analisadas' => 'sometimes|integer|min:0',
            'cargas_capturadas' => 'sometimes|integer|min:0',
            'cargas_ignoradas' => 'sometimes|integer|min:0',
            'inicio_execucao' => 'sometimes|date',
        ]);
        $validated['status'] = 'em_andamento';
        $validated['inicio_execucao'] = $validated['inicio_execucao'] ?? now();
        $execucao = Execucao::create($validated);
        return response()->json($execucao, 201);
    }

    public function updateExecucao(Request $request, Execucao $execucao): JsonResponse
    {
        $validated = $request->validate([
            'cargas_analisadas' => 'sometimes|integer|min:0',
            'cargas_capturadas' => 'sometimes|integer|min:0',
            'cargas_ignoradas' => 'sometimes|integer|min:0',
            'fim_execucao' => 'sometimes|date',
            'status' => 'sometimes|in:em_andamento,concluida,erro',
            'mensagem_erro' => 'nullable|string',
        ]);
        $execucao->update($validated);
        return response()->json($execucao);
    }

    public function storeLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_id' => 'nullable|exists:bots,id',
            'nivel' => 'required|in:info,warning,error',
            'evento' => 'required|string|max:100',
            'mensagem' => 'nullable|string',
            'contexto' => 'nullable|array',
        ]);
        $log = BotLog::registrar(
            $validated['bot_id'] ?? null,
            $validated['nivel'],
            $validated['evento'],
            $validated['mensagem'] ?? null,
            $validated['contexto'] ?? []
        );
        return response()->json($log, 201);
    }
}
