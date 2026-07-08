<?php

declare(strict_types=1);

namespace App\Utils;

class Container
{
    private array $services = [];
    private array $instances = [];

    /**
     * Register a service factory.
     */
    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Get a service by ID, lazily instantiating it.
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->services[$id])) {
            $this->instances[$id] = $this->services[$id]($this);
            return $this->instances[$id];
        }

        throw new \Exception("Service not found: " . $id);
    }
}
