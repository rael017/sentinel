<?php
namespace Horus\Sentinel;

use Predis\Client as RedisClient;

class SessionManager
{
    private static ?RedisClient $redis = null;
    private static bool $isStarted = false;

    public static function start(): void
    {
        if (self::$isStarted) return;
        
        self::$redis = new RedisClient(['host' => getenv('REDIS_HOST') ?: '127.0.0.1', 'port' => getenv('REDIS_PORT') ?: 6379]);
        
        session_set_save_handler(
            [self::class, 'open'], [self::class, 'close'], [self::class, 'read'],
            [self::class, 'write'], [self::class, 'destroy'], [self::class, 'gc']
        );
        
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/',
            'secure' => getenv('APP_ENV') === 'production',
            'httponly' => true, 'samesite' => 'Lax'
        ]);

        session_start();
        self::$isStarted = true;
        self::validateFingerprint();
    }

    public static function create(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['fingerprint'] = self::generateFingerprint();
    }

    
    public static function get(string $key, $default = null) { return $_SESSION[$key] ?? $default; }
    public static function set(string $key, $value): void { $_SESSION[$key] = $value; }
    public static function has(string $key): bool { return isset($_SESSION[$key]); }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    
    /**
     * Destrói a sessão atual do PHP.
     */
    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }
    private static function generateFingerprint(): string
    {
        return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    private static function validateFingerprint(): void
    {
        if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== self::generateFingerprint()) {
            self::destroy();
        }
    }
    
    // Métodos para o session_set_save_handler
    public static function open($p, $n): bool { return true; }
    public static function close(): bool { return true; }
    public static function read($id): string { return self::$redis->get("session:$id") ?? ''; }
    public static function write($id, $data): bool { return (bool) self::$redis->setex("session:$id", (int)ini_get('session.gc_maxlifetime'), $data); }
    public static function _destroy($id): bool { return (bool) self::$redis->del("session:$id"); }
    public static function gc($max): int { return 1; }
}
