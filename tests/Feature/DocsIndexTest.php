<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsIndexTest extends TestCase
{
    public function test_docs_index_renders_successfully(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('Documentation');
        $response->assertSee('PharmaSync Feature List');
    }
}
