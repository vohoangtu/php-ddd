<?php
namespace App\Shared\Domain\Event;

interface EventInterface
{
    public function getName(): string;
    public function getPayload(): array;
    public function getTimestamp(): int;
} 