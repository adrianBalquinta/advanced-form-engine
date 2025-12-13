<?php
/**
 * 
 * This container will later manage: Assets, Admin menus, Repositories, Security services, Integration clients, Event dispatcher
 * 
 * */
declare(strict_types=1);

namespace AFE\Core;

use RuntimeException;

class ServiceContainer
{
    /**
     * @var array<string, callable|object>
     */
    private array $services = [];

    /**
     * Register a service factory.
     *
     * @param string   $id      Fully qualified class name.
     * @param callable $factory Factory that returns an instance.
     */
    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Get a service instance by id (lazy-load).
     *
     * @param  string $id
     * @return object
     */
    public function get(string $id): object
    {
        if (!array_key_exists($id, $this->services)) {
            throw new RuntimeException("Service not found: {$id}");
        }

        // If it's still a factory, call it once and store the instance.
        if (is_callable($this->services[$id])) {
            $this->services[$id] = call_user_func($this->services[$id]);
        }

        return $this->services[$id];
    }
}