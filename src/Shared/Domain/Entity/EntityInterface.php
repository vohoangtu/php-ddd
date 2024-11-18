<?php
namespace App\Shared\Domain\Entity;

interface EntityInterface
{
    public function getId();
    public function toArray(): array;
} 