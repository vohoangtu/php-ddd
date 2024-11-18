<?php

namespace App\Notification\Domain;

class EmailJob
{
    private string $recipientEmail;
    private string $recipientName;
    private string $subject;
    private string $template;
    private array $data;
    private int $priority;
    private ?\DateTime $scheduledFor;

    public function __construct(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $template,
        array $data = [],
        int $priority = 1,
        ?\DateTime $scheduledFor = null
    ) {
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->subject = $subject;
        $this->template = $template;
        $this->data = $data;
        $this->priority = $priority;
        $this->scheduledFor = $scheduledFor;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getScheduledFor(): ?\DateTime
    {
        return $this->scheduledFor;
    }
} 