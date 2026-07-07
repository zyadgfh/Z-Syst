<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_pharmasync_docs_page_renders_successfully(): void
    {
        $response = $this->get('/docs/pharmasync-feature-list');

        $response->assertStatus(200);
        $response->assertSee('PharmaSync Product Feature List');
        $response->assertSee('Overview');
    }
}
