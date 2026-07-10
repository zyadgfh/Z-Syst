<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_z_syst_docs_page_renders_successfully(): void
    {
        $response = $this->get('/docs/z-syst-feature-list');

        $response->assertStatus(200);
        $response->assertSee('Z-Syst Product Feature List');
        $response->assertSee('Overview');
    }
}
