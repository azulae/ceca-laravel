<?php
return [
    'url_ok'                        => env('CECA_URL_OK', ''),
    'url_ko'                        => env('CECA_URL_KO', ''),
    'codigo_comercio'               => env('CECA_CODIGO_COMERCIO', ''),
    'acquirer_bin'                  => env('CECA_ACQUIRER_BIN', ''),
    'nombre_comercio'               => env('CECA_NOMBRE_COMERCIO', ''),
    'terminal'       		        => env('CECA_TERMINAL', ''),
    'terminal_desarrollo'       	=> env('CECA_TERMINAL_DESARROLLO', ''),
    'clave_encriptacion'            => env('CECA_CLAVE_ENCRIPTACION', ''),
    'clave_encriptacion_desarrollo' => env('CECA_CLAVE_ENCRIPTACION_DESARROLLO', ''),
    'modo'                          => env('CECA_MODO', 'desarrollo'), // produccion / desarrollo
];