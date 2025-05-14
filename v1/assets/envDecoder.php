<?php
namespace Env;

class Env
{
    protected static array $env = [];
    protected static bool $loaded = false;

    public static function load(string $file = __DIR__ . '/.env') : void
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $file));
        }

        if (!is_readable($file)) {
            throw new \RuntimeException(sprintf('%s is not readable', $file));
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Save to internal store
            self::$env[$name] = $value;

            // Save to superglobals
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load(); // default to /assets/.env
        }

        return self::$env[$key] ?? $_ENV[$key] ?? getenv($key) ?? $default;
    }
}