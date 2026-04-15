<?php

namespace App\Services;

use App\Models\Carga;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dispara POST JSON para a API de WhatsApp configurada em WHATSAPP_NOTIFY_URL.
 * Contrato sugerido do corpo: event, modo_teste, numeros, mensagem_texto, carga{...}
 */
class WhatsappCargaNotifier
{
    public static function notify(Carga $carga, array $numeros, bool $modoTeste): void
    {
        $url = config('services.whatsapp.notify_url');
        if (! is_string($url) || $url === '') {
            return;
        }

        $numeros = array_values(array_unique(array_filter(array_map('trim', $numeros))));
        if ($numeros === []) {
            return;
        }

        $ref = $carga->rfq_id ?? (string) $carga->id;
        $mensagemTexto = $modoTeste
            ? "Modo teste ativado: a carga {$ref} não foi capturada no SAP."
            : "Carga {$ref} capturada no SAP.";

        $payload = [
            'event' => $modoTeste ? 'carga_simulada' : 'carga_capturada',
            'modo_teste' => $modoTeste,
            'numeros' => $numeros,
            'mensagem_texto' => $mensagemTexto,
            'carga' => [
                'id' => $carga->id,
                'rfq_id' => $carga->rfq_id,
                'rfq_uuid' => $carga->rfq_uuid,
                'origem' => $carga->origem,
                'destino' => $carga->destino,
                'peso' => $carga->peso,
                'distancia' => $carga->distancia,
                'status' => $carga->status,
            ],
        ];

        try {
            $req = Http::timeout(15)->acceptJson()->asJson();
            $token = config('services.whatsapp.notify_token');
            if (is_string($token) && $token !== '') {
                $req = $req->withToken($token);
            }
            $res = $req->post($url, $payload);
            if (! $res->successful()) {
                Log::warning('API WhatsApp retornou HTTP '.$res->status(), [
                    'carga_id' => $carga->id,
                    'snippet' => substr($res->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao chamar API WhatsApp: '.$e->getMessage(), [
                'carga_id' => $carga->id,
            ]);
        }
    }
}
