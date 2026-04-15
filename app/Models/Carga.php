<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carga extends Model
{
    protected $fillable = [
        'bot_id',
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

    public function scopeSimuladas($query)
    {
        return $query->where('status', 'simulada');
    }

    /** Cargas efetivamente aceites: captura real no SAP ou simulação em modo teste. */
    public function scopeAceitas($query)
    {
        return $query->whereIn('status', ['capturada', 'simulada']);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('created_at', today());
    }
}
