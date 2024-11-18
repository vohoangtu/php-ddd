<?php

namespace App\Shared\Infrastructure\Validation;

use App\Shared\Domain\Validation\ValidationInterface;

class Validator implements ValidationInterface
{
    private array $rules = [];
    private array $messages = [];

    public function __construct()
    {
        $this->registerDefaultRules();
    }

    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) 
                ? explode('|', $fieldRules) 
                : $fieldRules;

            foreach ($fieldRules as $rule) {
                $ruleName = is_string($rule) ? explode(':', $rule)[0] : $rule;
                $parameters = is_string($rule) 
                    ? array_slice(explode(':', $rule), 1) 
                    : [];

                if (!$this->validateField(
                    $data[$field] ?? null,
                    $ruleName,
                    $parameters,
                    $data
                )) {
                    $errors[$field][] = $this->formatMessage(
                        $field,
                        $ruleName,
                        $parameters
                    );
                }
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    public function addRule(string $name, callable $rule): void
    {
        $this->rules[$name] = $rule;
    }

    private function registerDefaultRules(): void
    {
        $this->rules = [
            'required' => fn($value) => $value !== null && $value !== '',
            'email' => fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL),
            'min' => fn($value, $min) => strlen($value) >= $min,
            'max' => fn($value, $max) => strlen($value) <= $max,
            'numeric' => fn($value) => is_numeric($value),
            'integer' => fn($value) => filter_var($value, FILTER_VALIDATE_INT),
            'array' => fn($value) => is_array($value),
            'url' => fn($value) => filter_var($value, FILTER_VALIDATE_URL),
            'date' => fn($value) => strtotime($value) !== false,
            'in' => fn($value, $list) => in_array($value, explode(',', $list)),
            'unique' => fn($value, $table, $field, $except = null) => 
                $this->checkUnique($value, $table, $field, $except),
        ];
    }

    private function validateField($value, $rule, array $parameters, array $data): bool
    {
        if (!isset($this->rules[$rule])) {
            throw new \InvalidArgumentException("Unknown validation rule: $rule");
        }

        return $this->rules[$rule]($value, ...$parameters, $data);
    }

    private function formatMessage(string $field, string $rule, array $parameters): string
    {
        $message = $this->messages["$field.$rule"] 
            ?? $this->messages[$rule] 
            ?? "The $field field is invalid";

        return str_replace(
            [':field', ':parameters'],
            [$field, implode(', ', $parameters)],
            $message
        );
    }
} 