<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class ArquivoMortoRepository
{
    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(o.`nome` LIKE :q1 OR o.`codigo` LIKE :q2 OR a.`motivo` LIKE :q3)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
        }

        $situacao = (string) ($filtros['situacao'] ?? '');
        if (in_array($situacao, ['ativo', 'licenciado', 'desligado'], true)) {
            $condicoes[] = 'o.`situacao` = :situacao';
            $parametros[':situacao'] = $situacao;
        }

        $restaurado = (string) ($filtros['restaurado'] ?? '');
        if ($restaurado === '1') {
            $condicoes[] = 'a.`restaurado_em` IS NOT NULL';
        } elseif ($restaurado === '0') {
            $condicoes[] = 'a.`restaurado_em` IS NULL';
        }

        $inicio = (string) ($filtros['data_inicio'] ?? '');
        if ($inicio !== '') {
            $condicoes[] = 'a.`data_desligamento` >= :data_inicio';
            $parametros[':data_inicio'] = $inicio;
        }

        $fim = (string) ($filtros['data_fim'] ?? '');
        if ($fim !== '') {
            $condicoes[] = 'a.`data_desligamento` <= :data_fim';
            $parametros[':data_fim'] = $fim;
        }

        return Database::run(
            'SELECT a.*, o.`codigo`, o.`nome`, o.`situacao`, o.`foto`, o.`cargo_id`, o.`congregacao_id`,
                    ca.`nome` AS `cargo_nome`, cg.`nome` AS `congregacao_nome`,
                    u.`nome` AS `responsavel_nome`, ur.`nome` AS `restaurado_por_nome`
             FROM `arquivo_morto` a
             INNER JOIN `obreiros` o ON o.`id` = a.`obreiro_id`
             LEFT JOIN `cargos_ministeriais` ca ON ca.`id` = o.`cargo_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = o.`congregacao_id`
             LEFT JOIN `usuarios` u ON u.`id` = a.`responsavel_id`
             LEFT JOIN `usuarios` ur ON ur.`id` = a.`restaurado_por_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY a.`data_desligamento` DESC, a.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT a.*, o.`codigo`, o.`nome`, o.`situacao`, o.`foto`, o.`cpf`, o.`telefone`, o.`email`,
                    ca.`nome` AS `cargo_nome`, cg.`nome` AS `congregacao_nome`, u.`nome` AS `responsavel_nome`
             FROM `arquivo_morto` a
             INNER JOIN `obreiros` o ON o.`id` = a.`obreiro_id`
             LEFT JOIN `cargos_ministeriais` ca ON ca.`id` = o.`cargo_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = o.`congregacao_id`
             LEFT JOIN `usuarios` u ON u.`id` = a.`responsavel_id`
             WHERE a.`id` = :id
             LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return array<string, mixed>|null */
    public static function buscarPorObreiro(int $obreiroId): ?array
    {
        $registro = Database::run(
            'SELECT `id`, `restaurado_em` FROM `arquivo_morto` WHERE `obreiro_id` = :obreiro LIMIT 1',
            [':obreiro' => $obreiroId]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return list<int> */
    public static function arquivadosIds(): array
    {
        $linhas = Database::run('SELECT `obreiro_id` FROM `arquivo_morto`')->fetchAll();
        $ids = [];
        foreach ($linhas as $linha) {
            $ids[] = (int) $linha['obreiro_id'];
        }

        return $ids;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    COALESCE(SUM(CASE WHEN a.`restaurado_em` IS NULL THEN 1 ELSE 0 END), 0) AS `pendentes`,
                    COALESCE(SUM(CASE WHEN a.`restaurado_em` IS NOT NULL THEN 1 ELSE 0 END), 0) AS `restaurados`,
                    COALESCE(SUM(CASE WHEN o.`situacao` = "desligado" THEN 1 ELSE 0 END), 0) AS `desligados`,
                    COALESCE(SUM(CASE WHEN o.`situacao` = "licenciado" THEN 1 ELSE 0 END), 0) AS `licenciados`
             FROM `arquivo_morto` a
             INNER JOIN `obreiros` o ON o.`id` = a.`obreiro_id`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'pendentes' => (int) ($linha['pendentes'] ?? 0),
            'restaurados' => (int) ($linha['restaurados'] ?? 0),
            'desligados' => (int) ($linha['desligados'] ?? 0),
            'licenciados' => (int) ($linha['licenciados'] ?? 0),
        ];
    }

    /** @return array<string, string> */
    public static function regras(): array
    {
        return [
            'motivo' => 'required|min:5|max:1000',
            'data_desligamento' => 'required|date',
            'situacao' => 'required|in:licenciado,desligado',
            'responsavel_id' => 'integer',
            'observacoes' => 'max:1000',
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function arquivaveis(): array
    {
        return Database::run(
            'SELECT o.`id`, o.`codigo`, o.`nome`, o.`situacao`, ca.`nome` AS `cargo_nome`, cg.`nome` AS `congregacao_nome`
             FROM `obreiros` o
             LEFT JOIN `arquivo_morto` a ON a.`obreiro_id` = o.`id`
             LEFT JOIN `cargos_ministeriais` ca ON ca.`id` = o.`cargo_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = o.`congregacao_id`
             WHERE o.`deleted_at` IS NULL AND a.`id` IS NULL
             ORDER BY o.`nome` ASC'
        )->fetchAll();
    }

    /** @param array<string, mixed> $dados */
    public static function arquivar(int $obreiroId, array $dados): int
    {
        $obreiro = ObreiroRepository::buscar($obreiroId, true);
        if ($obreiro === null) {
            throw new InvalidArgumentException('Obreiro não encontrado.');
        }
        if (self::buscarPorObreiro($obreiroId) !== null) {
            throw new InvalidArgumentException('Este obreiro já possui registro no Arquivo Morto.');
        }

        $responsavel = (int) ($dados['responsavel_id'] ?? 0);
        if ($responsavel <= 0) {
            $responsavel = Auth::id();
        }

        Database::run(
            'INSERT INTO `arquivo_morto` (`obreiro_id`, `motivo`, `data_desligamento`, `responsavel_id`, `observacoes`, `created_at`)
             VALUES (:obreiro, :motivo, :data, :responsavel, :observacoes, NOW())',
            [
                ':obreiro' => $obreiroId,
                ':motivo' => (string) $dados['motivo'],
                ':data' => (string) $dados['data_desligamento'],
                ':responsavel' => $responsavel,
                ':observacoes' => (string) ($dados['observacoes'] ?? ''),
            ]
        );
        $id = (int) Database::connection()->lastInsertId();

        $situacao = (string) $dados['situacao'];
        Database::run(
            'UPDATE `obreiros` SET `situacao` = :situacao, `updated_by` = :usuario WHERE `id` = :id',
            [':situacao' => $situacao, ':usuario' => Auth::id(), ':id' => $obreiroId]
        );

        ObreiroRepository::registrarHistorico([
            'obreiro_id' => $obreiroId,
            'tipo' => 'situacao',
            'situacao_anterior' => (string) $obreiro['situacao'],
            'situacao_nova' => $situacao,
            'descricao' => 'Arquivado no Arquivo Morto. Motivo: ' . (string) $dados['motivo'],
        ]);

        return $id;
    }

    public static function restaurar(int $id): void
    {
        $registro = self::buscar($id);
        if ($registro === null) {
            throw new InvalidArgumentException('Registro do Arquivo Morto não encontrado.');
        }
        if ($registro['restaurado_em'] !== null) {
            throw new InvalidArgumentException('Este registro já foi restaurado.');
        }

        Database::run(
            'UPDATE `arquivo_morto` SET `restaurado_em` = NOW(), `restaurado_por_id` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => $id]
        );
        Database::run(
            'UPDATE `obreiros` SET `situacao` = "ativo", `updated_by` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => (int) $registro['obreiro_id']]
        );

        ObreiroRepository::registrarHistorico([
            'obreiro_id' => (int) $registro['obreiro_id'],
            'tipo' => 'situacao',
            'situacao_anterior' => (string) $registro['situacao'],
            'situacao_nova' => 'ativo',
            'descricao' => 'Restaurado do Arquivo Morto.',
        ]);
    }
}
