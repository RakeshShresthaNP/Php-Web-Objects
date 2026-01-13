<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

// --- 1. THE ATTRIBUTE ---
#[Attribute(Attribute::TARGET_METHOD)]
final class AsEventListener
{

    public function __construct(public string $eventClass, public int $priority = 0)
    {}
}

// --- 2. THE BASE STOPPABLE EVENT ---
abstract class StoppableEvent
{

    private bool $stopped = false;

    public function stop()
    {
        $this->stopped = true;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}

// --- 3. THE DISPATCHER ---
final class EventDispatcher
{

    private array $listeners = [];

    public function register(object $subscriber)
    {
        $reflection = new ReflectionClass($subscriber);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attr) {
                $instance = $attr->newInstance();
                $this->listeners[$instance->eventClass][] = [
                    'callback' => [
                        $subscriber,
                        $method->getName()
                    ],
                    'priority' => $instance->priority
                ];
                // Sort by priority (Higher first)
                usort($this->listeners[$instance->eventClass], fn ($a, $b) => $b['priority'] <=> $a['priority']);
            }
        }
    }

    public function dispatch(object $event)
    {
        foreach ($this->listeners[get_class($event)] ?? [] as $listener) {
            if ($event instanceof StoppableEvent && $event->isStopped())
                break;
            $listener['callback']($event);
        }
    }

    public function dispatchauto(object $event, ...$dependencies): void
    {
        $reflection = new ReflectionClass($event);

        foreach ($reflection->getMethods() as $method) {
            // Check if the method has the AsEventListener attribute
            if (! empty($method->getAttributes(AsEventListener::class))) {
                // Call the method on the event object itself
                $method->invoke($event, ...$dependencies);
            }
        }
    }
}
