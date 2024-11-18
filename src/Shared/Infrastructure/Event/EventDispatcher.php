<?php

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\EventInterface;
use App\Shared\Infrastructure\Error\ErrorHandler;

class EventDispatcher
{
    private array $listeners = [];
    private ErrorHandler $errorHandler;

    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
        ksort($this->listeners[$eventName]);
    }

    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getName();

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priorityListeners) {
            foreach ($priorityListeners as $listener) {
                try {
                    $listener($event);
                } catch (\Exception $e) {
                    $this->errorHandler->logError('Event listener failed', [
                        'event' => $eventName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    public function dispatchAsync(EventInterface $event): void
    {
        // Implement async dispatch using queue system
        // This is just a placeholder implementation
        $this->dispatch($event);
    }
} 