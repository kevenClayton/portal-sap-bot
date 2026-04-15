<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Execucao extends Model
{
    protected $table = 'execucoes';

    protected $fillable = [
        'bot_id',
        'cargas_analisadas',
        'cargas_capturadas',
        'cargas_ignoradas',
        'cargas_simuladas',
        'inicio_execucao',
        'fim_execucao',
        'status',
        'mensagem_erro',
    ];

    protected function casts(): array
    {
        return [
            'inicio_execucao' => 'datetime',
            'fim_execucao' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
