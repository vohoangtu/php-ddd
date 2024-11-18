<?php

namespace App\Notification\Infrastructure\Console;

use App\Notification\Infrastructure\Queue\EmailQueue;
use App\Shared\Infrastructure\Error\ErrorHandler;

class ProcessEmailQueueCommand
{
    private EmailQueue $emailQueue;
    private ErrorHandler $errorHandler;

    public function __construct(EmailQueue $emailQueue, ErrorHandler $errorHandler)
    {
        $this->emailQueue = $emailQueue;
        $this->errorHandler = $errorHandler;
    }

    public function execute(): void
    {
        try {
            $this->errorHandler->logInfo('Starting email queue processing');
            
            // Process queue
            $this->emailQueue->processQueue();
            
            // Cleanup old jobs
            $this->emailQueue->cleanupOldJobs();
            
            $this->errorHandler->logInfo('Email queue processing completed');
        } catch (\Exception $e) {
            $this->errorHandler->logError('Failed to process email queue', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 