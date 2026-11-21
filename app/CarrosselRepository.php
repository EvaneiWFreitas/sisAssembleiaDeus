<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class CarrosselRepository
{
    /** @return list<array<string, mixed>> */
    public static function listar(): array
    {
        return Database::run(
            'SELECT c.* FROM `carrossel` c ORDER BY c.`ordem` ASC, c.`id` ASC'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function ativos(): array
    {
        try {
            return Database::run(
                'SELECT c.`titulo`, c.`descricao`, c.`imagem`
                 FROM `carrossel` c
                 WHERE c.`ativo` = 1
                 ORDER BY c.`ordem` ASC, c.`id` ASC'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT * FROM `carrossel` WHERE `id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return array<string, string> */
    public static function regras(): array
    {
        return [
            'titulo' => 'max:150',
            'descricao' => 'max:255',
            'ordem' => 'integer|min:0',
            'ativo' => 'in:0,1',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $titulo = $titulo === '' ? null : $titulo;
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;
        $ordem = max(0, (int) ($dados['ordem'] ?? 0));
        $ativo = (int) ($dados['ativo'] ?? 0) === 1 ? 1 : 0;
        $imagem = trim((string) ($dados['imagem'] ?? ''));

        if ($id !== null && self::buscar($id) === null) {
            throw new InvalidArgumentException('Imagem de fundo não encontrada.');
        }
        if ($imagem === '') {
            throw new InvalidArgumentException('Selecione uma imagem de fundo.');
        }

        if ($id !== null) {
            Database::run(
                'UPDATE `carrossel` SET `titulo` = :titulo, `descricao` = :descricao,
                    `imagem` = :imagem, `ordem` = :ordem, `ativo` = :ativo, `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    ':titulo' => $titulo,
                    ':descricao' => $descricao,
                    ':imagem' => $imagem,
                    ':ordem' => $ordem,
                    ':ativo' => $ativo,
                    ':id' => $id,
                ]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `carrossel` (`titulo`, `descricao`, `imagem`, `ordem`, `ativo`, `created_at`, `updated_at`)
             VALUES (:titulo, :descricao, :imagem, :ordem, :ativo, NOW(), NOW())',
            [
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':imagem' => $imagem,
                ':ordem' => $ordem,
                ':ativo' => $ativo,
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        Database::run('DELETE FROM `carrossel` WHERE `id` = :id', [':id' => $id]);
    }

    public static function alternarAtivo(int $id): int
    {
        $item = self::buscar($id);
        if ($item === null) {
            throw new InvalidArgumentException('Imagem de fundo não encontrada.');
        }

        $ativo = (int) $item['ativo'] === 1 ? 0 : 1;
        Database::run('UPDATE `carrossel` SET `ativo` = :ativo, `updated_at` = NOW() WHERE `id` = :id', [
            ':ativo' => $ativo,
            ':id' => $id,
        ]);

        return $ativo;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`ativo` = 1) AS `ativos`,
                    SUM(`ativo` = 0) AS `inativos`
               FROM `carrossel`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'inativos' => (int) ($linha['inativos'] ?? 0),
        ];
    }

    public static function proximaOrdem(): int
    {
        $linha = Database::run('SELECT COALESCE(MAX(`ordem`), 0) AS `ordem` FROM `carrossel`')->fetch();

        return (int) ($linha['ordem'] ?? 0) + 10;
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    public static function valoresFormulario(array $item): array
    {
        return [
            'titulo' => (string) ($item['titulo'] ?? ''),
            'descricao' => (string) ($item['descricao'] ?? ''),
            'ordem' => (string) (int) ($item['ordem'] ?? 0),
            'ativo' => (string) (int) ($item['ativo'] ?? 0),
            'imagem' => (string) ($item['imagem'] ?? ''),
        ];
    }
}
