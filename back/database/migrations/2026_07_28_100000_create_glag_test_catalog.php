<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = [
            'LECHES' => ['Leche entera', 'Leche descremada', 'Leche semidescremada', 'Leche deslactosada', 'Leche chocolatada', 'Leche saborizada frutilla', 'Leche saborizada vainilla', 'Leche en polvo entera', 'Leche en polvo descremada', 'Leche condensada'],
            'YOGURES' => ['Yogur natural', 'Yogur frutilla', 'Yogur durazno', 'Yogur vainilla', 'Yogur griego', 'Yogur bebible frutilla', 'Yogur bebible mora', 'Yogur con cereal', 'Yogur descremado', 'Yogur probiótico'],
            'QUESOS FRESCOS' => ['Queso criollo', 'Queso fresco', 'Quesillo', 'Queso ricota', 'Queso mozzarella', 'Queso cottage', 'Queso untable natural', 'Queso untable con hierbas', 'Queso chaqueño', 'Queso menonita'],
            'QUESOS MADUROS' => ['Queso gouda', 'Queso cheddar', 'Queso parmesano', 'Queso provolone', 'Queso dambo', 'Queso edam', 'Queso ahumado', 'Queso semiduro', 'Queso azul', 'Queso emmental'],
            'MANTEQUILLAS Y CREMAS' => ['Mantequilla con sal', 'Mantequilla sin sal', 'Mantequilla en barra', 'Manteca clarificada', 'Crema de leche', 'Crema chantilly', 'Crema doble', 'Crema ácida', 'Manteca batida', 'Margarina láctea'],
            'DERIVADOS Y POSTRES' => ['Dulce de leche', 'Manjar blanco', 'Arequipe', 'Flan de vainilla', 'Postre de chocolate', 'Gelatina de leche', 'Api con leche', 'Helado de crema', 'Helado de yogur', 'Cuajada'],
            'HUEVOS' => ['Huevo blanco mediano', 'Huevo blanco grande', 'Huevo rojo mediano', 'Huevo rojo grande', 'Huevo extra grande', 'Huevo de codorniz', 'Huevo orgánico', 'Maple de huevos', 'Clara pasteurizada', 'Yema pasteurizada'],
            'FORRAJES' => ['Alfalfa en fardo', 'Maíz molido', 'Afrecho de trigo', 'Sal mineral', 'Concentrado lechero', 'Silo de maíz', 'Avena forrajera', 'Cebada forrajera', 'Melaza', 'Núcleo vitamínico'],
            'INSUMOS DE QUESERÍA' => ['Cuajo líquido', 'Fermento láctico', 'Sal para quesería', 'Cloruro de calcio', 'Bolsa de envase 1 L', 'Botella PET 1 L', 'Tapa rosca', 'Etiqueta adhesiva', 'Film plástico', 'Bidón de 20 L'],
            'COMBOS' => ['Combo desayuno', 'Combo lácteo familiar', 'Combo quesos', 'Combo yogures', 'Combo mantequillas', 'Combo escolar', 'Combo semanal', 'Combo económico', 'Combo premium', 'Combo empresarial'],
        ];
        $colors = ['blue-9', 'light-blue-7', 'teal-7', 'green-8', 'light-green-7', 'cyan-8', 'indigo-7', 'brown-6', 'blue-grey-7', 'primary'];
        $weightedCategories = ['QUESOS FRESCOS', 'QUESOS MADUROS', 'MANTEQUILLAS Y CREMAS', 'FORRAJES'];

        DB::transaction(function () use ($catalog, $colors, $weightedCategories) {
            DB::table('productos')->delete();
            DB::table('categorias')->delete();
            $sequence = 1;

            foreach ($catalog as $categoryIndex => $items) {
                $categoryId = DB::table('categorias')->insertGetId([
                    'nombre' => $categoryIndex,
                    'color' => $colors[array_search($categoryIndex, array_keys($catalog), true)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($items as $itemIndex => $name) {
                    $weighted = in_array($categoryIndex, $weightedCategories, true);
                    $unit = $weighted ? 'KG' : ($categoryIndex === 'LECHES' ? 'LTS' : 'PZS');
                    $purchasePrice = round(8 + fmod($sequence * 1.13, 42), 2);
                    DB::table('productos')->insert([
                        'codigo' => sprintf('GLAG-%04d', $sequence),
                        'codigo_barras' => sprintf('780200%06d', $sequence),
                        'nombre' => mb_strtoupper($name),
                        'categoria' => $categoryIndex,
                        'categoria_id' => $categoryId,
                        'unidad' => $unit,
                        'precio_compra' => $purchasePrice,
                        'precio_venta' => round($purchasePrice * 1.32, 2),
                        'stock_inicial' => $weighted ? (15000 + $itemIndex * 2750) : (15 + $itemIndex * 4),
                        'foto' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $sequence++;
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('productos')->where('codigo', 'like', 'GLAG-%')->delete();
    }
};
