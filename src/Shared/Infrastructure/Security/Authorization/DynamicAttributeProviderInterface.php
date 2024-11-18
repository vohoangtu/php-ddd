<?php

namespace App\Shared\Infrastructure\Security\Authorization;

interface DynamicAttributeProviderInterface 
{
    public function getAttributes(int $userId, array $context): array;
} 