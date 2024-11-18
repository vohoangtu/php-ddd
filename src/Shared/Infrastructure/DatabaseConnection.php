<?php

namespace App\Shared\Infrastructure;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseConnection
{
    public static function initialize()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $_ENV['DB_CONNECTION'],
            'host'      => $_ENV['DB_HOST'],
            'database'  => $_ENV['DB_DATABASE'],
            'username'  => $_ENV['DB_USERNAME'],
            'password'  => $_ENV['DB_PASSWORD'],
            'port'      => $_ENV['DB_PORT'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
} 