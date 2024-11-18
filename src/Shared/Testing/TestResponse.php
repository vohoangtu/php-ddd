<?php

namespace App\Shared\Testing;

class TestResponse
{
    private $response;
    private $decoded;

    public function __construct($response)
    {
        $this->response = $response;
        $this->decoded = json_decode($response->getContent(), true);
    }

    public function assertOk(): self
    {
        PHPUnit::assertEquals(200, $this->response->getStatusCode());
        return $this;
    }

    public function assertCreated(): self
    {
        PHPUnit::assertEquals(201, $this->response->getStatusCode());
        return $this;
    }

    public function assertUnauthorized(): self
    {
        PHPUnit::assertEquals(401, $this->response->getStatusCode());
        return $this;
    }

    public function assertForbidden(): self
    {
        PHPUnit::assertEquals(403, $this->response->getStatusCode());
        return $this;
    }

    public function assertNotFound(): self
    {
        PHPUnit::assertEquals(404, $this->response->getStatusCode());
        return $this;
    }

    public function assertJson($expected): self
    {
        PHPUnit::assertEquals($expected, $this->decoded);
        return $this;
    }

    public function assertJsonStructure(array $structure): self
    {
        $this->assertJsonStructureRecursive($structure, $this->decoded);
        return $this;
    }

    private function assertJsonStructureRecursive(array $structure, $actual): void
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertIsArray($actual);
                foreach ($actual as $actualItem) {
                    $this->assertJsonStructureRecursive($value, $actualItem);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $actual);
                $this->assertJsonStructureRecursive($value, $actual[$key]);
            } else {
                PHPUnit::assertArrayHasKey($value, $actual);
            }
        }
    }
} 