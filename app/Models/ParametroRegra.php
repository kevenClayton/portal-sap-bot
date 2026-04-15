<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroRegra extends Model
{
    protected $table = 'parametro_regras';

    protected $fillable = [
        'parametro_id',
        'aplica_a',
        'cidade',
        'peso_min_kg',
        'peso_max_kg',
        'valor_carga_min',
        'valor_carga_max',
    ];

    protected function casts(): array
    {
        return [
            'peso_min_kg' => 'decimal:2',
            'peso_max_kg' => 'decimal:2',
            'valor_carga_min' => 'decimal:2',
            'valor_carga_max' => 'decimal:2',
        ];
    }

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }
}
