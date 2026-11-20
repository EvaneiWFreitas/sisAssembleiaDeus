<?php
declare(strict_types=1);

namespace App;

final class UserRepository
{
    /** @return list<array<string, mixed>> */
    public static function listar(string $busca = ''): array
    {
        $sql = 'SELECT u.`id`, u.`perfil_id`, u.`nome`, u.`email`, u.`ativo`, u.`ultimo_acesso`,
                       u.`bloqueado_ate`, u.`created_at`, p.`nome` AS `perfil_nome`
                FROM `usuarios` u
                LEFT JOIN `perfis` p ON p.`id` = u.`perfil_id`';
        $parametros = [];

        if ($busca !== '') {
            $sql .= ' WHERE u.`nome` LIKE :busca OR u.`email` LIKE :busca OR p.`nome` LIKE :busca';
            $parametros[':busca'] = '%' . $busca . '%';
        }

        $sql .= ' ORDER BY u.`nome` ASC';

        return Database::run($sql, $parametros)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $linha = Database::run(
            'SELECT u.*, p.`nome` AS `perfil_nome`, p.`slug` AS `perfil_slug`
             FROM `usuarios` u
             LEFT JOIN `perfis` p ON p.`id` = u.`perfil_id`
             WHERE u.`id` = :id
             LIMIT 1',
            [':id' => $id]
        )->fetch();

        return is_array($linha) ? $linha : null;
    }

    /** @return list<array<string, mixed>> */
    public static function perfis(): array
    {
        return Database::run('SELECT `id`, `nome`, `slug`, `descricao` FROM `perfis` WHERE `ativo` = 1 ORDER BY `nome` ASC')->fetchAll();
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT
                COUNT(*) AS `total`,
                SUM(CASE WHEN `ativo` = 1 THEN 1 ELSE 0 END) AS `ativos`,
                SUM(CASE WHEN `ativo` = 0 THEN 1 ELSE 0 END) AS `inativos`,
                SUM(CASE WHEN `bloqueado_ate` IS NOT NULL AND `bloqueado_ate` > NOW() THEN 1 ELSE 0 END) AS `bloqueados`
             FROM `usuarios`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'inativos' => (int) ($linha['inativos'] ?? 0),
            'bloqueados' => (int) ($linha['bloqueados'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados): int
    {
        $usuarioId = Auth::id();
        Database::run(
            'INSERT INTO `usuarios`
                (`perfil_id`, `nome`, `email`, `senha_hash`, `ativo`, `observacoes`, `created_by`, `updated_by`)
             VALUES
                (:perfil, :nome, :email, :senha, :ativo, :observacoes, :criador, :atualizador)',
            [
                ':perfil' => (int) $dados['perfil_id'],
                ':nome' => (string) $dados['nome'],
                ':email' => (string) $dados['email'],
                ':senha' => Auth::hash((string) $dados['senha']),
                ':ativo' => (int) $dados['ativo'],
                ':observacoes' => ($dados['observacoes'] ?? '') === '' ? null : (string) $dados['observacoes'],
                ':criador' => $usuarioId,
                ':atualizador' => $usuarioId,
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        $campos = [
            '`perfil_id` = :perfil',
            '`nome` = :nome',
            '`email` = :email',
            '`ativo` = :ativo',
            '`observacoes` = :observacoes',
            '`updated_by` = :usuario',
        ];
        $parametros = [
            ':perfil' => (int) $dados['perfil_id'],
            ':nome' => (string) $dados['nome'],
            ':email' => (string) $dados['email'],
            ':ativo' => (int) $dados['ativo'],
            ':observacoes' => ($dados['observacoes'] ?? '') === '' ? null : (string) $dados['observacoes'],
            ':usuario' => Auth::id(),
            ':id' => $id,
        ];

        if (($dados['senha'] ?? '') !== '') {
            $campos[] = '`senha_hash` = :senha';
            $campos[] = '`tentativas_falhas` = 0';
            $campos[] = '`bloqueado_ate` = NULL';
            $campos[] = '`reset_token_hash` = NULL';
            $campos[] = '`reset_expira_em` = NULL';
            $parametros[':senha'] = Auth::hash((string) $dados['senha']);
        }

        Database::run(
            'UPDATE `usuarios` SET ' . implode(', ', $campos) . ' WHERE `id` = :id',
            $parametros
        );
    }

    public static function definirStatus(int $id, int $ativo): void
    {
        Database::run(
            'UPDATE `usuarios` SET `ativo` = :ativo, `updated_by` = :usuario WHERE `id` = :id',
            [':ativo' => $ativo, ':usuario' => Auth::id(), ':id' => $id]
        );
    }

    public static function redefinirSenha(int $id, string $senha): void
    {
        Database::run(
            'UPDATE `usuarios` SET
                `senha_hash` = :senha, `tentativas_falhas` = 0, `bloqueado_ate` = NULL,
                `reset_token_hash` = NULL, `reset_expira_em` = NULL, `updated_by` = :usuario
             WHERE `id` = :id',
            [':senha' => Auth::hash($senha), ':usuario' => Auth::id(), ':id' => $id]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function historico(int $id): array
    {
        return Database::run(
            'SELECT `acao`, `modulo`, `dados_novos`, `created_at`
             FROM `auditoria`
             WHERE `entidade` = "usuarios" AND `entidade_id` = :entidade
                OR (`modulo` = "auth" AND `entidade_id` = :auth)
             ORDER BY `created_at` DESC
             LIMIT 8',
            [':entidade' => $id, ':auth' => $id]
        )->fetchAll();
    }

    public static function contarSuperadministradoresAtivos(?int $excetoId = null): int
    {
        $sql = 'SELECT COUNT(*) AS `total`
                FROM `usuarios` u
                INNER JOIN `perfis` p ON p.`id` = u.`perfil_id`
                WHERE p.`slug` = "superadministrador" AND u.`ativo` = 1';
        $parametros = [];

        if ($excetoId !== null) {
            $sql .= ' AND u.`id` <> :id';
            $parametros[':id'] = $excetoId;
        }

        return (int) (Database::run($sql, $parametros)->fetch()['total'] ?? 0);
    }
}
