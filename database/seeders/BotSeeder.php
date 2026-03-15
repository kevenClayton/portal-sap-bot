<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\Parametro;
use Illuminate\Database\Seeder;

class BotSeeder extends Seeder
{
    public function run(): void
    {
        $bot = Bot::firstOrCreate(
            ['id' => 1],
            [
                'status' => 'inativo',
                'intervalo' => 60,
                'horario_inicio' => null,
                'horario_fim' => null,
            ]
        );

        if (! $bot->parametros()->exists()) {
            Parametro::create([
                'bot_id' => $bot->id,
                'origem' => null,
                'destino' => null,
                'peso_min' => null,
                'distancia_max' => null,
                'intervalo_busca' => 60,
                'horario_inicio' => null,
                'horario_fim' => null,
            ]);
        }
    }
}
