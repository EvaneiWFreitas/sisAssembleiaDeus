<?php
declare(strict_types=1);

namespace App;

use Throwable;

final class AclRepository
{
    /** @return list<array<string, mixed>> */
    public static function perfis(): array
    {
        return Database::run(
            'SELECT p.`id`, p.`nome`, p.`slug`, p.`descricao`, p.`ativo`,
                    COUNT(DISTINCT pp.`permissao_id`) AS `total_permissoes`,
                    COUNT(DISTINCT u.`id`) AS `total_usuarios`
             FROM `perfis` p
             LEFT JOIN `perfil_permissao` pp ON pp.`perfil_id` = p.`id`
             LEFT JOIN `usuarios` u ON u.`perfil_id` = p.`id`
             GROUP BY p.`id`
             ORDER BY p.`id` ASC'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function perfil(int $id): ?array
    {
        $linha = Database::run('SELECT * FROM `perfis` WHERE `id` = :id LIMIT 1', [':id' => $id])->fetch();
        return is_array($linha) ? $linha : null;
    }

    /** @return array<string, list<array<string, string>>> */
    public static function catalogo(): array
    {
        $modulos = AdminRepository::modulos();
        $grupos = [];

        foreach (Database::run('SELECT `id`, `modulo`, `acao`, `descricao` FROM `permissoes` ORDER BY `modulo`, `acao`')->fetchAll() as $permissao) {
            $modulo = (string) $permissao['modulo'];
            $nomeModulo = $modulos[$modulo]['nome'] ?? ucwords(str_replace('_', ' ', $modulo));
            $grupos[$nomeModulo][] = [
                'id' => (string) $permissao['id'],
                'modulo' => $modulo,
                'acao' => (string) $permissao['acao'],
                'descricao' => (string) ($permissao['descricao'] ?? ''),
                'rotulo' => self::acaoRotulo((string) $permissao['acao']),
            ];
        }

        ksort($grupos, SORT_NATURAL | SORT_FLAG_CASE);

        return $grupos;
    }

    /** @return list<string> */
    public static function permissoesDoPerfil(int $perfilId): array
    {
        $linhas = Database::run(
            'SELECT CONCAT(p.`modulo`, ".", p.`acao`) AS `chave`
             FROM `perfil_permissao` pp
             INNER JOIN `permissoes` p ON p.`id` = pp.`permissao_id`
             WHERE pp.`perfil_id` = :id',
            [':id' => $perfilId]
        )->fetchAll();

        $chaves = [];
        foreach ($linhas as $linha) {
            $chaves[] = (string) $linha['chave'];
        }

        return $chaves;
    }

    /** @return array<int, string> */
    public static function chavesValidas(): array
    {
        $validas = [];
        foreach (Database::run('SELECT `id`, `modulo`, `acao` FROM `permissoes`')->fetchAll() as $linha) {
            $validas[(int) $linha['id']] = (string) $linha['modulo'] . '.' . (string) $linha['acao'];
        }

        return $validas;
    }

    /** @param list<int> $permissaoIds */
    public static function salvarPermissoes(int $perfilId, array $permissaoIds): void
    {
        $perfil = self::perfil($perfilId);
        if ($perfil === null) {
            return;
        }

        $validas = self::chavesValidas();
        $selecionadas = array_values(array_unique(array_filter(
            array_map('intval', $permissaoIds),
            static fn(int $id): bool => $id > 0 && isset($validas[$id])
        )));

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            if (($perfil['slug'] ?? '') !== 'superadministrador') {
                Database::run('DELETE FROM `perfil_permissao` WHERE `perfil_id` = :id', [':id' => $perfilId]);
            } else {
                $selecionadas = array_keys($validas);
            }

            foreach ($selecionadas as $id) {
                Database::run(
                    'INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`) VALUES (:perfil, :permissao)',
                    [':perfil' => $perfilId, ':permissao' => $id]
                );
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        Permission::limpar();
    }

    private static function acaoRotulo(string $acao): string
    {
        return match ($acao) {
            'ver' => 'Visualizar',
            'criar' => 'Criar',
            'editar' => 'Editar',
            'excluir' => 'Excluir',
            'gerar' => 'Gerar',
            default => ucfirst($acao),
        };
    }
}
