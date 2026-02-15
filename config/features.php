<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags - NuweFarma
    |--------------------------------------------------------------------------
    |
    | Configuración de feature flags para controlar funcionalidades experimentales
    | y de inteligencia artificial en la aplicación.
    |
    */

    'ai' => [
        /*
        |--------------------------------------------------------------------------
        | Chatbot de ventas
        |--------------------------------------------------------------------------
        | Habilita el asistente de IA para ayuda en ventas y recomendaciones.
        */
        'chatbot' => env('FEATURE_AI_CHATBOT', false),

        /*
        |--------------------------------------------------------------------------
        | Predicción de inventario
        |--------------------------------------------------------------------------
        | Habilita análisis predictivo para sugerir reorder points.
        */
        'inventory_prediction' => env('FEATURE_AI_INVENTORY_PREDICTION', false),

        /*
        |--------------------------------------------------------------------------
        | Recomendaciones de productos
        |--------------------------------------------------------------------------
        | Habilita recomendaciones personalizadas basadas en historial de compras.
        */
        'product_recommendations' => env('FEATURE_AI_PRODUCT_RECOMMENDATIONS', false),

        /*
        |--------------------------------------------------------------------------
        | Análisis de ventas
        |--------------------------------------------------------------------------
        | Habilita análisis automatizado de patrones de venta.
        */
        'sales_analytics' => env('FEATURE_AI_SALES_ANALYTICS', false),

        /*
        |--------------------------------------------------------------------------
        | Detección de fraude
        |--------------------------------------------------------------------------
        | Habilita detección de patrones sospechosos en transacciones.
        */
        'fraud_detection' => env('FEATURE_AI_FRAUD_DETECTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Funcionalidades experimentales
    |--------------------------------------------------------------------------
    */

    'experimental' => [
        /*
        |--------------------------------------------------------------------------
        | Webhooks avanzados
        |--------------------------------------------------------------------------
        | Habilita sistema de webhooks con reintentos y cola.
        */
        'advanced_webhooks' => env('FEATURE_ADVANCED_WEBHOOKS', false),

        /*
        |--------------------------------------------------------------------------
        | Dashboard en tiempo real
        |--------------------------------------------------------------------------
        | Habilita actualizaciones en tiempo real con WebSockets.
        */
        'real_time_dashboard' => env('FEATURE_REAL_TIME_DASHBOARD', false),

        /*
        |--------------------------------------------------------------------------
        | Multi-sucursal avanzado
        |--------------------------------------------------------------------------
        | Habilita transferencias entre sucursales y reportes consolidados.
        */
        'multi_branch_advanced' => env('FEATURE_MULTI_BRANCH_ADVANCED', false),

        /*
        |--------------------------------------------------------------------------
        | Punto de venta offline
        |--------------------------------------------------------------------------
        | Habilita modo offline con sincronización posterior.
        */
        'pos_offline' => env('FEATURE_POS_OFFLINE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Funciones utilitarias
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Verificar si una feature está habilitada
    |--------------------------------------------------------------------------
    */
    'enabled' => function (string $category, string $feature): bool {
        $config = config("features.{$category}.{$feature}", false);

        if (is_bool($config)) {
            return $config;
        }

        return filter_var($config, FILTER_VALIDATE_BOOLEAN);
    },
];
