<?php
declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;

final class Permission
{
    /** @var array<string, true>|null */
    private static ?array $cache = null;

    /** @return array<string, true> */
    private static function carregar(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $perfilId = Auth::perfilId();
        if ($perfilId === null) {
            self::$cache = [];
            return self::$cache;
        }

        $stmt = Database::run(
            'SELECT `p`.`modulo`, `p`.`acao`
               FROM `perfil_permissao` `pp`
               JOIN `permissoes` `p` ON `p`.`id` = `pp`.`permissao_id`
              WHERE `pp`.`perfil_id` = :perfil',
            [':perfil' => $perfilId]
        );

        $permissao = [];
        foreach ($stmt->fetchAll() as $linha) {
            $permissao[$linha['modulo'] . '.' . $linha['acao']] = true;
        }

        self::$cache = $permissao;
        return self::$cache;
    }

    public static function can(string $modulo, string $acao = 'ver'): bool
    {
        $permissao = self::carregar();
        return isset($permissao[$modulo . '.' . $acao]);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::carregar());
    }

    public static function limpar(): void
    {
        self::$cache = null;
    }

    public static function requirePermission(string $modulo, string $acao = 'ver'): void
    {
        if (!self::can($modulo, $acao)) {
            throw new HttpException(403, 'Sem permissão para acessar este módulo.');
        }
    }
}
