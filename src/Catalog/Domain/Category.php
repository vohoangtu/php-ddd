<?php

namespace App\Catalog\Domain;

class Category
{
    private int $id;
    private string $name;
    private ?string $description;
    private ?string $slug;
    private ?int $parentId;
    private ?string $image;
    private bool $isActive;
    private string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        int $id,
        string $name,
        ?string $description,
        ?string $slug,
        ?int $parentId,
        ?string $image,
        bool $isActive,
        string $createdAt,
        ?string $updatedAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->slug = $slug;
        $this->parentId = $parentId;
        $this->image = $image;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getSlug(): ?string { return $this->slug; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getImage(): ?string { return $this->image; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
} 