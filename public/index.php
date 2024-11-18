<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Shared\Infrastructure\Http\Request;
use App\Shared\Infrastructure\Kernel;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Bootstrap container
$container = require __DIR__ . '/../src/bootstrap.php';

// Create and handle request
$request = Request::createFromGlobals();
$kernel = new Kernel($container);
$response = $kernel->handle($request);
$response->send(); 