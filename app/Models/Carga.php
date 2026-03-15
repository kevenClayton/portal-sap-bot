<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carga extends Model
{
    protected $fillable = [
        'rfq_uuid',
        'rfq_id',
        'origem',
        'destino',
        'peso',
        'distancia',
        'status',
        'dados_json',
        'tempo_resposta_ms',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
            'distancia' => 'decimal:2',
            'dados_json' => 'array',
        ];
    }

    public function scopeAnalisadas($query)
    {
        return $query->where('status', 'analisada');
    }

    public function scopeCapturadas($query)
    {
        return $query->where('status', 'capturada');
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('created_at', today());
    }
}
