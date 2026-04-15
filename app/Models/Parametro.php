<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parametro extends Model
{
    protected $table = 'parametros';

    protected $hidden = [
        'portal_senha',
    ];

    protected $appends = [
        'portal_senha_definida',
    ];

    protected $fillable = [
        'bot_id',
        'origem',
        'destino',
        'peso_min',
        'peso_max',
        'distancia_max',
        'distancia_min',
        'custo_min',
        'tipo_veiculo',
        'intervalo_busca',
        'horario_inicio',
        'horario_fim',
        'emails_notificacao',
        'whatsapp_numeros',
        'modo_teste',
        'portal_usuario',
        'portal_senha',
    ];

    protected function casts(): array
    {
        return [
            'peso_min' => 'decimal:2',
            'peso_max' => 'decimal:2',
            'distancia_max' => 'decimal:2',
            'distancia_min' => 'decimal:2',
            'custo_min' => 'decimal:2',
            'emails_notificacao' => 'array',
            'whatsapp_numeros' => 'array',
            'modo_teste' => 'boolean',
            'portal_senha' => 'encrypted',
        ];
    }

    protected function portalSenhaDefinida(): Attribute
    {
        return Attribute::get(function (): bool {
            $raw = $this->attributes['portal_senha'] ?? null;

            return $raw !== null && $raw !== '';
        });
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function cidadesAceitas(): HasMany
    {
        return $this->hasMany(ParametroCidadeAceita::class);
    }

    public function regrasCidades(): HasMany
    {
        return $this->hasMany(ParametroRegra::class);
    }
}
