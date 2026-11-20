<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class Session
{
    private static bool $iniciada = false;

    public static function start(): void
    {
        if (self::$iniciada || session_status() === PHP_SESSION_ACTIVE) {
            self::$iniciada = true;
            return;
        }

        $nome = (string) config('security.session_name', 'sisad_session');
        $path = (string) config('app.base_path', '/');
        if ($path === '') {
            $path = '/';
        }

        session_name($nome);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $path,
            'domain' => '',
            'secure' => (bool) config('security.cookie_secure', false),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_cache_limiter('nocache');

        if (!session_start()) {
            throw new RuntimeException('Nao foi possivel iniciar a sessao.');
        }

        self::$iniciada = true;
    }

    public static function sincronizar(): void
    {
        self::start();

        $request = Request::current();
        $usuarioId = self::get('usuario_id');
        $expira = date('Y-m-d H:i:s', time() + (int) config('security.session_lifetime', 7200));

        try {
            Database::run(
                'INSERT INTO `sessoes` (`id`, `usuario_id`, `ip`, `user_agent`, `expira_em`, `ultima_atividade`, `ativa`)
                 VALUES (:id, :usuario, :ip, :agente, :expira, NOW(), 1)
                 ON DUPLICATE KEY UPDATE `usuario_id` = VALUES(`usuario_id`), `ip` = VALUES(`ip`),
                    `user_agent` = VALUES(`user_agent`), `expira_em` = VALUES(`expira_em`),
                    `ultima_atividade` = NOW(), `ativa` = 1',
                [
                    ':id' => session_id(),
                    ':usuario' => is_numeric($usuarioId) ? (int) $usuarioId : null,
                    ':ip' => $request->ip !== '' ? $request->ip : null,
                    ':agente' => $request->userAgent !== '' ? $request->userAgent : null,
                    ':expira' => $expira,
                ]
            );
        } catch (\Throwable $exception) {
            error_log('Falha ao sincronizar sessao: ' . $exception->getMessage());
        }
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        self::start();
        return $_SESSION[$chave] ?? $padrao;
    }

    public static function set(string $chave, mixed $valor): void
    {
        self::start();
        $_SESSION[$chave] = $valor;
    }

    public static function remove(string $chave): void
    {
        self::start();
        unset($_SESSION[$chave]);
    }

    public static function flash(string $chave, mixed $valor): void
    {
        $mensagens = self::get('_flash', []);
        if (!is_array($mensagens)) {
            $mensagens = [];
        }
        $mensagens[$chave] = $valor;
        self::set('_flash', $mensagens);
    }

    public static function takeFlash(string $chave): mixed
    {
        $mensagens = self::get('_flash', []);
        if (!is_array($mensagens) || !array_key_exists($chave, $mensagens)) {
            return null;
        }
        $valor = $mensagens[$chave];
        unset($mensagens[$chave]);
        self::set('_flash', $mensagens);

        return $valor;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function id(): string
    {
        self::start();
        return session_id();
    }

    public static function destroy(): void
    {
        self::start();

        try {
            Database::run('DELETE FROM `sessoes` WHERE `id` = :id', [':id' => session_id()]);
        } catch (\Throwable $exception) {
            error_log('Falha ao remover sessao: ' . $exception->getMessage());
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        session_destroy();
        self::$iniciada = false;
    }
}
