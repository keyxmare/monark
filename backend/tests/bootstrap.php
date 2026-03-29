<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require \dirname(__DIR__) . '/vendor/autoload.php';

// Force test environment regardless of container's APP_ENV
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';

// Override DATABASE_URL to use the test database — the container env var
// points to the dev DB and Dotenv cannot override real env vars.
$_SERVER['DATABASE_URL'] = 'postgresql://app:changeme@database:5432/monark_test?serverVersion=17&charset=utf8';
$_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];
\putenv('DATABASE_URL=' . $_SERVER['DATABASE_URL']);

if (\method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');
}
