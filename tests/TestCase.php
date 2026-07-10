<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Override OS environment variables before Laravel boots
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_DATABASE'] = ':memory:';
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $_ENV['CACHE_STORE'] = 'array';
        $_SERVER['CACHE_STORE'] = 'array';
        putenv('CACHE_STORE=array');

        $_ENV['SESSION_DRIVER'] = 'array';
        $_SERVER['SESSION_DRIVER'] = 'array';
        putenv('SESSION_DRIVER=array');

        $_ENV['QUEUE_CONNECTION'] = 'sync';
        $_SERVER['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');

        $_ENV['BROADCAST_CONNECTION'] = 'null';
        $_SERVER['BROADCAST_CONNECTION'] = 'null';
        putenv('BROADCAST_CONNECTION=null');

        $_ENV['LOG_CHANNEL'] = 'null';
        $_SERVER['LOG_CHANNEL'] = 'null';
        putenv('LOG_CHANNEL=null');

        parent::setUp();
    }
}
