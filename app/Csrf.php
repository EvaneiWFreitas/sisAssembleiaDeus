<?php
declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;

final class Csrf
{
    public const FIELD = '_csrf';

    public static function ensure(): void
    {
        $chave = (string) config('security.csrf_token_name', '_csrf_token');
        $token = Session::get($chave);

        if (!is_string($token) || $token === '') {
            self::regenerate();
        }
    }

    public static function token(): string
    {
        self::ensure();
        $chave = (string) config('security.csrf_token_name', '_csrf_token');
        $token = Session::get($chave);

        return is_string($token) ? $token : '';
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals(self::token(), $token);
    }

    public static function check(Request $request): void
    {
        if (in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->post(self::FIELD);
            if (!is_string($token) || !self::validate($token)) {
                throw new HttpException(400, 'Token CSRF invalido ou ausente. Recarregue a pagina e tente novamente.');
            }
        }
    }

    public static function regenerate(): void
    {
        $chave = (string) config('security.csrf_token_name', '_csrf_token');
        Session::set($chave, bin2hex(random_bytes(32)));
    }
}
