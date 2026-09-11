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

La migración instala 100 productos de prueba en 10 categorías (`GLAG-####`). Usuario inicial:

- Usuario: `admin`
- Contraseña: `admin`

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
