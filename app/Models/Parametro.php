<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parametro extends Model
{
    protected $table = 'parametros';

    protected $fillable = [
        'bot_id',
        'origem',
        'destino',
        'peso_min',
        'distancia_max',
        'distancia_min',
        'custo_min',
        'tipo_veiculo',
        'intervalo_busca',
        'horario_inicio',
        'horario_fim',
    ];

    protected function casts(): array
    {
        return [
            'peso_min' => 'decimal:2',
            'distancia_max' => 'decimal:2',
            'distancia_min' => 'decimal:2',
            'custo_min' => 'decimal:2',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
