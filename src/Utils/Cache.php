<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Cache Utility
 * Provides safe, JSON-serialized file caching with atomic locks.
 * Replaces dangerous serialize/unserialize to eliminate PHP Object Injection risks.
 */
class Cache
{
    private static string $cacheDir = __DIR__ . '/../../storage/cache';

    /**
     * Ensure cache directory exists and is writable.
     */
    private static function ensureDirectory(): void
    {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Get path for a cache key.
     */
    private static function getFilePath(string $key): string
    {
        self::ensureDirectory();
        return self::$cacheDir . '/' . hash('sha256', $key) . '.cache';
    }

    /**
     * Get an item from the cache, or execute callback and cache the result.
     *
     * @param string   $key
     * @param int      $ttlSeconds
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttlSeconds);
        return $value;
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
                @unlink($file);
                return $default;
            }

            if (time() >= $data['expires_at']) {
                @unlink($file);
                return $default;
            }

            return $data['value'];
        } catch (\Throwable) {
            @unlink($file);
            return $default;
        }
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttlSeconds
     * @return bool
     */
    public static function set(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        $file = self::getFilePath($key);
        $data = [
            'expires_at' => time() + $ttlSeconds,
            'value'      => $value,
        ];

        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            return (file_put_contents($file, $encoded, LOCK_EX) !== false);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Clear an item from the cache.
     */
    public static function forget(string $key): void
    {
        $file = self::getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Flush all items in the cache directory.
     */
    public static function flush(): void
    {
        self::ensureDirectory();
        $files = glob(self::$cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
