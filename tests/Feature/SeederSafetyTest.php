<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    public function test_destructive_database_seeder_refuses_production_before_touching_database(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->expectException(\RuntimeException::class);
            (new DatabaseSeeder)->run();
        } finally {
            $this->app['env'] = $original;
        }
    }

    public function test_demo_users_cannot_be_seeded_in_production(): void
    {
        $original = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->expectException(\RuntimeException::class);
            (new UserSeeder)->run();
        } finally {
            $this->app['env'] = $original;
        }
    }
}
