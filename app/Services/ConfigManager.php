<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ConfigManager
{
    protected static function getHomeDir(): string
    {
        return getenv('HOME') ?: getenv('USERPROFILE') ?: sys_get_temp_dir();
    }

    protected static function getConfigPath(): string
    {
        return self::getHomeDir() . '/.rynude/config.json';
    }

    /**
     * Get config value by key, with default value fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();
        return $config[$key] ?? $default;
    }

    /**
     * Set config value and save to file.
     */
    public static function set(string $key, mixed $value): void
    {
        $config = self::load();
        $config[$key] = $value;
        self::save($config);
    }

    /**
     * Load the config file.
     */
    public static function load(): array
    {
        $path = self::getConfigPath();
        if (!File::exists($path)) {
            return self::defaults();
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        return is_array($data) ? array_merge(self::defaults(), $data) : self::defaults();
    }

    /**
     * Save the config file.
     */
    public static function save(array $config): void
    {
        $path = self::getConfigPath();
        $dir = dirname($path);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        File::put($path, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Default configurations.
     */
    public static function defaults(): array
    {
        return [
            'default_model' => 'claude-sonnet-4-6',
            'max_iterations' => 100,
            'auto_approve' => false,
            'theme' => 'dark',
            'ignored_dirs' => ['.git', 'node_modules', 'vendor', 'storage', 'bootstrap/cache'],
        ];
    }
}
