<?php

declare(strict_types=1);

namespace Dna\Snps;

/**
 * Abstract Singleton class
 */
abstract class Singleton
{
    private static array $instances = [];

    /**
     * Protected constructor to prevent direct instantiation
     */
    protected function __construct() {}

    /**
     * Prevent cloning of the instance
     */
    protected function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): static
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }

        return self::$instances[$class];
    }
}