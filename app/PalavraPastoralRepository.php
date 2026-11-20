<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class PalavraPastoralRepository
{
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(p.`titulo` LIKE :q1 OR p.`texto` LIKE :q2 OR u.`nome` LIKE :q3)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
        }

        $publicada = (string) ($filtros['publicada'] ?? '');
        if ($publicada === '0' || $publicada === '1') {
            $condicoes[] = 'p.`publicada` = :publicada';
            $parametros[':publicada'] = (int) $publicada;
        }

        return Database::run(
            'SELECT p.*, u.`nome` AS `autor_nome`
               FROM `palavra_pastoral` p
               LEFT JOIN `usuarios` u ON u.`id` = p.`autor_id`
              WHERE ' . implode(' AND ', $condicoes) . '
              ORDER BY p.`created_at` DESC, p.`id` DESC',
            $parametros
        )->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT p.*, u.`nome` AS `autor_nome`
               FROM `palavra_pastoral` p
               LEFT JOIN `usuarios` u ON u.`id` = p.`autor_id`
              WHERE p.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function autores(): array
    {
        return Database::run(
            'SELECT `id`, `nome`, `email` FROM `usuarios` WHERE `ativo` = 1 ORDER BY `nome` ASC'
        )->fetchAll();
    }

    public static function regras(): array
    {
        return [
            'titulo' => 'required|max:180',
            'texto' => 'max:10000',
            'autor_id' => 'integer',
            'foto' => 'max:255',
            'publicada' => 'in:0,1',
        ];
    }

    public static function salvar(?int $id, array $dados): int
    {
        $atual = $id === null ? null : self::buscar($id);
        if ($id !== null && $atual === null) {
            throw new InvalidArgumentException('Mensagem pastoral não encontrada.');
        }

        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '') {
            throw new InvalidArgumentException('Informe o título da mensagem.');
        }

        $texto = trim((string) ($dados['texto'] ?? ''));
        $texto = $texto === '' ? null : $texto;
        $foto = trim((string) ($dados['foto'] ?? ''));
        $foto = $foto === '' ? null : $foto;
        $publicada = (int) ($dados['publicada'] ?? 0) === 1 ? 1 : 0;

        $autorId = (int) ($dados['autor_id'] ?? 0);
        if ($autorId <= 0) {
            $autorId = null;
        } elseif (Database::run('SELECT `id` FROM `usuarios` WHERE `id` = :id AND `ativo` = 1', [':id' => $autorId])->fetch() === false) {
            throw new InvalidArgumentException('Autor selecionado não está disponível.');
        }

        $parametros = [
            ':titulo' => $titulo,
            ':texto' => $texto,
            ':autor' => $autorId,
            ':foto' => $foto,
            ':publicada' => $publicada,
        ];

        if ($id !== null) {
            Database::run(
                'UPDATE `palavra_pastoral`
                    SET `titulo` = :titulo, `texto` = :texto, `autor_id` = :autor,
                        `foto` = :foto, `publicada` = :publicada, `updated_at` = NOW()
                  WHERE `id` = :id',
                $parametros + [':id' => $id]
            );
            $registroId = $id;
        } else {
            Database::run(
                'INSERT INTO `palavra_pastoral` (`titulo`, `texto`, `autor_id`, `foto`, `publicada`, `created_at`, `updated_at`)
                 VALUES (:titulo, :texto, :autor, :foto, :publicada, NOW(), NOW())',
                $parametros
            );
            $registroId = (int) Database::connection()->lastInsertId();
        }

        if ($publicada === 1) {
            Database::run(
                'UPDATE `palavra_pastoral` SET `publicada` = 0, `updated_at` = NOW() WHERE `id` <> :id_outros',
                [':id_outros' => $registroId]
            );
        }

        return $registroId;
    }

    public static function excluir(int $id): void
    {
        Database::run('DELETE FROM `palavra_pastoral` WHERE `id` = :id', [':id' => $id]);
    }

    public static function alternarPublicacao(int $id): int
    {
        $item = self::buscar($id);
        if ($item === null) {
            throw new InvalidArgumentException('Mensagem pastoral não encontrada.');
        }

        $publicada = (int) $item['publicada'] === 1 ? 0 : 1;
        Database::run('UPDATE `palavra_pastoral` SET `publicada` = :publicada, `updated_at` = NOW() WHERE `id` = :id', [
            ':publicada' => $publicada,
            ':id' => $id,
        ]);

        if ($publicada === 1) {
            Database::run(
                'UPDATE `palavra_pastoral` SET `publicada` = 0, `updated_at` = NOW() WHERE `id` <> :id_outros',
                [':id_outros' => $id]
            );
        }

        return $publicada;
    }

    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`publicada` = 1) AS `publicadas`,
                    SUM(`publicada` = 0) AS `rascunhos`
               FROM `palavra_pastoral`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'publicadas' => (int) ($linha['publicadas'] ?? 0),
            'rascunhos' => (int) ($linha['rascunhos'] ?? 0),
        ];
    }

    public static function valoresFormulario(array $item): array
    {
        return [
            'titulo' => (string) ($item['titulo'] ?? ''),
            'texto' => (string) ($item['texto'] ?? ''),
            'autor_id' => (string) ($item['autor_id'] ?? '0'),
            'foto' => (string) ($item['foto'] ?? ''),
            'publicada' => (string) (int) ($item['publicada'] ?? 0),
        ];
    }

    public static function listarVersiculos(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(v.`referencia` LIKE :q1 OR v.`texto` LIKE :q2)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
        }

        $ativo = (string) ($filtros['ativo'] ?? '');
        if ($ativo === '0' || $ativo === '1') {
            $condicoes[] = 'v.`ativo` = :ativo';
            $parametros[':ativo'] = (int) $ativo;
        }

        return Database::run(
            'SELECT v.* FROM `versiculos_destaque` v
              WHERE ' . implode(' AND ', $condicoes) . '
              ORDER BY v.`ordem` ASC, v.`id` ASC',
            $parametros
        )->fetchAll();
    }

    public static function buscarVersiculo(int $id): ?array
    {
        $registro = Database::run(
            'SELECT * FROM `versiculos_destaque` WHERE `id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function regrasVersiculo(): array
    {
        return [
            'referencia' => 'required|max:120',
            'texto' => 'required|max:2000',
            'ordem' => 'integer|min:0',
            'ativo' => 'in:0,1',
        ];
    }

    public static function salvarVersiculo(?int $id, array $dados): int
    {
        $atual = $id === null ? null : self::buscarVersiculo($id);
        if ($id !== null && $atual === null) {
            throw new InvalidArgumentException('Versículo não encontrado.');
        }

        $referencia = trim((string) ($dados['referencia'] ?? ''));
        $texto = trim((string) ($dados['texto'] ?? ''));
        if ($referencia === '' || $texto === '') {
            throw new InvalidArgumentException('Informe a referência e o texto do versículo.');
        }

        $ordem = max(0, (int) ($dados['ordem'] ?? 0));
        $ativo = (int) ($dados['ativo'] ?? 0) === 1 ? 1 : 0;
        $parametros = [
            ':referencia' => $referencia,
            ':texto' => $texto,
            ':ordem' => $ordem,
            ':ativo' => $ativo,
        ];

        if ($id !== null) {
            Database::run(
                'UPDATE `versiculos_destaque`
                    SET `referencia` = :referencia, `texto` = :texto, `ordem` = :ordem,
                        `ativo` = :ativo, `updated_at` = NOW()
                  WHERE `id` = :id',
                $parametros + [':id' => $id]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `versiculos_destaque` (`referencia`, `texto`, `ordem`, `ativo`, `created_at`, `updated_at`)
             VALUES (:referencia, :texto, :ordem, :ativo, NOW(), NOW())',
            $parametros
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluirVersiculo(int $id): void
    {
        Database::run('DELETE FROM `versiculos_destaque` WHERE `id` = :id', [':id' => $id]);
    }

    public static function alternarVersiculoAtivo(int $id): int
    {
        $item = self::buscarVersiculo($id);
        if ($item === null) {
            throw new InvalidArgumentException('Versículo não encontrado.');
        }

        $ativo = (int) $item['ativo'] === 1 ? 0 : 1;
        Database::run('UPDATE `versiculos_destaque` SET `ativo` = :ativo, `updated_at` = NOW() WHERE `id` = :id', [
            ':ativo' => $ativo,
            ':id' => $id,
        ]);

        return $ativo;
    }

    public static function estatisticasVersiculos(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`ativo` = 1) AS `ativos`,
                    SUM(`ativo` = 0) AS `inativos`
               FROM `versiculos_destaque`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'inativos' => (int) ($linha['inativos'] ?? 0),
        ];
    }

    public static function proximaOrdemVersiculo(): int
    {
        $linha = Database::run('SELECT COALESCE(MAX(`ordem`), 0) AS `ordem` FROM `versiculos_destaque`')->fetch();

        return (int) ($linha['ordem'] ?? 0) + 10;
    }

    public static function valoresFormularioVersiculo(array $item): array
    {
        return [
            'referencia' => (string) ($item['referencia'] ?? ''),
            'texto' => (string) ($item['texto'] ?? ''),
            'ordem' => (string) (int) ($item['ordem'] ?? 0),
            'ativo' => (string) (int) ($item['ativo'] ?? 0),
        ];
    }
}
