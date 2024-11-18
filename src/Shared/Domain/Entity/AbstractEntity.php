<?php

namespace App\Shared\Domain\Entity;

abstract class AbstractEntity implements EntityInterface
{
    protected $id;

    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    protected function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
} 