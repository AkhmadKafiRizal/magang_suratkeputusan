<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? null);

        if ($database !== 'siarka_feature_testing') {
            throw new \RuntimeException('Pengujian dibatalkan: DB_DATABASE wajib menggunakan siarka_feature_testing.');
        }

        parent::setUp();
    }
}
