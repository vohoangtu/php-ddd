<?php

namespace App\Shared\Infrastructure\Notification\Channel;

use App\Shared\Domain\Notification\NotificationInterface;
use App\Notification\Infrastructure\Queue\EmailQueue;
use App\Notification\Domain\EmailJob;

class EmailChannel implements NotificationChannelInterface
{
    private EmailQueue $emailQueue;
    
    public function __construct(EmailQueue $emailQueue)
    {
        $this->emailQueue = $emailQueue;
    }

    public function send(NotificationInterface $notification): void
    {
        $job = new EmailJob(
            $notification->getRecipient(),
            $notification->getData()['subject'] ?? '',
            $notification->getTemplate(),
            $notification->getData()
        );

        $this->emailQueue->push($job);
    }
} 