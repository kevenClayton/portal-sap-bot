<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken() ?? $request->header('X-Worker-Token');
        $token = is_string($raw) ? trim($raw) : '';
        $expected = trim((string) config('services.worker.token'));

        if ($expected === '') {
            return response()->json([
                'message' => 'WORKER_API_TOKEN não está definido no .env do Laravel (raiz do projeto). Defina o mesmo valor que no worker/.env e execute: php artisan config:clear',
            ], 503);
        }

        if ($token === '' || ! hash_equals($expected, $token)) {
            return response()->json([
                'message' => 'Token do worker inválido. O WORKER_API_TOKEN no .env do Laravel tem de ser exactamente igual ao WORKER_API_TOKEN no worker/.env. Depois de alterar: php artisan config:clear',
            ], 401);
        }

        return $next($request);
    }
}
