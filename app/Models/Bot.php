<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bot extends Model
{
    protected $fillable = [
        'status',
        'intervalo',
        'horario_inicio',
        'horario_fim',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function parametros(): HasMany
    {
        return $this->hasMany(Parametro::class, 'bot_id');
    }

    public function execucoes(): HasMany
    {
        return $this->hasMany(Execucao::class, 'bot_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BotLog::class, 'bot_id');
    }

    public function parametroAtual(): ?Parametro
    {
        return $this->parametros()->latest()->first();
    }

    public function estaAtivo(): bool
    {
        return $this->status === 'ativo';
    }
}
