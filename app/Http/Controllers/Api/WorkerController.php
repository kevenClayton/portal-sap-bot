<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CargaNotificacaoMail;
use App\Models\Bot;
use App\Models\Carga;
use App\Models\Execucao;
use App\Support\BotLogBuffer;
use App\Services\WhatsappCargaNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkerController extends Controller
{
    public function bot(): JsonResponse
    {
        $bot = Bot::where('status', 'ativo')->with(['parametros.cidadesAceitas', 'parametros.regrasCidades'])->first();
        if (! $bot) {
            return response()->json(['ativo' => false, 'bot' => null]);
        }
        $parametro = $bot->parametroAtual();
        if ($parametro) {
            $parametro->loadMissing(['cidadesAceitas', 'regrasCidades']);
        }

        return response()->json([
            'ativo' => true,
            'bot' => [
                'id' => $bot->id,
                'intervalo' => $bot->intervalo,
                'horario_inicio' => $bot->horario_inicio ? substr($bot->horario_inicio, 0, 5) : null,
                'horario_fim' => $bot->horario_fim ? substr($bot->horario_fim, 0, 5) : null,
            ],
            'parametros' => $parametro ? [
                'peso_min' => $parametro->peso_min,
                'peso_max' => $parametro->peso_max,
                'distancia_max' => $parametro->distancia_max,
                'distancia_min' => $parametro->distancia_min,
                'custo_min' => $parametro->custo_min,
                'tipo_veiculo' => $parametro->tipo_veiculo,
                'intervalo_busca' => $parametro->intervalo_busca,
                'horario_inicio' => $parametro->horario_inicio ? substr($parametro->horario_inicio, 0, 5) : null,
                'horario_fim' => $parametro->horario_fim ? substr($parametro->horario_fim, 0, 5) : null,
                'emails_notificacao' => $parametro->emails_notificacao ?? [],
                'whatsapp_numeros' => $parametro->whatsapp_numeros ?? [],
                'modo_teste' => (bool) $parametro->modo_teste,
                'portal_usuario' => $parametro->portal_usuario,
                'portal_senha' => $parametro->portal_senha,
                'cidades_origem_aceitas' => $parametro->cidadesAceitas
                    ->where('tipo', 'origem')
                    ->pluck('cidade')
                    ->map(fn (string $c) => mb_strtoupper(trim($c)))
                    ->values()
                    ->all(),
                'cidades_destino_aceitas' => $parametro->cidadesAceitas
                    ->where('tipo', 'destino')
                    ->pluck('cidade')
                    ->map(fn (string $c) => mb_strtoupper(trim($c)))
                    ->values()
                    ->all(),
                'regras' => $parametro->regrasCidades->map(fn ($r) => [
                    'aplica_a' => $r->aplica_a,
                    'cidade' => mb_strtoupper(trim($r->cidade)),
                    'peso_min_kg' => $r->peso_min_kg !== null ? (float) $r->peso_min_kg : null,
                    'peso_max_kg' => $r->peso_max_kg !== null ? (float) $r->peso_max_kg : null,
                    'valor_carga_min' => $r->valor_carga_min !== null ? (float) $r->valor_carga_min : null,
                    'valor_carga_max' => $r->valor_carga_max !== null ? (float) $r->valor_carga_max : null,
                ])->values()->all(),
            ] : null,
        ]);
    }

    public function storeCarga(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_id' => 'nullable|exists:bots,id',
            'rfq_uuid' => 'nullable|string',
            'rfq_id' => 'nullable|string',
            'origem' => 'nullable|string',
            'destino' => 'nullable|string',
            'peso' => 'nullable|numeric',
            'distancia' => 'nullable|numeric',
            'status' => 'required|in:analisada,capturada,ignorada,erro,simulada',
            'dados_json' => 'nullable|array',
            'tempo_resposta_ms' => 'nullable|integer',
        ]);

        $uuid = isset($validated['rfq_uuid']) && $validated['rfq_uuid'] !== ''
            ? $validated['rfq_uuid']
            : null;

        $previous = $uuid
            ? Carga::query()->where('rfq_uuid', $uuid)->first()
            : null;

        if ($uuid) {
            $carga = Carga::query()->updateOrCreate(
                ['rfq_uuid' => $uuid],
                $validated
            );
        } else {
            $carga = Carga::query()->create($validated);
        }

        if ($carga->bot_id) {
            if ($carga->status === 'capturada' && $previous?->status !== 'capturada') {
                $this->enviarNotificacaoCarga($carga);
            }
            if ($carga->status === 'simulada' && $previous?->status !== 'simulada') {
                $this->enviarNotificacaoCarga($carga);
            }
        }

        $code = $carga->wasRecentlyCreated ? 201 : 200;

        return response()->json($carga, $code);
    }

    private function enviarNotificacaoCarga(Carga $carga): void
    {
        $bot = Bot::with('parametros')->find($carga->bot_id);
        $param = $bot?->parametroAtual();
        if (! $param) {
            return;
        }

        $modoTeste = $carga->status === 'simulada';

        $emails = $param->emails_notificacao;
        if (is_array($emails) && $emails !== []) {
            $emails = array_values(array_filter(array_map('trim', $emails)));
            if ($emails !== []) {
                try {
                    Mail::to($emails)->send(new CargaNotificacaoMail($carga, $modoTeste));
                } catch (\Throwable $e) {
                    Log::warning('Falha ao enviar e-mail de carga: '.$e->getMessage(), [
                        'carga_id' => $carga->id,
                        'rfq_id' => $carga->rfq_id,
                    ]);
                }
            }
        }

        $whats = $param->whatsapp_numeros;
        if (is_array($whats) && $whats !== []) {
            WhatsappCargaNotifier::notify($carga, $whats, $modoTeste);
        }
    }

    public function storeExecucao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_id' => 'required|exists:bots,id',
            'cargas_analisadas' => 'sometimes|integer|min:0',
            'cargas_capturadas' => 'sometimes|integer|min:0',
            'cargas_ignoradas' => 'sometimes|integer|min:0',
            'cargas_simuladas' => 'sometimes|integer|min:0',
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
            'cargas_simuladas' => 'sometimes|integer|min:0',
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
            'evento' => 'required|string|max:255',
            'mensagem' => 'nullable|string',
            'contexto' => 'nullable|array',
        ]);
        $log = BotLogBuffer::append(
            $validated['bot_id'] ?? null,
            $validated['nivel'],
            $validated['evento'],
            $validated['mensagem'] ?? null,
            $validated['contexto'] ?? []
        );

        return response()->json($log, 201);
    }
}
