<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarImagenes extends Command
{
    protected $signature = 'glag:verificar-imagenes {--limpiar : Poner foto en NULL en los productos cuyo archivo no existe}';

    protected $description = 'Revisa que cada foto referenciada en la base exista en public/images (útil después de desplegar).';

    public function handle(): int
    {
        $productos = DB::table('productos')
            ->whereNotNull('foto')
            ->where('foto', '<>', '')
            ->get(['id', 'codigo', 'nombre', 'foto']);

        $this->line('Carpeta: <comment>'.public_path('images').'</comment>');

        if ($productos->isEmpty()) {
            $this->info('Ningún producto tiene foto asignada.');

            return self::SUCCESS;
        }

        $faltantes = $productos->reject(fn ($p) => is_file(public_path('images/'.$p->foto)));

        $this->line('Productos con foto: <info>'.$productos->count().'</info>');
        $this->line('Archivos presentes: <info>'.($productos->count() - $faltantes->count()).'</info>');

        if ($faltantes->isEmpty()) {
            $this->newLine();
            $this->info('Todas las imágenes están en su lugar.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Faltan '.$faltantes->count().' archivos:');
        $this->table(['Código', 'Producto', 'Archivo'], $faltantes->map(
            fn ($p) => [$p->codigo, mb_strimwidth($p->nombre, 0, 40, '…'), $p->foto]
        )->all());

        if ($this->option('limpiar')) {
            DB::table('productos')->whereIn('id', $faltantes->pluck('id'))->update(['foto' => null]);
            $this->info('Se puso foto = NULL en esos '.$faltantes->count().' productos.');

            return self::SUCCESS;
        }

        $this->comment('Copiá los archivos a public/images/productos/ o corré con --limpiar para quitar la referencia.');

        return self::FAILURE;
    }
}
