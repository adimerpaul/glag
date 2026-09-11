<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimpiarMovimientos extends Command
{
    protected $signature = 'glag:limpiar-movimientos
                            {--force : No pedir confirmación}
                            {--stock : Además dejar productos.stock_inicial en 0}
                            {--proveedores : Además borrar proveedores}
                            {--keep-audits : Conservar la tabla de auditoría}';

    protected $description = 'Vacía ventas, compras, bajas, almacenes y lotes. No toca productos, categorías, usuarios ni permisos.';

    /** Orden hijo → padre: las pivote primero, los documentos al final. */
    private const TABLAS = [
        'venta_detalle_lotes',
        'baja_detalle_lotes',
        'almacen_detalle_lotes',
        'almacen_detalle_conteos',
        'venta_detalles',
        'compra_detalles',
        'baja_detalles',
        'almacen_detalles',
        'ventas',
        'compras',
        'bajas',
        'almacenes',
        'lotes',
    ];

    public function handle(): int
    {
        $tablas = self::TABLAS;
        if (! $this->option('keep-audits')) {
            $tablas[] = 'audits';
        }
        if ($this->option('proveedores')) {
            $tablas[] = 'proveedores';
        }

        $tablas = array_values(array_filter($tablas, fn ($t) => Schema::hasTable($t)));

        $this->line('Base de datos: <comment>'.DB::connection()->getDatabaseName().'</comment>');
        $this->table(['Tabla', 'Filas'], array_map(
            fn ($t) => [$t, DB::table($t)->count()],
            $tablas
        ));

        if ($this->option('stock')) {
            $this->warn('Se pondrá stock_inicial = 0 en los '.DB::table('productos')->count().' productos.');
        }

        if (! $this->option('force') && ! $this->confirm('Esto borra los datos listados y no se puede deshacer. ¿Continuar?', false)) {
            $this->info('Cancelado. No se borró nada.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tablas as $tabla) {
                DB::table($tabla)->truncate();
                $this->line("  vaciada <info>{$tabla}</info>");
            }

            if ($this->option('stock')) {
                DB::table('productos')->update(['stock_inicial' => 0]);
                $this->line('  stock_inicial = 0 en <info>productos</info>');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->info('Movimientos limpiados. Los correlativos vuelven a V-00000001 / C-00000001.');

        if (! $this->option('stock')) {
            $this->comment('productos.stock_inicial quedó como estaba. Usá --stock para arrancar el inventario en 0 y cargarlo con un almacén.');
        }

        return self::SUCCESS;
    }
}
