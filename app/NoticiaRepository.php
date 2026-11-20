<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class NoticiaRepository
{
    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['n.`deleted_at` IS NULL'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(n.`titulo` LIKE :q1 OR n.`resumo` LIKE :q2 OR n.`conteudo` LIKE :q3 OR n.`slug` LIKE :q4)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
        }

        $publicada = (string) ($filtros['publicada'] ?? '');
        if ($publicada === '0' || $publicada === '1') {
            $condicoes[] = 'n.`publicada` = :publicada';
            $parametros[':publicada'] = (int) $publicada;
        }

        $autorId = (int) ($filtros['autor_id'] ?? 0);
        if ($autorId > 0) {
            $condicoes[] = 'n.`autor_id` = :autor';
            $parametros[':autor'] = $autorId;
        }

        return Database::run(
            'SELECT n.*, u.`nome` AS `autor_nome`, u.`email` AS `autor_email`
               FROM `noticias` n
               LEFT JOIN `usuarios` u ON u.`id` = n.`autor_id`
              WHERE ' . implode(' AND ', $condicoes) . '
              ORDER BY COALESCE(n.`publicada_em`, n.`created_at`) DESC, n.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT n.*, u.`nome` AS `autor_nome`, u.`email` AS `autor_email`
               FROM `noticias` n
               LEFT JOIN `usuarios` u ON u.`id` = n.`autor_id`
              WHERE n.`id` = :id AND n.`deleted_at` IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return list<array<string, mixed>> */
    public static function autores(): array
    {
        return Database::run(
            'SELECT `id`, `nome`, `email` FROM `usuarios` WHERE `ativo` = 1 ORDER BY `nome` ASC'
        )->fetchAll();
    }

    /** @return array<string, string> */
    public static function regras(): array
    {
        return [
            'titulo' => 'required|max:180',
            'resumo' => 'max:500',
            'conteudo' => 'max:10000',
            'imagem' => 'max:255',
            'autor_id' => 'integer',
            'publicada' => 'in:0,1',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $titulo = trim((string) $dados['titulo']);
        $autorId = (int) ($dados['autor_id'] ?? 0);
        if ($autorId <= 0) {
            $autorId = null;
        } elseif (Database::run('SELECT `id` FROM `usuarios` WHERE `id` = :id AND `ativo` = 1', [':id' => $autorId])->fetch() === false) {
            throw new InvalidArgumentException('Autor selecionado não está disponível.');
        }

        $resumo = trim((string) ($dados['resumo'] ?? ''));
        $resumo = $resumo === '' ? null : $resumo;
        $conteudo = trim((string) ($dados['conteudo'] ?? ''));
        $conteudo = $conteudo === '' ? null : $conteudo;
        $imagem = trim((string) ($dados['imagem'] ?? ''));
        $imagem = $imagem === '' ? null : $imagem;
        $publicada = (int) ($dados['publicada'] ?? 0) === 1 ? 1 : 0;

        $atual = $id === null ? null : self::buscar($id);
        if ($id !== null && $atual === null) {
            throw new InvalidArgumentException('Notícia não encontrada.');
        }

        $publicadaEm = null;
        if ($publicada === 1) {
            if ($atual !== null && (int) $atual['publicada'] === 1 && $atual['publicada_em'] !== null) {
                $publicadaEm = (string) $atual['publicada_em'];
            } else {
                $publicadaEm = date('Y-m-d H:i:s');
            }
        }

        $slug = self::gerarSlug($titulo, $id);
        if ($id !== null) {
            Database::run(
                'UPDATE `noticias` SET `titulo` = :titulo, `slug` = :slug, `resumo` = :resumo,
                    `conteudo` = :conteudo, `imagem` = :imagem, `publicada` = :publicada,
                    `publicada_em` = :publicada_em, `autor_id` = :autor,
                    `updated_by` = :usuario, `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    ':titulo' => $titulo,
                    ':slug' => $slug,
                    ':resumo' => $resumo,
                    ':conteudo' => $conteudo,
                    ':imagem' => $imagem,
                    ':publicada' => $publicada,
                    ':publicada_em' => $publicadaEm,
                    ':autor' => $autorId,
                    ':usuario' => Auth::id(),
                    ':id' => $id,
                ]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `noticias`
                (`titulo`, `slug`, `resumo`, `conteudo`, `imagem`, `publicada`,
                 `publicada_em`, `autor_id`, `created_by`, `updated_by`, `created_at`, `updated_at`)
             VALUES (:titulo, :slug, :resumo, :conteudo, :imagem, :publicada,
                     :publicada_em, :autor, :usuario_criacao, :usuario_atualizacao, NOW(), NOW())',
            [
                ':titulo' => $titulo,
                ':slug' => $slug,
                ':resumo' => $resumo,
                ':conteudo' => $conteudo,
                ':imagem' => $imagem,
                ':publicada' => $publicada,
                ':publicada_em' => $publicadaEm,
                ':autor' => $autorId,
                ':usuario_criacao' => Auth::id(),
                ':usuario_atualizacao' => Auth::id(),
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'UPDATE `noticias` SET `deleted_at` = NOW(), `publicada` = 0,
                `updated_by` = :usuario, `updated_at` = NOW()
              WHERE `id` = :id AND `deleted_at` IS NULL',
            [':id' => $id, ':usuario' => Auth::id()]
        );
    }

    public static function alternarPublicacao(int $id): int
    {
        $noticia = self::buscar($id);
        if ($noticia === null) {
            throw new InvalidArgumentException('Notícia não encontrada.');
        }

        $publicada = (int) $noticia['publicada'] === 1 ? 0 : 1;
        $publicadaEm = $publicada === 1 ? date('Y-m-d H:i:s') : null;
        Database::run(
            'UPDATE `noticias` SET `publicada` = :publicada, `publicada_em` = :publicada_em,
                `updated_by` = :usuario, `updated_at` = NOW()
              WHERE `id` = :id',
            [
                ':publicada' => $publicada,
                ':publicada_em' => $publicadaEm,
                ':usuario' => Auth::id(),
                ':id' => $id,
            ]
        );

        return $publicada;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`publicada` = 1) AS `publicadas`,
                    SUM(`publicada` = 0) AS `rascunhos`,
                    SUM(`imagem` IS NOT NULL AND `imagem` <> "") AS `com_imagem`
               FROM `noticias` WHERE `deleted_at` IS NULL'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'publicadas' => (int) ($linha['publicadas'] ?? 0),
            'rascunhos' => (int) ($linha['rascunhos'] ?? 0),
            'com_imagem' => (int) ($linha['com_imagem'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $noticia @return array<string, mixed> */
    public static function valoresFormulario(array $noticia): array
    {
        $valores = [];
        foreach (array_keys(self::regras()) as $campo) {
            $valores[$campo] = (string) ($noticia[$campo] ?? '');
        }
        $valores['publicada'] = (string) (int) ($noticia['publicada'] ?? 0);
        $valores['autor_id'] = (string) (int) ($noticia['autor_id'] ?? 0);

        return $valores;
    }

    private static function gerarSlug(string $titulo, ?int $id): string
    {
        $base = slugify($titulo);
        if ($base === '') {
            $base = 'noticia';
        }

        $slug = $base;
        $sufixo = 2;
        while (true) {
            $parametros = [':slug' => $slug];
            $condicao = '';
            if ($id !== null) {
                $condicao = ' AND `id` <> :id';
                $parametros[':id'] = $id;
            }
            $existe = Database::run(
                'SELECT `id` FROM `noticias` WHERE `slug` = :slug' . $condicao . ' LIMIT 1',
                $parametros
            )->fetch();
            if ($existe === false) {
                return $slug;
            }
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
    }
}
