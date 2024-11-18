<?php

namespace App\Shared\Infrastructure\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'];
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'];
            $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $this->mailer->Port = $_ENV['MAIL_PORT'];
            $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            throw new \RuntimeException("Email configuration error: {$e->getMessage()}");
        }
    }

    public function send(string $to, string $subject, string $template, array $data = []): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->renderTemplate($template, $data);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            // Log error
            error_log("Failed to send email: {$e->getMessage()}");
            return false;
        }
    }

    private function renderTemplate(string $template, array $data): string
    {
        $blade = app()->get('blade');
        return $blade->make("emails.$template", $data)->render();
    }
} 