<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Parametro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametroController extends Controller
{
    private function parametroRules(): array
    {
        return [
            'origem' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'peso_min' => 'nullable|numeric|min:0',
            'peso_max' => 'nullable|numeric|min:0',
            'distancia_max' => 'nullable|numeric|min:0',
            'distancia_min' => 'nullable|numeric|min:0',
            'custo_min' => 'nullable|numeric|min:0',
            'tipo_veiculo' => 'nullable|string|in:ZZTRUCK,ZZBITRUCK,ZZCARRETA,ZZRODOTREM',
            'intervalo_busca' => 'nullable|integer|min:10|max:3600',
            'horario_inicio' => 'nullable|date_format:H:i',
            'horario_fim' => 'nullable|date_format:H:i',
            'emails_notificacao' => 'nullable|array',
            'emails_notificacao.*' => 'email',
            'whatsapp_numeros' => 'nullable|array',
            'whatsapp_numeros.*' => 'string|max:40',
            'modo_teste' => 'sometimes|boolean',
            'cidades_origem_aceitas' => 'nullable|array',
            'cidades_origem_aceitas.*' => 'string|max:255',
            'cidades_destino_aceitas' => 'nullable|array',
            'cidades_destino_aceitas.*' => 'string|max:255',
            'regras' => 'nullable|array',
            'regras.*.aplica_a' => 'nullable|in:origem,destino',
            'regras.*.cidade' => 'nullable|string|max:255',
            'regras.*.peso_min_kg' => 'nullable|numeric|min:0',
            'regras.*.peso_max_kg' => 'nullable|numeric|min:0',
            'regras.*.valor_carga_min' => 'nullable|numeric|min:0',
            'regras.*.valor_carga_max' => 'nullable|numeric|min:0',
            'portal_usuario' => 'nullable|string|max:255',
            'portal_senha' => 'nullable|string|max:500',
        ];
    }

    public function index(Bot $bot): JsonResponse
    {
        $lista = $bot->parametros()->with(['cidadesAceitas', 'regrasCidades'])->get();

        return response()->json($lista);
    }

    public function store(Request $request, Bot $bot): JsonResponse
    {
        $validated = $request->validate($this->parametroRules());
        $validated['bot_id'] = $bot->id;
        unset($validated['cidades_origem_aceitas'], $validated['cidades_destino_aceitas'], $validated['regras']);
        $this->stripEmptyPortalSenha($validated);

        $parametro = Parametro::create($validated);
        $this->syncCidadesERegras($parametro, $request);
        $parametro->load(['cidadesAceitas', 'regrasCidades']);

        return response()->json($parametro, 201);
    }

    public function show(Parametro $parametro): JsonResponse
    {
        $parametro->load(['cidadesAceitas', 'regrasCidades']);

        return response()->json($parametro);
    }

    public function update(Request $request, Parametro $parametro): JsonResponse
    {
        $validated = $request->validate($this->parametroRules());
        unset($validated['cidades_origem_aceitas'], $validated['cidades_destino_aceitas'], $validated['regras']);
        $this->stripEmptyPortalSenha($validated);

        $parametro->update($validated);
        $this->syncCidadesERegras($parametro, $request);
        $parametro->load(['cidadesAceitas', 'regrasCidades']);

        return response()->json($parametro);
    }

    public function destroy(Parametro $parametro): JsonResponse
    {
        $parametro->delete();

        return response()->json(null, 204);
    }

    private function syncCidadesERegras(Parametro $parametro, Request $request): void
    {
        $parametro->cidadesAceitas()->delete();

        foreach ($request->input('cidades_origem_aceitas', []) as $c) {
            $c = is_string($c) ? trim($c) : '';
            if ($c === '') {
                continue;
            }
            $parametro->cidadesAceitas()->create(['tipo' => 'origem', 'cidade' => $c]);
        }

        foreach ($request->input('cidades_destino_aceitas', []) as $c) {
            $c = is_string($c) ? trim($c) : '';
            if ($c === '') {
                continue;
            }
            $parametro->cidadesAceitas()->create(['tipo' => 'destino', 'cidade' => $c]);
        }

        $parametro->regrasCidades()->delete();

        foreach ($request->input('regras', []) as $r) {
            if (! is_array($r)) {
                continue;
            }
            $cidade = isset($r['cidade']) ? trim((string) $r['cidade']) : '';
            $aplica = $r['aplica_a'] ?? null;
            if ($cidade === '' || ! in_array($aplica, ['origem', 'destino'], true)) {
                continue;
            }

            $parametro->regrasCidades()->create([
                'aplica_a' => $aplica,
                'cidade' => $cidade,
                'peso_min_kg' => $this->nullableDecimal($r['peso_min_kg'] ?? null),
                'peso_max_kg' => $this->nullableDecimal($r['peso_max_kg'] ?? null),
                'valor_carga_min' => $this->nullableDecimal($r['valor_carga_min'] ?? null),
                'valor_carga_max' => $this->nullableDecimal($r['valor_carga_max'] ?? null),
            ]);
        }
    }

    private function nullableDecimal(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? (string) $v : null;
    }

    /**
     * Senha vazia no update não sobrescreve a senha já guardada.
     */
    private function stripEmptyPortalSenha(array &$validated): void
    {
        if (! array_key_exists('portal_senha', $validated)) {
            return;
        }
        if ($validated['portal_senha'] === null || $validated['portal_senha'] === '') {
            unset($validated['portal_senha']);
        }
    }
}
