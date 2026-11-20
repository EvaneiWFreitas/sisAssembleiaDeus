<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    private const MAX_TENTATIVAS = 5;
    private const MINUTOS_BLOQUEIO = 30;

    private static ?array $user = null;

    public static function hash(string $senha): string
    {
        return password_hash($senha, PASSWORD_DEFAULT);
    }

    public static function attempt(string $email, string $senha): bool
    {
        $email = trim($email);
        $stmt = Database::run('SELECT * FROM `usuarios` WHERE `email` = :email LIMIT 1', [':email' => $email]);
        $usuario = $stmt->fetch();

        if (!is_array($usuario)) {
            self::registrarFalha(null);
            return false;
        }

        if ((int) $usuario['ativo'] !== 1) {
            return false;
        }

        if ($usuario['bloqueado_ate'] !== null && strtotime((string) $usuario['bloqueado_ate']) > time()) {
            return false;
        }

        if (!password_verify($senha, (string) $usuario['senha_hash'])) {
            self::registrarFalha((int) $usuario['id']);
            return false;
        }

        Database::run(
            'UPDATE `usuarios`
                SET `tentativas_falhas` = 0, `bloqueado_ate` = NULL, `ultimo_acesso` = NOW(),
                    `reset_token_hash` = NULL, `reset_expira_em` = NULL
              WHERE `id` = :id',
            [':id' => $usuario['id']]
        );

        Session::set('usuario_id', (int) $usuario['id']);
        Session::regenerate();
        Csrf::regenerate();
        Permission::limpar();
        self::$user = $usuario;

        Audit::registrar('login', 'auth', 'usuarios', (int) $usuario['id'], null, ['email' => $email]);

        return true;
    }

    private static function registrarFalha(?int $usuarioId): void
    {
        if ($usuarioId === null) {
            Audit::registrar('login_falha', 'auth', null, null, null, ['usuario' => 'desconhecido']);
            return;
        }

        $stmt = Database::run('SELECT `tentativas_falhas` FROM `usuarios` WHERE `id` = :id', [':id' => $usuarioId]);
        $tentativas = (int) (($stmt->fetch()['tentativas_falhas'] ?? 0)) + 1;
        $bloqueadoAte = $tentativas >= self::MAX_TENTATIVAS
            ? date('Y-m-d H:i:s', time() + self::MINUTOS_BLOQUEIO * 60)
            : null;

        Database::run(
            'UPDATE `usuarios` SET `tentativas_falhas` = :tentativas, `bloqueado_ate` = :bloqueado WHERE `id` = :id',
            [':tentativas' => $tentativas, ':bloqueado' => $bloqueadoAte, ':id' => $usuarioId]
        );

        Audit::registrar('login_falha', 'auth', 'usuarios', $usuarioId, null, ['tentativas' => $tentativas]);
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = self::id();
        if ($id === null) {
            return null;
        }

        $stmt = Database::run(
            'SELECT `id`, `perfil_id`, `nome`, `email`, `foto`, `ativo` FROM `usuarios` WHERE `id` = :id AND `ativo` = 1 LIMIT 1',
            [':id' => $id]
        );
        $usuario = $stmt->fetch();

        if (!is_array($usuario)) {
            Session::remove('usuario_id');
            return null;
        }

        self::$user = $usuario;
        return self::$user;
    }

    public static function id(): ?int
    {
        $id = Session::get('usuario_id');
        return is_numeric($id) ? (int) $id : null;
    }

    public static function perfilId(): ?int
    {
        $usuario = self::user();
        return $usuario === null ? null : (int) $usuario['perfil_id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasPermission(string $modulo, string $acao = 'ver'): bool
    {
        return self::check() && Permission::can($modulo, $acao);
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            Audit::registrar('logout', 'auth', 'usuarios', $id);
        }
        Session::destroy();
        Permission::limpar();
        self::$user = null;
    }
}
