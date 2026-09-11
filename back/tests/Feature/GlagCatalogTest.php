<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlagCatalogTest extends TestCase
{
    use RefreshDatabase;

    /** El catálogo real vive en database/data/catalogo.json y lo carga una migración. */
    public function test_migrations_load_the_real_catalog(): void
    {
        $catalogo = json_decode(file_get_contents(database_path('data/catalogo.json')), true);

        $this->assertSame(count($catalogo['productos']), Producto::count());
        $this->assertSame(count($catalogo['categorias']), Categoria::count());

        // El catálogo de prueba GLAG-#### queda fuera: lo reemplaza el real.
        $this->assertSame(0, Producto::where('codigo', 'like', 'GLAG-%')->count());
    }

    public function test_every_product_has_a_category_and_a_unique_code(): void
    {
        $this->assertSame(
            Producto::count(),
            Producto::distinct()->count('codigo'),
            'Hay códigos de producto repetidos'
        );

        $this->assertSame(
            0,
            Producto::whereNull('categoria_id')->whereNotNull('categoria')->count(),
            'Hay productos con categoría sin resolver'
        );
    }

    public function test_admin_can_login_and_receive_a_sanctum_token(): void
    {
        $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user', 'must_change_password'])
            ->assertJsonPath('user.username', 'admin');
    }
}
