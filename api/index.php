<?php

/**
 * Pintu masuk Laravel untuk Vercel (runtime vercel-php).
 * Di Vercel hanya /tmp yang bisa ditulis, jadi semua cache/log/view dialihkan ke sana.
 */

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$tmp = '/tmp';

foreach (['app/public', 'app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    if (! is_dir("$tmp/storage/$dir")) {
        @mkdir("$tmp/storage/$dir", 0755, true);
    }
}

$defaults = [
    'APP_CONFIG_CACHE' => "$tmp/config.php",
    'APP_EVENTS_CACHE' => "$tmp/events.php",
    'APP_PACKAGES_CACHE' => "$tmp/packages.php",
    'APP_ROUTES_CACHE' => "$tmp/routes.php",
    'APP_SERVICES_CACHE' => "$tmp/services.php",
    'VIEW_COMPILED_PATH' => "$tmp/storage/framework/views",
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->useStoragePath("$tmp/storage");

$app->handleRequest(Request::capture());
