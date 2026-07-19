<?php

namespace App\Core;

use Illuminate\Http\Exceptions\HttpResponseException;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!session()->has(self::SESSION_KEY)) {
            session([self::SESSION_KEY => bin2hex(random_bytes(32))]);
        }

        return session(self::SESSION_KEY);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function verify(): void
    {
        if (request()->method() !== 'POST') {
            return;
        }

        $submitted = request()->input('_csrf', request()->header('X-CSRF-TOKEN', ''));
        $expected = session(self::SESSION_KEY, '');

        if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
            if (self::wantsJson()) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired security token. Please refresh and try again.',
                ], 419));
            }

            session(['flash' => [
                'type' => 'danger',
                'message' => 'Your session expired or the form was invalid. Please try again.',
            ]]);

            $referer = request()->header('referer');
            throw new HttpResponseException(redirect($referer ?: church_url('login')));
        }
    }

    private static function wantsJson(): bool
    {
        return request()->expectsJson()
            || str_contains(request()->path(), 'qr-checkin');
    }
}
