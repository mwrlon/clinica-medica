<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expediente da clínica
    |--------------------------------------------------------------------------
    |
    | Horários em que o paciente pode marcar consulta (iguais aos do site:
    | segunda a sexta, 7h às 19h; sábado, 8h às 12h). Cada consulta dura
    | "intervalo_minutos"; a última começa 'intervalo' antes do fim do expediente.
    |
    | Chave = dia da semana ISO (1 = segunda ... 7 = domingo); null = fechado.
    | O médico pode fazer "encaixes" fora desta grade.
    |
    */

    'intervalo_minutos' => 30,

    'expediente' => [
        1 => ['07:00', '19:00'],
        2 => ['07:00', '19:00'],
        3 => ['07:00', '19:00'],
        4 => ['07:00', '19:00'],
        5 => ['07:00', '19:00'],
        6 => ['08:00', '12:00'],
        7 => null,
    ],

];
