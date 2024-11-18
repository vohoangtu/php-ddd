<?php
namespace App\Notification\Infrastructure\Queue;

use Illuminate\Database\Capsule\Manager as DB;
use App\Notification\Domain\EmailJob;
use App\Shared\Infrastructure\Error\ErrorHandler;

class EmailQueue
{
    private ErrorHandler $errorHandler;

    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function push(EmailJob $job): void
    {
        try {
            DB::table('email_queue')->insert([
                'recipient_email' => $job->getRecipientEmail(),
                'recipient_name' => $job->getRecipientName(),
                'subject' => $job->getSubject(),
                'template' => $job->getTemplate(),
                'data' => json_encode($job->getData()),
                'priority' => $job->getPriority(),
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
                'scheduled_for' => $job->getScheduledFor() ?? now()
            ]);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Failed to queue email', [
                'error' => $e->getMessage(),
                'recipient' => $job->getRecipientEmail(),
                'subject' => $job->getSubject()
            ]);
            throw $e;
        }
    }

    public function processQueue(int $limit = 50): void
    {
        try {
            $jobs = DB::table('email_queue')
                ->where('status', 'pending')
                ->where('scheduled_for', '<=', now())
                ->where('attempts', '<', 3)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            foreach ($jobs as $job) {
                $this->processJob($job);
            }
        } catch (\Exception $e) {
            $this->errorHandler->logError('Failed to process email queue', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processJob(object $job): void
    {
        try {
            // Update job status to processing
            DB::table('email_queue')
                ->where('id', $job->id)
                ->update([
                    'status' => 'processing',
                    'attempts' => DB::raw('attempts + 1'),
                    'last_attempt' => now()
                ]);

            // Send email
            $emailService = new \App\Notification\Application\EmailNotificationService();
            $emailService->send(
                $job->recipient_email,
                $job->recipient_name,
                $job->subject,
                $job->template,
                json_decode($job->data, true)
            );

            // Mark as completed
            DB::table('email_queue')
                ->where('id', $job->id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

            $this->errorHandler->logInfo('Email sent successfully', [
                'job_id' => $job->id,
                'recipient' => $job->recipient_email
            ]);
        } catch (\Exception $e) {
            $this->handleFailedJob($job, $e);
        }
    }

    private function handleFailedJob(object $job, \Exception $e): void
    {
        $status = $job->attempts >= 2 ? 'failed' : 'pending';
        
        DB::table('email_queue')
            ->where('id', $job->id)
            ->update([
                'status' => $status,
                'error' => $e->getMessage(),
                'updated_at' => now()
            ]);

        $this->errorHandler->logError('Failed to send email', [
            'job_id' => $job->id,
            'recipient' => $job->recipient_email,
            'attempts' => $job->attempts + 1,
            'error' => $e->getMessage()
        ]);
    }

    public function retryFailedJobs(): void
    {
        try {
            DB::table('email_queue')
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'attempts' => 0,
                    'error' => null,
                    'updated_at' => now()
                ]);
        } catch (\Exception $e) {
            $this->errorHandler->logError('Failed to retry failed jobs', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cleanupOldJobs(int $days = 30): void
    {
        try {
            DB::table('email_queue')
                ->where('status', 'completed')
                ->where('completed_at', '<', now()->subDays($days))
                ->delete();
        } catch (\Exception $e) {
            $this->errorHandler->logError('Failed to cleanup old jobs', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 