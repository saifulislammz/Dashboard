<?php

declare(strict_types=1);

namespace App\Utils;

class Cache
{
    private static string $cacheDir = __DIR__ . '/../../storage/cache';

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string   $key
     * @param int      $ttlSeconds
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $file = self::$cacheDir . '/' . md5($key) . '.cache';

        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if (time() < $data['expires_at']) {
                return $data['value'];
            }
            // Expired
            unlink($file);
        }

        $value = $callback();

        $data = [
            'expires_at' => time() + $ttlSeconds,
            'value'      => $value
        ];

        file_put_contents($file, serialize($data));

        return $value;
    }

    /**
     * Clear an item from the cache.
     */
    public static function forget(string $key): void
    {
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
