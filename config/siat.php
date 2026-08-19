<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor fiscal
    |--------------------------------------------------------------------------
    |
    | En esta etapa se usa el proveedor simulado (SimulatedSiatProvider). El
    | adaptador real (SiataRestProvider) se podrá activar cuando existan
    | credenciales, firma digital y homologación con el SIN. Nunca guardar
    | credenciales en texto plano; se cifran con la APP_KEY del proyecto.
    |
    */

    'provider' => env('SIAT_PROVIDER', 'simulated'),

    'ambiente' => env('SIAT_AMBIENTE', 'pruebas'),

    'codigo_sistema' => env('SIAT_CODIGO_SISTEMA', 'NuweFarmaPOA'),

    'cuarentena' => env('SIAT_CUARENTENA', false),

    /*
    |--------------------------------------------------------------------------
    | Credenciales de integración SIAT
    |--------------------------------------------------------------------------
    |
    | Se almacenan cifradas en SIAT_CREDENCIALES (cifrado con APP_KEY). Solo se
    | usan por el provider real cuando esté habilitado.
    |
    */

    'credentials' => env('SIAT_CREDENCIALES'),

    'timeout' => env('SIAT_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Leyendas por defecto
    |--------------------------------------------------------------------------
    |
    | Leyenda emitida por el SIAT tras validar la factura. En producción la
    | asigna el SIN con base en catálogos vigentes.
    |
    */

    'leyenda' => env('SIAT_LEYENDA', 'LEYENDA DE LA FACTURA CON DERECHO A CRÉDITO FISCAL'),

];