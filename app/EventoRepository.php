<?php
declare(strict_types=1);

namespace App;

use DateTime;
use InvalidArgumentException;

final class EventoRepository
{
    public const TIPOS = [
        'culto' => 'Culto',
        'congresso' => 'Congresso',
        'festividade' => 'Festividade',
        'campanha' => 'Campanha',
        'conferencia' => 'Conferência',
        'reuniao' => 'Reunião',
        'escola_biblica' => 'Escola Bíblica',
        'especial' => 'Evento especial',
    ];

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['e.`deleted_at` IS NULL'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(e.`titulo` LIKE :q1 OR e.`descricao` LIKE :q2 OR e.`local` LIKE :q3 OR e.`slug` LIKE :q4)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
        }

        $tipo = (string) ($filtros['tipo'] ?? '');
        if (isset(self::TIPOS[$tipo])) {
            $condicoes[] = 'e.`tipo` = :tipo';
            $parametros[':tipo'] = $tipo;
        }

        $publicado = (string) ($filtros['publicado'] ?? '');
        if ($publicado === '0' || $publicado === '1') {
            $condicoes[] = 'e.`publicado` = :publicado';
            $parametros[':publicado'] = (int) $publicado;
        }

        $destaque = (string) ($filtros['destaque'] ?? '');
        if ($destaque === '0' || $destaque === '1') {
            $condicoes[] = 'e.`destaque` = :destaque';
            $parametros[':destaque'] = (int) $destaque;
        }

        $periodo = (string) ($filtros['periodo'] ?? '');
        if ($periodo === 'futuros') {
            $condicoes[] = 'e.`data_inicio` >= NOW()';
        } elseif ($periodo === 'passados') {
            $condicoes[] = 'e.`data_inicio` < NOW()';
        }

