<?php

declare(strict_types=1);

namespace Application;

use Symfony\Component\Messenger\MessageBusInterface;

class EventBus
{
    public function __construct(
        private readonly MessageBusInterface $eventBus
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
