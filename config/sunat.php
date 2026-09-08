<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IGV
    |--------------------------------------------------------------------------
    |
    | Tasa vigente del IGV. Estaba escrita a mano en seis controllers, con lo
    | que un cambio de tasa obligaba a tocar seis archivos y era facil que
    | quedara alguno desactualizado.
    |
    */

    'igv' => (float) env('SUNAT_IGV', 0.18),

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | 'beta' apunta al ambiente de pruebas de SUNAT. Nunca emitir contra
    | 'produccion' sin haber validado el circuito completo en beta.
    |
    */

    'ambiente' => env('SUNAT_AMBIENTE', 'beta'),

    /*
    | Las URLs no se escriben a mano: Greenter las mantiene en
    | Greenter\Ws\Services\SunatEndpoints y las cambia cuando SUNAT las cambia.
    | Una de las que estaban aca ya no era la correcta.
    */

    /*
    |--------------------------------------------------------------------------
    | Moneda por defecto
    |--------------------------------------------------------------------------
    */

    'moneda' => env('SUNAT_MONEDA', 'PEN'),

];
