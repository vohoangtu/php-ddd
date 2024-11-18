<?php
namespace App\Shared\Domain\Validation;

interface ValidationInterface
{
    public function validate(array $data, array $rules): ValidationResult;
    public function addRule(string $name, callable $rule): void;
} 