<?php

namespace Tests\Feature;

use Tests\TestCase;

final class Cie10CatalogViewTest extends TestCase
{
    public function test_catalog_view_exposes_crud_and_controlled_import_workflows(): void
    {
        $html = view('egresos.cie10', [
            'centralUser' => [
                'name' => 'Administrador',
                'email' => 'admin@example.test',
            ],
        ])->render();

        self::assertStringContainsString('id="catalog-form"', $html);
        self::assertStringContainsString('id="import-form"', $html);
        self::assertStringContainsString('id="import-analysis"', $html);
        self::assertStringContainsString('window.CIE10_CONFIG', $html);
        self::assertStringContainsString('El análisis no modifica el catálogo', $html);
        self::assertStringContainsString('Seguirá disponible en el historial clínico', file_get_contents(
            public_path('assets/js/egresos-cie10.js')
        ));
    }
}
