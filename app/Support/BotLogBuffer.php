<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BotLogBuffer
{
    private const BUFFER_KEY = 'bot_logs:buffer:v1';

    private const COUNTER_KEY = 'bot_logs:counter:v1';

    public static function append(
        ?int $botId,
        string $nivel,
        string $evento,
        ?string $mensagem = null,
        array $contexto = []
    ): array {
        $agora = Carbon::now();
        $proximoId = self::nextId();
        $ttlEmMinutos = self::ttlEmMinutos();
        $maximoEntradas = self::maximoEntradas();

        $novoLog = [
            'id' => $proximoId,
            'bot_id' => $botId,
            'nivel' => $nivel,
            'evento' => $evento,
            'mensagem' => $mensagem,
            'contexto' => $contexto,
            'created_at' => $agora->toISOString(),
            'updated_at' => $agora->toISOString(),
            'bot' => $botId ? ['id' => $botId] : null,
        ];

        $linhas = self::readBuffer();
        $linhas[] = $novoLog;
        $linhasFiltradas = self::purgeExpiradas($linhas, $ttlEmMinutos);

        if (count($linhasFiltradas) > $maximoEntradas) {
            $linhasFiltradas = array_slice($linhasFiltradas, -$maximoEntradas);
        }

        self::writeBuffer($linhasFiltradas, $ttlEmMinutos);

        self::espelharErroCriticoParaLogLaravel($botId, $nivel, $evento, $mensagem, $contexto);

        return $novoLog;
    }

    /**
     * O buffer do painel não passa pelo Monolog; o Discord (MarvinLabs) só recebe o que for
     * escrito em Log::*. Erros do worker (erro_api / erro_auth) espelham-se aqui para a stack
     * configurada (ex.: single,discord).
     */
    private static function espelharErroCriticoParaLogLaravel(
        ?int $botId,
        string $nivel,
        string $evento,
        ?string $mensagem,
        array $contexto
    ): void {
        if ($nivel !== 'error') {
            return;
        }
        if (! in_array($evento, ['erro_api', 'erro_auth'], true)) {
            return;
        }
        if (! filter_var(env('WORKER_ERROR_LOG_TO_LARAVEL', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            $prefixo = $botId !== null ? "[Worker bot #{$botId}] " : '[Worker] ';
            $linha = $prefixo."[{$evento}] ".($mensagem ?? '(sem mensagem)');
            $resumo = self::contextoTruncadoParaWebhook($contexto, 1600);
            Log::error($linha, $resumo === '' ? [] : ['contexto' => $resumo]);
        } catch (\Throwable) {
            // Não impedir o registo no buffer se o canal de log falhar.
        }
    }

    /**
     * JSON compacto para Discord / Monolog (evita corpo OData gigante no webhook).
     *
     * @param  array<string, mixed>  $contexto
     */
    private static function contextoTruncadoParaWebhook(array $contexto, int $maxCaracteres): string
    {
        if ($contexto === []) {
            return '';
        }

        $json = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return '';
        }

        if (strlen($json) <= $maxCaracteres) {
            return $json;
        }

        return substr($json, 0, $maxCaracteres).'…';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function terminalInit(?int $botId, int $limite): array
    {
        $linhasFiltradas = self::filtrarPorBot(self::readBuffer(), $botId);
        $linhasFiltradas = array_slice($linhasFiltradas, -$limite);

        return array_values($linhasFiltradas);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function terminalTail(?int $botId, int $afterId, int $limite): array
    {
        $linhasFiltradas = self::filtrarPorBot(self::readBuffer(), $botId);
        $linhasFiltradas = array_filter(
            $linhasFiltradas,
            static fn (array $linha): bool => (int) ($linha['id'] ?? 0) > $afterId
        );

        if (count($linhasFiltradas) > $limite) {
            $linhasFiltradas = array_slice(array_values($linhasFiltradas), -$limite);
        }

        return array_values($linhasFiltradas);
    }

    /**
     * @return array<string, mixed>
     */
    public static function paginate(int $porPagina, int $paginaAtual, ?int $botId, ?string $nivel, ?string $evento): array
    {
        $linhasFiltradas = self::readBuffer();
        $linhasFiltradas = self::filtrarPorBot($linhasFiltradas, $botId);

        if ($nivel !== null && $nivel !== '') {
            $linhasFiltradas = array_filter(
                $linhasFiltradas,
                static fn (array $linha): bool => (string) ($linha['nivel'] ?? '') === $nivel
            );
        }

        if ($evento !== null && $evento !== '') {
            $linhasFiltradas = array_filter(
                $linhasFiltradas,
                static fn (array $linha): bool => (string) ($linha['evento'] ?? '') === $evento
            );
        }

        $linhasFiltradas = array_values(array_reverse($linhasFiltradas));
        $totalItens = count($linhasFiltradas);
        $ultimaPagina = max(1, (int) ceil($totalItens / $porPagina));
        $paginaNormalizada = max(1, min($paginaAtual, $ultimaPagina));
        $offsetInicial = ($paginaNormalizada - 1) * $porPagina;
        $itensPagina = array_slice($linhasFiltradas, $offsetInicial, $porPagina);

        return [
            'data' => array_values($itensPagina),
            'current_page' => $paginaNormalizada,
            'per_page' => $porPagina,
            'total' => $totalItens,
            'last_page' => $ultimaPagina,
        ];
    }

    private static function nextId(): int
    {
        if (! Cache::has(self::COUNTER_KEY)) {
            Cache::forever(self::COUNTER_KEY, 0);
        }

        return (int) Cache::increment(self::COUNTER_KEY);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function readBuffer(): array
    {
        $linhas = Cache::get(self::BUFFER_KEY, []);

        if (! is_array($linhas)) {
            return [];
        }

        return array_values(array_filter($linhas, static fn ($linha): bool => is_array($linha)));
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     */
    private static function writeBuffer(array $linhas, int $ttlEmMinutos): void
    {
        Cache::put(self::BUFFER_KEY, array_values($linhas), now()->addMinutes($ttlEmMinutos));
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     * @return array<int, array<string, mixed>>
     */
    private static function purgeExpiradas(array $linhas, int $ttlEmMinutos): array
    {
        $limiteMinimo = Carbon::now()->subMinutes($ttlEmMinutos);

        return array_values(array_filter(
            $linhas,
            static function (array $linha) use ($limiteMinimo): bool {
                $dataCriacao = isset($linha['created_at']) ? Carbon::parse((string) $linha['created_at']) : null;

                return $dataCriacao?->greaterThanOrEqualTo($limiteMinimo) ?? false;
            }
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $linhas
     * @return array<int, array<string, mixed>>
     */
    private static function filtrarPorBot(array $linhas, ?int $botId): array
    {
        if ($botId === null) {
            return array_values($linhas);
        }

        return array_values(array_filter(
            $linhas,
            static fn (array $linha): bool => (int) ($linha['bot_id'] ?? 0) === $botId
        ));
    }

    private static function ttlEmMinutos(): int
    {
        $ttlConfigurado = (int) env('BOT_LOG_TTL_MINUTES', 1440);

        return max(5, $ttlConfigurado);
    }

    private static function maximoEntradas(): int
    {
        $maximoConfigurado = (int) env('BOT_LOG_MAX_ENTRIES', 5000);

        return max(200, $maximoConfigurado);
    }
}

