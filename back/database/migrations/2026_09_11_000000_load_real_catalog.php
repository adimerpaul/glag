<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Carga el catálogo real del negocio (categorías + productos) desde
 * database/data/catalogo.json y saca de en medio el catálogo de prueba GLAG-####.
 *
 * Es idempotente y no destructiva: hace upsert por `codigo`, nunca borra productos
 * reales, y a los que ya existen NO les toca `stock_inicial` — ese valor lo mueven
 * las ventas, compras, bajas y almacenes, y pisarlo desincronizaría el inventario.
 *
 * Las fotos son rutas relativas a back/public/images/. Los archivos se despliegan
 * con el repo (public/images no está en .gitignore); si alguno falta, el producto
 * simplemente se muestra sin imagen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ruta = database_path('data/catalogo.json');
        if (! is_file($ruta)) {
            return;
        }

        $catalogo = json_decode(file_get_contents($ruta), true);
        if (! is_array($catalogo) || empty($catalogo['productos'])) {
            return;
        }

        DB::transaction(function () use ($catalogo) {
            $this->removeTestCatalog();

            $categorias = $this->syncCategorias($catalogo['categorias'] ?? []);

            foreach ($catalogo['productos'] as $producto) {
                $this->syncProducto($producto, $categorias);
            }
        });
    }

    /**
     * El catálogo de prueba sólo sirve para los tests. Sus productos nunca tuvieron
     * movimientos, así que se pueden borrar; las categorías, sólo si quedan vacías.
     */
    private function removeTestCatalog(): void
    {
        $ids = DB::table('productos')->where('codigo', 'like', 'GLAG-%')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('lotes')->whereIn('producto_id', $ids)->delete();
        DB::table('productos')->whereIn('id', $ids)->delete();

        DB::table('categorias')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('productos')
                ->whereColumn('productos.categoria_id', 'categorias.id'))
            ->delete();
    }

    /** @return array<string,int> nombre de categoría => id */
    private function syncCategorias(array $categorias): array
    {
        $mapa = [];

        foreach ($categorias as $categoria) {
            $nombre = trim((string) ($categoria['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $existente = DB::table('categorias')->where('nombre', $nombre)->first();
            if ($existente) {
                $mapa[$nombre] = $existente->id;

                continue;
            }

            $mapa[$nombre] = DB::table('categorias')->insertGetId([
                'nombre' => $nombre,
                'color' => $categoria['color'] ?: 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $mapa;
    }

    /** @param  array<string,int>  $categorias */
    private function syncProducto(array $producto, array $categorias): void
    {
        $codigo = trim((string) ($producto['codigo'] ?? ''));
        if ($codigo === '') {
            return;
        }

        $nombreCategoria = trim((string) ($producto['categoria'] ?? ''));

        $campos = [
            'codigo_barras' => $producto['codigo_barras'] ?: null,
            'nombre' => $producto['nombre'],
            'categoria' => $nombreCategoria ?: null,
            'categoria_id' => $categorias[$nombreCategoria] ?? null,
            'unidad' => $producto['unidad'] ?: 'UNIDAD',
            'precio_compra' => $producto['precio_compra'] ?? 0,
            'precio_venta' => $producto['precio_venta'] ?? 0,
            'foto' => $producto['foto'] ?: null,
            'updated_at' => now(),
        ];

        $existente = DB::table('productos')->where('codigo', $codigo)->first();

        if ($existente) {
            // Sin stock_inicial: en una base con movimientos ese valor ya no es el del archivo.
            DB::table('productos')->where('id', $existente->id)->update($campos);

            return;
        }

        DB::table('productos')->insert($campos + [
            'codigo' => $codigo,
            'stock_inicial' => $producto['stock_inicial'] ?? 0,
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        $ruta = database_path('data/catalogo.json');
        if (! is_file($ruta)) {
            return;
        }

        $catalogo = json_decode(file_get_contents($ruta), true);
        $codigos = array_column($catalogo['productos'] ?? [], 'codigo');

        DB::table('productos')->whereIn('codigo', $codigos)->delete();
    }
};
