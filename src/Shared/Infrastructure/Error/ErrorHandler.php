<?php

namespace App\Shared\Infrastructure\Error;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Formatter\LineFormatter;

class ErrorHandler
{
    private Logger $logger;
    private bool $isDebugMode;

    public function __construct()
    {
        $this->isDebugMode = $_ENV['APP_DEBUG'] === 'true';
        $this->initializeLogger();
        $this->registerHandlers();
    }

    private function initializeLogger(): void
    {
        $this->logger = new Logger('app');

        // File handler for all logs
        $fileHandler = new StreamHandler(
            __DIR__ . '/../../../../logs/app.log',
            Logger::DEBUG
        );
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        ));
        $this->logger->pushHandler($fileHandler);

        // Error log handler for errors and above
        $errorHandler = new StreamHandler(
            __DIR__ . '/../../../../logs/error.log',
            Logger::ERROR
        );
        $errorHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        ));
        $this->logger->pushHandler($errorHandler);

        // Slack handler for critical errors
        if (isset($_ENV['SLACK_WEBHOOK_URL'])) {
            $slackHandler = new SlackHandler(
                $_ENV['SLACK_WEBHOOK_URL'],
                '#errors',
                'ErrorBot',
                true,
                null,
                Logger::CRITICAL
            );
            $this->logger->pushHandler($slackHandler);
        }
    }

    private function registerHandlers(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        switch ($errno) {
            case E_USER_ERROR:
                $this->logger->error('PHP Fatal Error', $error);
                $this->displayError($error);
                exit(1);

            case E_USER_WARNING:
                $this->logger->warning('PHP Warning', $error);
                break;

            case E_USER_NOTICE:
                $this->logger->notice('PHP Notice', $error);
                break;

            default:
                $this->logger->warning('PHP Unknown Error', $error);
                break;
        }

        return true;
    }

    public function handleException(\Throwable $exception): void
    {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        $this->logger->error('Uncaught Exception', $error);
        $this->displayError($error);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [
            E_ERROR,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE
        ])) {
            $this->logger->critical('PHP Fatal Error', $error);
            $this->displayError($error);
        }
    }

    private function displayError(array $error): void
    {
        if ($this->isDebugMode) {
            // Display detailed error for developers
            echo $this->renderDebugError($error);
        } else {
            // Display user-friendly error page
            http_response_code(500);
            include __DIR__ . '/../../../../views/errors/500.php';
        }
    }

    private function renderDebugError(array $error): string
    {
        $output = "<div style='background:#f8f9fa;padding:20px;margin:20px;border-radius:5px;'>";
        $output .= "<h2 style='color:#dc3545;'>Error Occurred</h2>";
        $output .= "<p><strong>Type:</strong> {$error['type']}</p>";
        $output .= "<p><strong>Message:</strong> {$error['message']}</p>";
        $output .= "<p><strong>File:</strong> {$error['file']}</p>";
        $output .= "<p><strong>Line:</strong> {$error['line']}</p>";
        
        if (isset($error['trace'])) {
            $output .= "<h3>Stack Trace:</h3>";
            $output .= "<pre>{$error['trace']}</pre>";
        }
        
        $output .= "</div>";
        return $output;
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }
} 