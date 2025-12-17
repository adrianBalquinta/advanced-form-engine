<?php
declare(strict_types=1);

namespace AFE\Core;

class EventDispatcher
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @param string $eventName
     * @param object $event
     */
    public function dispatch(string $eventName, object $event): void
    {
        if (empty($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }
}
