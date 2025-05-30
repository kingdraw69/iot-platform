<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Dotenv\Dotenv;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cargar explícitamente el archivo .env.testing
        if (file_exists(base_path('.env.testing'))) {
            Dotenv::createImmutable(base_path(), '.env.testing')->load();
        }

        // Forzar el uso de MySQL en las pruebas
        config()->set('database.default', 'mysql');
    }
}
