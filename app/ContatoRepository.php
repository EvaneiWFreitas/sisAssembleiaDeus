<?php
declare(strict_types=1);

namespace App;

final class ContatoRepository
{
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(m.`nome` LIKE :q1 OR m.`email` LIKE :q2 OR m.`assunto` LIKE :q3 OR m.`mensagem` LIKE :q4)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
        }

        $situacao = (string) ($filtros['situacao'] ?? '');
        if ($situacao === 'nao_lidas') {
            $condicoes[] = 'm.`lida` = 0';
        } elseif ($situacao === 'lidas') {
            $condicoes[] = 'm.`lida` = 1';
        } elseif ($situacao === 'respondidas') {
            $condicoes[] = 'm.`respondida_em` IS NOT NULL';
        } elseif ($situacao === 'nao_respondidas') {
            $condicoes[] = 'm.`respondida_em` IS NULL';
        }

        return Database::run(
            'SELECT m.* FROM `contato_mensagens` m
              WHERE ' . implode(' AND ', $condicoes) . '
              ORDER BY m.`created_at` DESC, m.`id` DESC',
            $parametros
        )->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT * FROM `contato_mensagens` WHERE `id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`lida` = 0) AS `nao_lidas`,
                    SUM(`lida` = 1) AS `lidas`,
                    SUM(`respondida_em` IS NOT NULL) AS `respondidas`
               FROM `contato_mensagens`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'nao_lidas' => (int) ($linha['nao_lidas'] ?? 0),
            'lidas' => (int) ($linha['lidas'] ?? 0),
            'respondidas' => (int) ($linha['respondidas'] ?? 0),
        ];
    }

    public static function marcarLida(int $id): void
    {
        Database::run(
            'UPDATE `contato_mensagens` SET `lida` = 1 WHERE `id` = :id',
            [':id' => $id]
        );
    }

    public static function marcarRespondida(int $id): void
    {
        Database::run(
            'UPDATE `contato_mensagens`
                SET `lida` = 1, `respondida_em` = COALESCE(`respondida_em`, NOW())
              WHERE `id` = :id',
            [':id' => $id]
        );
    }

    public static function desmarcarRespondida(int $id): void
    {
        Database::run(
            'UPDATE `contato_mensagens` SET `respondida_em` = NULL WHERE `id` = :id',
            [':id' => $id]
        );
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'DELETE FROM `contato_mensagens` WHERE `id` = :id',
            [':id' => $id]
        );
    }

    public static function excluirLidas(): int
    {
        return Database::run(
            'DELETE FROM `contato_mensagens` WHERE `lida` = 1'
        )->rowCount();
    }
}
