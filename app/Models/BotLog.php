<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotLog extends Model
{
    protected $table = 'bot_logs';

    protected $fillable = [
        'bot_id',
        'nivel',
        'evento',
        'mensagem',
        'contexto',
    ];

    protected function casts(): array
    {
        return [
            'contexto' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public static function registrar(?int $botId, string $nivel, string $evento, ?string $mensagem = null, array $contexto = []): self
    {
        return self::create([
            'bot_id' => $botId,
            'nivel' => $nivel,
            'evento' => $evento,
            'mensagem' => $mensagem,
            'contexto' => $contexto,
        ]);
    }
}
