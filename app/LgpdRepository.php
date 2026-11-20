<?php
declare(strict_types=1);

namespace App;

final class LgpdRepository
{
    private const TITULARES = ['membro', 'obreiro', 'contato'];

    public static function filtros(array $query): array
    {
        return [
            'titular_tipo' => in_array((string) ($query['titular_tipo'] ?? ''), self::TITULARES, true) ? (string) $query['titular_tipo'] : '',
            'finalidade' => trim((string) ($query['finalidade'] ?? '')),
            'consentido' => (string) ($query['consentido'] ?? '') === '1' ? '1' : ((string) ($query['consentido'] ?? '') === '0' ? '0' : ''),
            'de' => self::data((string) ($query['de'] ?? '')),
            'ate' => self::data((string) ($query['ate'] ?? '')),
            'q' => trim((string) ($query['q'] ?? '')),
            'limite' => in_array((int) ($query['limite'] ?? 100), [50, 100, 200, 500], true) ? (int) ($query['limite'] ?? 100) : 100,
        ];
    }

    public static function listar(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);

        return Database::run(
            'SELECT c.*, t.`nome` AS `titular_nome`
               FROM `lgpd_consentimentos` c
               LEFT JOIN (
                    SELECT \'membro\' AS `tipo`, `id`, `nome` FROM `membros`
                    UNION ALL SELECT \'obreiro\' AS `tipo`, `id`, `nome` FROM `obreiros`
                    UNION ALL SELECT \'contato\' AS `tipo`, `id`, `nome` FROM `contato_mensagens`
               ) t ON t.`tipo` = c.`titular_tipo` AND t.`id` = c.`titular_id`
              WHERE 1 = 1' . $condicoes . '
              ORDER BY c.`data_consentimento` DESC, c.`id` DESC
              LIMIT ' . (int) $filtros['limite'],
            $parametros
        )->fetchAll();
    }

    public static function estatisticas(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(CASE WHEN c.`consentido` = 1 THEN 1 ELSE 0 END) AS `consentidos`,
                    SUM(CASE WHEN c.`consentido` = 0 THEN 1 ELSE 0 END) AS `recusados`,
                    COUNT(DISTINCT c.`finalidade`) AS `finalidades`
               FROM `lgpd_consentimentos` c
               LEFT JOIN (
                    SELECT \'membro\' AS `tipo`, `id`, `nome` FROM `membros`
                    UNION ALL SELECT \'obreiro\' AS `tipo`, `id`, `nome` FROM `obreiros`
                    UNION ALL SELECT \'contato\' AS `tipo`, `id`, `nome` FROM `contato_mensagens`
               ) t ON t.`tipo` = c.`titular_tipo` AND t.`id` = c.`titular_id`
              WHERE 1 = 1' . $condicoes,
            $parametros
        )->fetch();

        return is_array($linha) ? $linha : ['total' => 0, 'consentidos' => 0, 'recusados' => 0, 'finalidades' => 0];
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT c.*, t.`nome` AS `titular_nome`
               FROM `lgpd_consentimentos` c
               LEFT JOIN (
                    SELECT \'membro\' AS `tipo`, `id`, `nome` FROM `membros`
                    UNION ALL SELECT \'obreiro\' AS `tipo`, `id`, `nome` FROM `obreiros`
                    UNION ALL SELECT \'contato\' AS `tipo`, `id`, `nome` FROM `contato_mensagens`
               ) t ON t.`tipo` = c.`titular_tipo` AND t.`id` = c.`titular_id`
              WHERE c.`id` = :id
              LIMIT 1',
            [':id' => $id]
        )->fetch();

        return is_array($registro) ? $registro : null;
    }

    public static function finalidades(): array
    {
        return Database::run(
            'SELECT DISTINCT `finalidade` FROM `lgpd_consentimentos` ORDER BY `finalidade` ASC'
        )->fetchAll();
    }

    public static function titulares(string $tipo): array
    {
        if (!in_array($tipo, self::TITULARES, true)) {
            return [];
        }

        if ($tipo === 'contato') {
            return Database::run(
                'SELECT `id`, `nome`, `email` FROM `contato_mensagens` ORDER BY `created_at` DESC LIMIT 300'
            )->fetchAll();
        }

        return Database::run(
            'SELECT `id`, `nome`, `email` FROM `' . ($tipo === 'membro' ? 'membros' : 'obreiros') . '`
              WHERE `deleted_at` IS NULL ORDER BY `nome` ASC LIMIT 500'
        )->fetchAll();
    }

    public static function criar(array $dados): int
    {
        Database::run(
            'INSERT INTO `lgpd_consentimentos`
                (`titular_tipo`, `titular_id`, `finalidade`, `consentido`, `data_consentimento`, `ip`)
             VALUES
                (:titular_tipo, :titular_id, :finalidade, :consentido, :data_consentimento, :ip)',
            self::parametros($dados)
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function atualizar(int $id, array $dados): void
    {
        $parametros = self::parametros($dados);
        $parametros[':id'] = $id;
        Database::run(
            'UPDATE `lgpd_consentimentos`
                SET `titular_tipo` = :titular_tipo,
                    `titular_id` = :titular_id,
                    `finalidade` = :finalidade,
                    `consentido` = :consentido,
                    `data_consentimento` = :data_consentimento,
                    `ip` = :ip
              WHERE `id` = :id',
            $parametros
        );
    }

    public static function excluir(int $id): void
    {
        Database::run('DELETE FROM `lgpd_consentimentos` WHERE `id` = :id', [':id' => $id]);
    }

    public static function auditoriaResumo(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `registros`, MIN(`created_at`) AS `mais_antigo` FROM `auditoria`'
        )->fetch();

        return is_array($linha) ? $linha : ['registros' => 0, 'mais_antigo' => null];
    }

    private static function parametros(array $dados): array
    {
        return [
            ':titular_tipo' => (string) ($dados['titular_tipo'] ?? ''),
            ':titular_id' => (int) ($dados['titular_id'] ?? 0),
            ':finalidade' => (string) ($dados['finalidade'] ?? ''),
            ':consentido' => (int) ($dados['consentido'] ?? 0) === 1 ? 1 : 0,
            ':data_consentimento' => (string) ($dados['data_consentimento'] ?? date('Y-m-d H:i:s')),
            ':ip' => (string) ($dados['ip'] ?? '') === '' ? null : (string) $dados['ip'],
        ];
    }

    private static function data(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }
        $data = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $data !== false && $data->format('Y-m-d') === $valor ? $valor : '';
    }

    private static function condicoes(array $filtros): array
    {
        $sql = '';
        $parametros = [];

        if (($filtros['titular_tipo'] ?? '') !== '') {
            $sql .= ' AND c.`titular_tipo` = :titular_tipo';
            $parametros[':titular_tipo'] = $filtros['titular_tipo'];
        }
        if (($filtros['finalidade'] ?? '') !== '') {
            $sql .= ' AND c.`finalidade` = :finalidade';
            $parametros[':finalidade'] = $filtros['finalidade'];
        }
        if (($filtros['consentido'] ?? '') !== '') {
            $sql .= ' AND c.`consentido` = :consentido';
            $parametros[':consentido'] = (int) $filtros['consentido'];
        }
        if (($filtros['de'] ?? '') !== '') {
            $sql .= ' AND c.`data_consentimento` >= :de';
            $parametros[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (($filtros['ate'] ?? '') !== '') {
            $sql .= ' AND c.`data_consentimento` <= :ate';
            $parametros[':ate'] = $filtros['ate'] . ' 23:59:59';
        }
        if (($filtros['q'] ?? '') !== '') {
            $termo = '%' . $filtros['q'] . '%';
            $sql .= ' AND (c.`finalidade` LIKE :q_finalidade OR t.`nome` LIKE :q_titular OR c.`ip` LIKE :q_ip OR CAST(c.`titular_id` AS CHAR) LIKE :q_id)';
            $parametros[':q_finalidade'] = $termo;
            $parametros[':q_titular'] = $termo;
            $parametros[':q_ip'] = $termo;
            $parametros[':q_id'] = $termo;
        }

        return [$sql, $parametros];
    }
}
