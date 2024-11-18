<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Shared\Infrastructure\Security\SSO\Server\SSOServer;
use App\Shared\Infrastructure\Security\SSO\Server\Config\ServerConfig;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize server
$server = new SSOServerBootstrap();
$server->start(); 