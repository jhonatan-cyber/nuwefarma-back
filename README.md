## NuweFarma Backend API

Backend-only Laravel application. All frontend assets and Vite tooling were removed; use it as an API or service.

### Requisitos
- PHP 8.2+
- Composer
- Base de datos configurada en `.env`

### Puesta en marcha
1. Copia el entorno: `cp .env.example .env` y ajusta credenciales de base de datos.
2. Instala dependencias: `composer install`.
3. Genera key: `php artisan key:generate`.
4. Ejecuta migraciones: `php artisan migrate`.
5. Levanta el servidor: `php artisan serve`.

### Pruebas
`php artisan test`

### Notas
- La ruta `/` responde JSON de salud básica.
- No hay build frontend ni assets en `resources/`.
