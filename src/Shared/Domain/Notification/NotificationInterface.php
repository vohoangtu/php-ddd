<?php
namespace App\Shared\Domain\Notification;

interface NotificationInterface
{
    public function getType(): string;
    public function getData(): array;
    public function getRecipient(): string;
    public function getTemplate(): string;
} 