 <?php
 namespace App\Shared\Infrastructure\Logging;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SlackWebhookHandler;

class Logger implements LoggerInterface
{
    private MonologLogger $logger;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->logger = new MonologLogger('app');

        // Add daily rotating file handler
        $this->logger->pushHandler(
            new RotatingFileHandler(
                $this->config['path'] . '/app.log',
                30, // Keep logs for 30 days
                MonologLogger::DEBUG
            )
        );

        // Add error log handler
        $errorHandler = new StreamHandler(
            $this->config['path'] . '/error.log',
            MonologLogger::ERROR
        );
        $errorHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        ));
        $this->logger->pushHandler($errorHandler);

        // Add Slack handler for critical errors
        if (isset($this->config['slack_webhook_url'])) {
            $this->logger->pushHandler(
                new SlackWebhookHandler(
                    $this->config['slack_webhook_url'],
                    null,
                    null,
                    true,
                    null,
                    false,
                    true,
                    MonologLogger::CRITICAL
                )
            );
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->formatContext($context));
    }

    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $this->formatContext($context));
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $this->formatContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->formatContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->formatContext($context));
    }

    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $this->formatContext($context));
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->formatContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $this->formatContext($context));
    }

    private function formatContext(array $context): array
    {
        return array_merge($context, [
            'timestamp' => time(),
            'request_id' => $this->getRequestId(),
            'user_id' => $this->getCurrentUserId(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null
        ]);
    }

    private function getRequestId(): string
    {
        return $_SERVER['HTTP_X_REQUEST_ID'] 
            ?? uniqid('req_', true);
    }

    private function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}