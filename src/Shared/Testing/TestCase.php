<?php

namespace App\Shared\Testing;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Shared\Infrastructure\Container\Container;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;
    protected array $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = require __DIR__ . '/../../../bootstrap/app.php';
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        $this->clearFixtures();
        parent::tearDown();
    }

    protected function loadFixtures(): void
    {
        foreach ($this->fixtures as $fixture) {
            (new $fixture())->load($this->container->get('db'));
        }
    }

    protected function clearFixtures(): void
    {
        foreach (array_reverse($this->fixtures) as $fixture) {
            (new $fixture())->clear($this->container->get('db'));
        }
    }

    protected function actingAs($user): self
    {
        $_SESSION['user_id'] = $user->getId();
        return $this;
    }

    protected function json(string $method, string $uri, array $data = []): TestResponse
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        
        return new TestResponse(
            $this->container->get('kernel')->handle(
                new Request($data)
            )
        );
    }
} 