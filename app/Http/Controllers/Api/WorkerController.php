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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkerController extends Controller
{
    /** Não reenviar notificação (e-mail/WhatsApp) para a mesma carga+estado antes deste intervalo. */
    private const MINUTOS_COOLDOWN_NOTIFICACAO_CARGA = 240;

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
            ? (string) $validated['rfq_uuid']
            : null;

        $rfqId = isset($validated['rfq_id']) && $validated['rfq_id'] !== ''
            ? (string) $validated['rfq_id']
            : null;

        $botId = isset($validated['bot_id']) && $validated['bot_id'] !== ''
            ? (int) $validated['bot_id']
            : null;

        $previous = null;
        $carga = null;

        if ($uuid !== null) {
            $previous = Carga::query()->where('rfq_uuid', $uuid)->first();
            $carga = Carga::query()->updateOrCreate(
                ['rfq_uuid' => $uuid],
                $validated
            );
        } elseif ($rfqId !== null && $botId !== null) {
            $previous = Carga::query()
                ->where('bot_id', $botId)
                ->where('rfq_id', $rfqId)
                ->first();
            $carga = Carga::query()->updateOrCreate(
                ['bot_id' => $botId, 'rfq_id' => $rfqId],
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

        if ($carga->status === 'erro') {
            Log::error(
                'Erro ao tentar pegar carga: '.json_encode([
                    'carga_id' => $carga->id,
                    'bot_id' => $carga->bot_id,
                    'rfq_id' => $carga->rfq_id,
                    'rfq_uuid' => $carga->rfq_uuid,
                    'origem' => $carga->origem,
                    'destino' => $carga->destino,
                    'dados_json' => $carga->dados_json,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $code = $carga->wasRecentlyCreated ? 201 : 200;

        return response()->json($carga, $code);
    }

    private function enviarNotificacaoCarga(Carga $carga): void
    {
        $bot = Bot::with('parametros')->find($carga->bot_id);
        $param = $bot?->parametroAtual();
        if (! $param) {
            Log::error(
                'Parâmetros do bot não encontrados para notificação de carga: '.json_encode([
                    'carga_id' => $carga->id,
                    'bot_id' => $carga->bot_id,
                    'status' => $carga->status,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            return;
        }

        $modoTeste = $carga->status === 'simulada';

        $emails = $param->emails_notificacao;
        $listaEmails = [];
        if (is_array($emails) && $emails !== []) {
            $listaEmails = array_values(array_filter(array_map('trim', $emails)));
        }

        $whats = $param->whatsapp_numeros;
        $listaWhats = is_array($whats) && $whats !== [] ? $whats : [];

        if ($listaEmails === [] && $listaWhats === []) {
            return;
        }

        $chaveDedupe = $this->chaveDedupeNotificacaoCarga($carga);
        if (! Cache::add($chaveDedupe, 1, now()->addMinutes(self::MINUTOS_COOLDOWN_NOTIFICACAO_CARGA))) {
            return;
        }

        try {
            if ($listaEmails !== []) {
                Mail::to($listaEmails)->send(new CargaNotificacaoMail($carga, $modoTeste));
            }

            if ($listaWhats !== []) {
                WhatsappCargaNotifier::notify($carga, $listaWhats, $modoTeste);
            }
        } catch (\Throwable $e) {
            Cache::forget($chaveDedupe);
            Log::error('Falha ao enviar notificação de carga: '.$e->getMessage(), [
                'carga_id' => $carga->id,
                'rfq_id' => $carga->rfq_id,
            ]);
        }
    }

    private function chaveDedupeNotificacaoCarga(Carga $carga): string
    {
        $referenciaRfq = $carga->rfq_uuid !== null && $carga->rfq_uuid !== ''
            ? 'u:'.sha1((string) $carga->rfq_uuid)
            : (
                $carga->rfq_id !== null && $carga->rfq_id !== ''
                    ? 'r:'.sha1((string) $carga->rfq_id)
                    : 'c:'.(string) $carga->id
            );

        return sprintf(
            'carga_notif:%d:%s:%s',
            (int) $carga->bot_id,
            $referenciaRfq,
            (string) $carga->status
        );
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
