<?php

namespace Tests\Fixtures;

use App\Shared\Testing\FixtureInterface;
use App\Shared\Infrastructure\Database\DatabaseInterface;

class ProductFixture implements FixtureInterface
{
    public function load(DatabaseInterface $db): void
    {
        $db->execute("
            INSERT INTO products (name, price, description)
            VALUES
                ('Product 1', 10.99, 'Description 1'),
                ('Product 2', 20.99, 'Description 2')
        ");
    }

    public function clear(DatabaseInterface $db): void
    {
        $db->execute("DELETE FROM products");
    }
} 