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

    'endpoints' => [
        'beta' => [
            'facturacion' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpgem-beta/billService',
            'guias'       => 'https://api-cpe-beta.sunat.gob.pe/v1/contribuyente/gem',
        ],
        'produccion' => [
            'facturacion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
            'guias'       => 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moneda por defecto
    |--------------------------------------------------------------------------
    */

    'moneda' => env('SUNAT_MONEDA', 'PEN'),

];
