<?php

namespace App\Shared\Infrastructure\Documentation;

use OpenApi\Annotations as OA;
use Symfony\Component\Yaml\Yaml;

class ApiDocGenerator
{
    private string $basePath;
    private array $config;

    public function __construct(string $basePath, array $config)
    {
        $this->basePath = $basePath;
        $this->config = $config;
    }

    public function generate(): array
    {
        $swagger = \OpenApi\Generator::scan([$this->basePath]);
        
        return json_decode($swagger->toJson(), true);
    }

    public function saveAsYaml(string $path): void
    {
        $docs = $this->generate();
        file_put_contents($path, Yaml::dump($docs, 10, 2));
    }

    public function saveAsJson(string $path): void
    {
        $docs = $this->generate();
        file_put_contents($path, json_encode($docs, JSON_PRETTY_PRINT));
    }
} 