# GLAG

**Granja Láctea de Altura Gonzales** — sistema de ventas e inventario (Bolivia, Bs).

- `back/` — API REST Laravel 13 (PHP 8.3), Sanctum + spatie/laravel-permission.
- `front/` — SPA/PWA Quasar 2 + Vue 3 (Vite).

## Backend Laravel

```bash
cd back
composer install
php artisan migrate
php artisan serve
```

Las migraciones dejan la base lista: **611 productos en 23 categorías** (desde `back/database/data/catalogo.json`) y el usuario inicial:

- Usuario: `admin`
- Contraseña: `admin`

## Despliegue en el servidor

```bash
git pull                       # o copiar los archivos, incluido back/public/images/
cd back
composer install --no-dev --optimize-autoloader
php artisan migrate --force    # crea esquema, permisos, admin y los 611 productos
php artisan glag:verificar-imagenes   # confirma que llegaron las 42 fotos
php artisan config:cache && php artisan route:cache
```

`php artisan migrate` es seguro sobre una base que ya tiene ventas: el catálogo se
carga con upsert por `codigo` y no modifica el stock de los productos existentes.
**Nunca usar `migrate:fresh` en producción**, borra todo.

Para vaciar ventas, compras, bajas, almacenes y lotes sin perder productos ni usuarios:

```bash
php artisan glag:limpiar-movimientos          # pide confirmación
php artisan glag:limpiar-movimientos --stock  # además deja el stock en 0
```

## Frontend Quasar

```bash
cd front
npm install
npm run dev
```

La URL de la API se configura con `VITE_API_BACK` en `front/.env.development` y `front/.env.production`.