        return Database::run(
            'SELECT e.*, u.`nome` AS `responsavel_nome`, u.`email` AS `responsavel_email`
              FROM `eventos` e
              LEFT JOIN `usuarios` u ON u.`id` = e.`responsavel_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY e.`data_inicio` ASC, e.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT e.*, u.`nome` AS `responsavel_nome`, u.`email` AS `responsavel_email`
               FROM `eventos` e
               LEFT JOIN `usuarios` u ON u.`id` = e.`responsavel_id`
              WHERE e.`id` = :id AND e.`deleted_at` IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return list<array<string, mixed>> */
    public static function responsaveis(): array
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
            'descricao' => 'max:2000',
            'tipo' => 'required|in:' . implode(',', array_keys(self::TIPOS)),
            'data_inicio' => 'required|datetime',
            'data_fim' => 'datetime',
            'horario' => 'max:50',
            'local' => 'max:200',
            'responsavel_id' => 'integer',
            'foto' => 'max:255',
            'publicado' => 'in:0,1',
            'destaque' => 'in:0,1',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $titulo = trim((string) $dados['titulo']);
        $tipo = (string) $dados['tipo'];
        if (!isset(self::TIPOS[$tipo])) {
            throw new InvalidArgumentException('Tipo de evento inválido.');
        }

        $inicio = self::dataHora((string) $dados['data_inicio']);
        $fim = trim((string) ($dados['data_fim'] ?? ''));
        $fim = $fim === '' ? null : self::dataHora($fim);
        if ($fim !== null && new DateTime($fim) < new DateTime($inicio)) {
            throw new InvalidArgumentException('A data de término deve ser posterior ao início.');
        }

        $responsavelId = (int) ($dados['responsavel_id'] ?? 0);
        if ($responsavelId <= 0) {
            $responsavelId = null;
        } elseif (Database::run('SELECT `id` FROM `usuarios` WHERE `id` = :id AND `ativo` = 1', [':id' => $responsavelId])->fetch() === false) {
            throw new InvalidArgumentException('Responsável selecionado não está disponível.');
        }

        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;
        $horario = trim((string) ($dados['horario'] ?? ''));
        $horario = $horario === '' ? null : $horario;
        $local = trim((string) ($dados['local'] ?? ''));
        $local = $local === '' ? null : $local;
        $foto = trim((string) ($dados['foto'] ?? ''));
        $foto = $foto === '' ? null : $foto;
        $publicado = (int) ($dados['publicado'] ?? 0) === 1 ? 1 : 0;
        $destaque = (int) ($dados['destaque'] ?? 0) === 1 ? 1 : 0;

        $atual = $id === null ? null : self::buscar($id);
        if ($id !== null && $atual === null) {
            throw new InvalidArgumentException('Evento não encontrado.');
        }

        $publicadoEm = null;
        if ($publicado === 1) {
            if ($atual !== null && (int) $atual['publicado'] === 1 && $atual['publicado_em'] !== null) {
                $publicadoEm = (string) $atual['publicado_em'];
            } else {
                $publicadoEm = date('Y-m-d H:i:s');
            }
        }

        $slug = self::gerarSlug($titulo, $id);
        if ($id !== null) {
            Database::run(
                'UPDATE `eventos` SET `titulo` = :titulo, `slug` = :slug, `descricao` = :descricao,
                    `tipo` = :tipo, `data_inicio` = :inicio, `data_fim` = :fim, `horario` = :horario,
                    `local` = :local, `responsavel_id` = :responsavel, `foto` = :foto,
                    `publicado` = :publicado, `publicado_em` = :publicado_em, `destaque` = :destaque,
                    `updated_by` = :usuario, `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    ':titulo' => $titulo,
                    ':slug' => $slug,
                    ':descricao' => $descricao,
                    ':tipo' => $tipo,
                    ':inicio' => $inicio,
                    ':fim' => $fim,
                    ':horario' => $horario,
                    ':local' => $local,
                    ':responsavel' => $responsavelId,
                    ':foto' => $foto,
                    ':publicado' => $publicado,
                    ':publicado_em' => $publicadoEm,
                    ':destaque' => $destaque,
                    ':usuario' => Auth::id(),
                    ':id' => $id,
                ]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `eventos`
                (`titulo`, `slug`, `descricao`, `tipo`, `data_inicio`, `data_fim`, `horario`,
                 `local`, `responsavel_id`, `foto`, `publicado`, `publicado_em`, `destaque`,
                 `created_by`, `updated_by`, `created_at`, `updated_at`)
             VALUES (:titulo, :slug, :descricao, :tipo, :inicio, :fim, :horario, :local,
                     :responsavel, :foto, :publicado, :publicado_em, :destaque,
                     :usuario_criacao, :usuario_atualizacao, NOW(), NOW())',
            [
                ':titulo' => $titulo,
                ':slug' => $slug,
                ':descricao' => $descricao,
                ':tipo' => $tipo,
                ':inicio' => $inicio,
                ':fim' => $fim,
                ':horario' => $horario,
                ':local' => $local,
                ':responsavel' => $responsavelId,
                ':foto' => $foto,
                ':publicado' => $publicado,
                ':publicado_em' => $publicadoEm,
                ':destaque' => $destaque,
                ':usuario_criacao' => Auth::id(),
                ':usuario_atualizacao' => Auth::id(),
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'UPDATE `eventos` SET `deleted_at` = NOW(), `publicado` = 0, `updated_by` = :usuario, `updated_at` = NOW()
              WHERE `id` = :id AND `deleted_at` IS NULL',
            [':id' => $id, ':usuario' => Auth::id()]
        );
    }

    public static function alternarPublicacao(int $id): int
    {
        $evento = self::buscar($id);
        if ($evento === null) {
            throw new InvalidArgumentException('Evento não encontrado.');
        }

        $publicado = (int) $evento['publicado'] === 1 ? 0 : 1;
        $publicadoEm = $publicado === 1 ? date('Y-m-d H:i:s') : null;
        Database::run(
            'UPDATE `eventos` SET `publicado` = :publicado, `publicado_em` = :publicado_em,
                `updated_by` = :usuario, `updated_at` = NOW()
              WHERE `id` = :id',
            [
                ':publicado' => $publicado,
                ':publicado_em' => $publicadoEm,
                ':usuario' => Auth::id(),
                ':id' => $id,
            ]
        );

        return $publicado;
    }

    public static function alternarDestaque(int $id): int
    {
        $evento = self::buscar($id);
        if ($evento === null) {
            throw new InvalidArgumentException('Evento não encontrado.');
        }

        $destaque = (int) $evento['destaque'] === 1 ? 0 : 1;
        Database::run(
            'UPDATE `eventos` SET `destaque` = :destaque, `updated_by` = :usuario, `updated_at` = NOW()
              WHERE `id` = :id',
            [
                ':destaque' => $destaque,
                ':usuario' => Auth::id(),
                ':id' => $id,
            ]
        );

        return $destaque;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`publicado` = 1) AS `publicados`,
                    SUM(`publicado` = 0) AS `rascunhos`,
                    SUM(`destaque` = 1) AS `destaques`,
                    SUM(`data_inicio` >= NOW()) AS `futuros`
               FROM `eventos` WHERE `deleted_at` IS NULL'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'publicados' => (int) ($linha['publicados'] ?? 0),
            'rascunhos' => (int) ($linha['rascunhos'] ?? 0),
            'destaques' => (int) ($linha['destaques'] ?? 0),
            'futuros' => (int) ($linha['futuros'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $evento @return array<string, mixed> */
    public static function valoresFormulario(array $evento): array
    {
        $valores = [];
        foreach (array_keys(self::regras()) as $campo) {
            $valores[$campo] = (string) ($evento[$campo] ?? '');
        }
        $valores['data_inicio'] = self::valorDataHora((string) ($evento['data_inicio'] ?? ''));
        $valores['data_fim'] = self::valorDataHora((string) ($evento['data_fim'] ?? ''));
        $valores['publicado'] = (string) (int) ($evento['publicado'] ?? 0);
        $valores['destaque'] = (string) (int) ($evento['destaque'] ?? 0);
        $valores['responsavel_id'] = (string) (int) ($evento['responsavel_id'] ?? 0);

        return $valores;
    }

    public static function rotuloTipo(string $tipo): string
    {
        return self::TIPOS[$tipo] ?? 'Evento';
    }

    private static function gerarSlug(string $titulo, ?int $id): string
    {
        $base = slugify($titulo);
        if ($base === '') {
            $base = 'evento';
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
                'SELECT `id` FROM `eventos` WHERE `slug` = :slug' . $condicao . ' LIMIT 1',
                $parametros
            )->fetch();
            if ($existe === false) {
                return $slug;
            }
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
    }

    private static function dataHora(string $valor): string
    {
        return str_replace('T', ' ', substr(trim($valor), 0, 16)) . ':00';
    }

    private static function valorDataHora(string $valor): string
    {
        if ($valor === '') {
            return '';
        }
        return str_replace(' ', 'T', substr($valor, 0, 16));
    }
}
