<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = '/home/efgttvis/efgtrack_stg/storage/framework/maintenance.php')) {
    require $maintenance;
}

require '/home/efgttvis/efgtrack_stg/vendor/autoload.php';

/** @var Application $app */
$app = require_once '/home/efgttvis/efgtrack_stg/bootstrap/app.php';

$app->handleRequest(Request::capture());
