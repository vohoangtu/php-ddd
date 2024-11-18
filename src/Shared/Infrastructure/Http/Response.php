<?php

namespace App\Shared\Infrastructure\Http;

class Response
{
    private string $content;
    private int $status;
    private array $headers;

    public function __construct(
        string $content = '',
        int $status = 200,
        array $headers = []
    ) {
        $this->content = $content;
        $this->status = $status;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
    }

    public static function json($data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        
        return new static(
            json_encode($data),
            $status,
            $headers
        );
    }

    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }

    private function sendContent(): void
    {
        echo $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
} 