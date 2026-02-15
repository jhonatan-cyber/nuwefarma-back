<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Roles y Permisos
    |--------------------------------------------------------------------------
    |
    | Definición centralizada de roles y permisos para la aplicación.
    | Los permisos se usan en tokens de Sanctum y políticas de acceso.
    |
    */

    'roles' => [
        'Administrador' => [
            'name' => 'Administrador',
            'description' => 'Acceso completo al sistema',
            'abilities' => ['*'],
        ],
        'Gerente' => [
            'name' => 'Gerente',
            'description' => 'Gestión de usuarios, productos, categorías y ventas',
            'abilities' => [
                'users:read',
                'users:write',
                'products:*',
                'categories:*',
                'sales:*',
            ],
        ],
        'Vendedor' => [
            'name' => 'Vendedor',
            'description' => 'Gestión de ventas y consulta de productos',
            'abilities' => [
                'products:read',
                'categories:read',
                'sales:read',
                'sales:write',
            ],
        ],
        'Almacenero' => [
            'name' => 'Almacenero',
            'description' => 'Gestión de inventario y productos',
            'abilities' => [
                'products:*',
                'categories:read',
                'inventory:*',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permisos por defecto
    |--------------------------------------------------------------------------
    |
    | Permisos para roles no reconocidos.
    |
    */

    'default' => [
        'abilities' => ['profile:read'],
    ],
];
