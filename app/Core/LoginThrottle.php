<?php

namespace App\Core;

class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private const LOCKOUT_SECONDS = 900;

    public static function tooManyAttempts(string $username): bool
    {
        $key = self::key($username);
        $attempts = session('login_attempts.' . $key, ['count' => 0, 'first_at' => time(), 'locked_until' => 0]);

        if (($attempts['locked_until'] ?? 0) > time()) {
            return true;
        }

        if (time() - ($attempts['first_at'] ?? 0) > self::WINDOW_SECONDS) {
            self::clear($username);
            return false;
        }

        return ($attempts['count'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $username): void
    {
        $key = self::key($username);
        $attempts = session('login_attempts.' . $key, ['count' => 0, 'first_at' => time(), 'locked_until' => 0]);

        if (time() - ($attempts['first_at'] ?? 0) > self::WINDOW_SECONDS) {
            $attempts = ['count' => 0, 'first_at' => time(), 'locked_until' => 0];
        }

        $attempts['count'] = ($attempts['count'] ?? 0) + 1;
        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $attempts['locked_until'] = time() + self::LOCKOUT_SECONDS;
        }

        session(['login_attempts.' . $key => $attempts]);
    }

    public static function clear(string $username): void
    {
        session()->forget('login_attempts.' . self::key($username));
    }

    public static function retryAfterSeconds(string $username): int
    {
        $key = self::key($username);
        $lockedUntil = session('login_attempts.' . $key . '.locked_until', 0);
        return max(0, $lockedUntil - time());
    }

    private static function key(string $username): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return hash('sha256', strtolower(trim($username)) . '|' . $ip);
    }
}
