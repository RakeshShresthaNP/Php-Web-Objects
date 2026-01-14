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

// --- 3. THE DISPATCHER ---
final class DispatcherEvent
{

    private array $listeners = [];

    public function __construct()
    {}

    public function dispatch(object $event, ...$dependencies): void
    {
        $reflection = new ReflectionClass($event);
        foreach ($reflection->getMethods() as $method) {
            if (! empty($method->getAttributes(AsEventListener::class))) {
                $method->invoke($event, ...$dependencies);
            }
        }
    }
}
