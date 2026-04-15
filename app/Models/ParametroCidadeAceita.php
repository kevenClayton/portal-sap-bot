<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroCidadeAceita extends Model
{
    protected $table = 'parametro_cidades_aceitas';

    protected $fillable = [
        'parametro_id',
        'tipo',
        'cidade',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }
}
