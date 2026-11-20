<?php
declare(strict_types=1);

namespace App;

use DateTime;
use InvalidArgumentException;

final class FinanceiroRepository
{
    public const TIPOS = ['entrada', 'saida'];
    public const ORIGENS = ['geral', 'dizimo', 'oferta'];
    public const FORMAS = ['dinheiro', 'pix', 'boleto', 'cartao', 'transferencia', 'cheque', 'outro'];

    public static function listar(array $filtros = []): array
    {
        [$where, $parametros] = self::condicoes($filtros, 'm');

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $where .= ' AND (m.`descricao` LIKE :q1 OR cat.`nome` LIKE :q2 OR cg.`nome` LIKE :q3'
                . ' OR dizimista.`nome` LIKE :q4 OR ofertante.`nome` LIKE :q5)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
            $parametros[':q5'] = '%' . $busca . '%';
        }

        $origem = (string) ($filtros['origem'] ?? '');
        if ($origem === 'dizimo') {
            $where .= ' AND d.`id` IS NOT NULL';
        } elseif ($origem === 'oferta') {
            $where .= ' AND o.`id` IS NOT NULL';
        } elseif ($origem === 'geral') {
            $where .= ' AND d.`id` IS NULL AND o.`id` IS NULL';
        }

        $sql = 'SELECT m.*, cat.`nome` AS `categoria_nome`, cg.`nome` AS `congregacao_nome`,
                       dizimista.`nome` AS `dizimista_nome`, ofertante.`nome` AS `ofertante_nome`,
                       d.`membro_id` AS `dizimista_id`, d.`referencia_mes` AS `referencia_dizimo`,
                       o.`ofertante_id`, o.`tipo_oferta`,
                       resp.`nome` AS `responsavel_nome`,
                       CASE WHEN d.`id` IS NOT NULL THEN "dizimo"
                            WHEN o.`id` IS NOT NULL THEN "oferta"
                            ELSE "geral" END AS `origem`
                FROM `movimentacoes` m
                INNER JOIN `categorias_financeiras` cat ON cat.`id` = m.`categoria_id`
                LEFT JOIN `congregacoes` cg ON cg.`id` = m.`congregacao_id`
                LEFT JOIN `usuarios` resp ON resp.`id` = m.`responsavel_id`
                LEFT JOIN `dizimos` d ON d.`movimentacao_id` = m.`id`
                LEFT JOIN `ofertas` o ON o.`movimentacao_id` = m.`id`
                LEFT JOIN `membros` dizimista ON dizimista.`id` = d.`membro_id`
                LEFT JOIN `membros` ofertante ON ofertante.`id` = o.`ofertante_id`
                WHERE ' . $where . ' ORDER BY m.`data_movimentacao` DESC, m.`id` DESC';

        return Database::run($sql, $parametros)->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT m.*, cat.`nome` AS `categoria_nome`, cg.`nome` AS `congregacao_nome`,
                    d.`membro_id` AS `dizimista_id`, d.`referencia_mes` AS `referencia_dizimo`,
                    o.`ofertante_id`, o.`tipo_oferta`
             FROM `movimentacoes` m
             INNER JOIN `categorias_financeiras` cat ON cat.`id` = m.`categoria_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = m.`congregacao_id`
             LEFT JOIN `dizimos` d ON d.`movimentacao_id` = m.`id`
             LEFT JOIN `ofertas` o ON o.`movimentacao_id` = m.`id`
             WHERE m.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function origem(int $id): string
    {
        $contagens = Database::run(
            'SELECT (SELECT COUNT(*) FROM `dizimos` WHERE `movimentacao_id` = :id_dizimo) AS `dizimos`,
                    (SELECT COUNT(*) FROM `ofertas` WHERE `movimentacao_id` = :id_oferta) AS `ofertas`',
            [':id_dizimo' => $id, ':id_oferta' => $id]
        )->fetch();

        if ((int) ($contagens['dizimos'] ?? 0) > 0) {
            return 'dizimo';
        }
        if ((int) ($contagens['ofertas'] ?? 0) > 0) {
            return 'oferta';
        }

        return 'geral';
    }

    public static function estatisticas(array $filtros = []): array
    {
        [$where, $parametros] = self::condicoes($filtros, 'm');
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    COALESCE(SUM(CASE WHEN m.`tipo` = "entrada" THEN m.`valor` ELSE 0 END), 0) AS `entradas`,
                    COALESCE(SUM(CASE WHEN m.`tipo` = "saida" THEN m.`valor` ELSE 0 END), 0) AS `saidas`,
                    COALESCE(SUM(CASE WHEN m.`fechado` = 1 THEN 1 ELSE 0 END), 0) AS `fechadas`
             FROM `movimentacoes` m WHERE ' . $where,
            $parametros
        )->fetch();

        $entradas = (float) ($linha['entradas'] ?? 0);
        $saidas = (float) ($linha['saidas'] ?? 0);

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
            'fechadas' => (int) ($linha['fechadas'] ?? 0),
        ];
    }

    public static function relatorio(array $filtros = []): array
    {
        [$where, $parametros] = self::condicoes($filtros, 'm');

        return Database::run(
            'SELECT m.`tipo`, cat.`id` AS `categoria_id`, cat.`nome` AS `categoria_nome`,
                    COUNT(*) AS `quantidade`, SUM(m.`valor`) AS `total`
             FROM `movimentacoes` m
             INNER JOIN `categorias_financeiras` cat ON cat.`id` = m.`categoria_id`
             WHERE ' . $where . '
             GROUP BY m.`tipo`, cat.`id`, cat.`nome`
             ORDER BY m.`tipo` ASC, `total` DESC',
            $parametros
        )->fetchAll();
    }

    public static function categorias(string $tipo = ''): array
    {
        $parametros = [];
        $where = '`ativo` = 1';
        if (in_array($tipo, self::TIPOS, true)) {
            $where .= ' AND `tipo` = :tipo';
            $parametros[':tipo'] = $tipo;
        }

        return Database::run(
            'SELECT `id`, `nome`, `tipo` FROM `categorias_financeiras` WHERE ' . $where . ' ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    public static function categoriasParaGestao(): array
    {
        return Database::run('SELECT * FROM `categorias_financeiras` ORDER BY `tipo` ASC, `nome` ASC')->fetchAll();
    }

    public static function congregacoes(): array
    {
        return Database::run('SELECT `id`, `codigo`, `nome` FROM `congregacoes` ORDER BY `nome` ASC')->fetchAll();
    }

    public static function membros(): array
    {
        return Database::run('SELECT `id`, `codigo`, `nome` FROM `membros` WHERE `deleted_at` IS NULL ORDER BY `nome` ASC')->fetchAll();
    }

    public static function responsaveis(): array
    {
        return Database::run('SELECT `id`, `nome` FROM `usuarios` WHERE `ativo` = 1 ORDER BY `nome` ASC')->fetchAll();
    }

    public static function competencias(): array
    {
        $linhas = Database::run('SELECT DISTINCT `competencia` FROM `movimentacoes` ORDER BY `competencia` DESC')->fetchAll();
        $competencias = [];
        foreach ($linhas as $linha) {
            $competencias[] = (string) $linha['competencia'];
        }
        $atual = date('Y-m');
        if (!in_array($atual, $competencias, true)) {
            $competencias[] = $atual;
        }
        rsort($competencias);

        return $competencias;
    }

    public static function regras(): array
    {
        return [
            'tipo' => 'required|in:' . implode(',', self::TIPOS),
            'origem' => 'required|in:' . implode(',', self::ORIGENS),
            'categoria_id' => 'required|integer',
            'congregacao_id' => 'integer',
            'descricao' => 'required|max:255',
            'valor' => 'required|numeric|positive',
            'data_movimentacao' => 'required|date',
            'forma_pagamento' => 'required|in:' . implode(',', self::FORMAS),
            'referencia_mes' => 'month',
            'competencia' => 'required|month',
            'membro_id' => 'integer',
            'ofertante_id' => 'integer',
            'tipo_oferta' => 'max:100',
            'observacoes' => 'max:1000',
        ];
    }

    public static function salvar(?int $id, array $dados): int
    {
        $tipo = (string) $dados['tipo'];
        $origem = (string) $dados['origem'];
        $referencia = trim((string) ($dados['referencia_mes'] ?? ''));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));

        $valores = [
            ':tipo' => $tipo,
            ':categoria_id' => (int) $dados['categoria_id'],
            ':congregacao_id' => (int) ($dados['congregacao_id'] ?? 0) > 0 ? (int) $dados['congregacao_id'] : null,
            ':descricao' => (string) $dados['descricao'],
            ':valor' => number_format((float) $dados['valor'], 2, '.', ''),
            ':data_movimentacao' => (string) $dados['data_movimentacao'],
            ':forma_pagamento' => (string) $dados['forma_pagamento'],
            ':referencia_mes' => $referencia === '' ? null : $referencia,
            ':competencia' => self::normalizarCompetencia((string) $dados['competencia']),
            ':responsavel_id' => Auth::id(),
            ':observacoes' => $observacoes === '' ? null : $observacoes,
        ];

        if ($id === null) {
            $valores[':created_by'] = Auth::id();
            $valores[':updated_by'] = Auth::id();
            Database::run(
                'INSERT INTO `movimentacoes`
                    (`tipo`, `categoria_id`, `congregacao_id`, `descricao`, `valor`, `data_movimentacao`,
                     `forma_pagamento`, `referencia_mes`, `competencia`, `responsavel_id`, `observacoes`,
                     `created_by`, `updated_by`)
                 VALUES
                    (:tipo, :categoria_id, :congregacao_id, :descricao, :valor, :data_movimentacao,
                     :forma_pagamento, :referencia_mes, :competencia, :responsavel_id, :observacoes,
                     :created_by, :updated_by)',
                $valores
            );
            $id = (int) Database::connection()->lastInsertId();
        } else {
            $valores[':usuario'] = Auth::id();
            $valores[':id'] = $id;
            Database::run(
                'UPDATE `movimentacoes` SET
                    `tipo` = :tipo, `categoria_id` = :categoria_id, `congregacao_id` = :congregacao_id,
                    `descricao` = :descricao, `valor` = :valor, `data_movimentacao` = :data_movimentacao,
                    `forma_pagamento` = :forma_pagamento, `referencia_mes` = :referencia_mes,
                    `competencia` = :competencia, `responsavel_id` = :responsavel_id,
                    `observacoes` = :observacoes, `updated_by` = :usuario
                 WHERE `id` = :id',
                $valores
            );
        }

        Database::run('DELETE FROM `dizimos` WHERE `movimentacao_id` = :id', [':id' => $id]);
        Database::run('DELETE FROM `ofertas` WHERE `movimentacao_id` = :id', [':id' => $id]);

        if ($origem === 'dizimo') {
            Database::run(
                'INSERT INTO `dizimos` (`movimentacao_id`, `membro_id`, `congregacao_id`, `referencia_mes`)
                 VALUES (:movimentacao, :membro, :congregacao, :referencia)',
                [
                    ':movimentacao' => $id,
                    ':membro' => (int) ($dados['membro_id'] ?? 0) > 0 ? (int) $dados['membro_id'] : null,
                    ':congregacao' => $valores[':congregacao_id'],
                    ':referencia' => $referencia === '' ? $valores[':competencia'] : $referencia,
                ]
            );
        } elseif ($origem === 'oferta') {
            $tipoOferta = trim((string) ($dados['tipo_oferta'] ?? ''));
            Database::run(
                'INSERT INTO `ofertas` (`movimentacao_id`, `ofertante_id`, `tipo_oferta`, `congregacao_id`)
                 VALUES (:movimentacao, :ofertante, :tipo_oferta, :congregacao)',
                [
                    ':movimentacao' => $id,
                    ':ofertante' => (int) ($dados['ofertante_id'] ?? 0) > 0 ? (int) $dados['ofertante_id'] : null,
                    ':tipo_oferta' => $tipoOferta === '' ? null : $tipoOferta,
                    ':congregacao' => $valores[':congregacao_id'],
                ]
            );
        }

        return $id;
    }

    public static function excluir(int $id): void
    {
        Database::run('DELETE FROM `movimentacoes` WHERE `id` = :id', [':id' => $id]);
    }

    public static function fechamentos(): array
    {
        return Database::run(
            'SELECT f.*, cg.`nome` AS `congregacao_nome`, u.`nome` AS `fechado_por_nome`
             FROM `fechamentos_mensais` f
             LEFT JOIN `congregacoes` cg ON cg.`id` = f.`congregacao_id`
             LEFT JOIN `usuarios` u ON u.`id` = f.`fechado_por_id`
             ORDER BY f.`competencia` DESC, f.`congregacao_id` ASC'
        )->fetchAll();
    }

    public static function fechar(string $competencia, int $congregacaoId, string $observacoes): int
    {
        $competencia = self::normalizarCompetencia($competencia);
        [$where, $parametros] = self::condicoes([
            'competencia' => $competencia,
            'congregacao_id' => $congregacaoId,
        ], 'm');

        $totais = Database::run(
            'SELECT COALESCE(SUM(CASE WHEN m.`tipo` = "entrada" THEN m.`valor` ELSE 0 END), 0) AS `entradas`,
                    COALESCE(SUM(CASE WHEN m.`tipo` = "saida" THEN m.`valor` ELSE 0 END), 0) AS `saidas`
             FROM `movimentacoes` m WHERE ' . $where,
            $parametros
        )->fetch();

        $entradas = (float) ($totais['entradas'] ?? 0);
        $saidas = (float) ($totais['saidas'] ?? 0);
        $observacoes = trim($observacoes);

        $existente = Database::run(
            'SELECT `id` FROM `fechamentos_mensais` WHERE `competencia` = :competencia AND `congregacao_id` = :congregacao LIMIT 1',
            [':competencia' => $competencia, ':congregacao' => $congregacaoId]
        )->fetch();

        if ($existente !== false) {
            $id = (int) $existente['id'];
            Database::run(
                'UPDATE `fechamentos_mensais` SET `total_entradas` = :entradas, `total_saidas` = :saidas,
                    `saldo` = :saldo, `fechado_por_id` = :usuario, `fechado_em` = NOW(), `observacoes` = :observacoes
                 WHERE `id` = :id',
                [
                    ':entradas' => $entradas,
                    ':saidas' => $saidas,
                    ':saldo' => $entradas - $saidas,
                    ':usuario' => Auth::id(),
                    ':observacoes' => $observacoes === '' ? null : $observacoes,
                    ':id' => $id,
                ]
            );
        } else {
            Database::run(
                'INSERT INTO `fechamentos_mensais`
                    (`competencia`, `congregacao_id`, `total_entradas`, `total_saidas`, `saldo`, `fechado_por_id`, `observacoes`)
                 VALUES (:competencia, :congregacao, :entradas, :saidas, :saldo, :usuario, :observacoes)',
                [
                    ':competencia' => $competencia,
                    ':congregacao' => $congregacaoId,
                    ':entradas' => $entradas,
                    ':saidas' => $saidas,
                    ':saldo' => $entradas - $saidas,
                    ':usuario' => Auth::id(),
                    ':observacoes' => $observacoes === '' ? null : $observacoes,
                ]
            );
            $id = (int) Database::connection()->lastInsertId();
        }

        [$whereMovimentacoes, $parametrosMovimentacoes] = self::condicoes([
            'competencia' => $competencia,
            'congregacao_id' => $congregacaoId,
        ], '');
        Database::run('UPDATE `movimentacoes` SET `fechado` = 1 WHERE ' . $whereMovimentacoes, $parametrosMovimentacoes);

        return $id;
    }

    public static function regrasCategoria(): array
    {
        return [
            'nome' => 'required|max:100',
            'tipo' => 'required|in:' . implode(',', self::TIPOS),
            'descricao' => 'max:255',
            'ativo' => 'in:0,1',
        ];
    }

    public static function salvarCategoria(?int $id, array $dados): int
    {
        $nome = trim((string) $dados['nome']);
        $tipo = (string) $dados['tipo'];
        $duplicado = Database::run(
            'SELECT `id` FROM `categorias_financeiras` WHERE `nome` = :nome AND `tipo` = :tipo'
                . ($id === null ? '' : ' AND `id` <> :id'),
            $id === null
                ? [':nome' => $nome, ':tipo' => $tipo]
                : [':nome' => $nome, ':tipo' => $tipo, ':id' => $id]
        )->fetch();

        if ($duplicado !== false) {
            throw new InvalidArgumentException('Já existe uma categoria com este nome e tipo.');
        }

        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $ativo = (string) ($dados['ativo'] ?? '1') === '0' ? 0 : 1;

        if ($id === null) {
            Database::run(
                'INSERT INTO `categorias_financeiras` (`nome`, `tipo`, `descricao`, `ativo`)
                 VALUES (:nome, :tipo, :descricao, :ativo)',
                [
                    ':nome' => $nome,
                    ':tipo' => $tipo,
                    ':descricao' => $descricao === '' ? null : $descricao,
                    ':ativo' => $ativo,
                ]
            );
            return (int) Database::connection()->lastInsertId();
        }

        Database::run(
            'UPDATE `categorias_financeiras` SET `nome` = :nome, `tipo` = :tipo,
                `descricao` = :descricao, `ativo` = :ativo WHERE `id` = :id',
            [
                ':nome' => $nome,
                ':tipo' => $tipo,
                ':descricao' => $descricao === '' ? null : $descricao,
                ':ativo' => $ativo,
                ':id' => $id,
            ]
        );

        return $id;
    }

    public static function excluirCategoria(int $id): void
    {
        $vinculos = Database::run(
            'SELECT COUNT(*) AS `total` FROM `movimentacoes` WHERE `categoria_id` = :id',
            [':id' => $id]
        )->fetch();
        if ((int) ($vinculos['total'] ?? 0) > 0) {
            throw new InvalidArgumentException('Não é possível excluir uma categoria com movimentações vinculadas.');
        }

        Database::run('DELETE FROM `categorias_financeiras` WHERE `id` = :id', [':id' => $id]);
    }

    private static function condicoes(array $filtros, string $alias): array
    {
        $condicoes = ['1=1'];
        $parametros = [];
        $prefixo = $alias === '' ? '' : $alias . '.';

        $tipo = (string) ($filtros['tipo'] ?? '');
        if (in_array($tipo, self::TIPOS, true)) {
            $condicoes[] = $prefixo . '`tipo` = :tipo';
            $parametros[':tipo'] = $tipo;
        }

        $categoriaId = (int) ($filtros['categoria_id'] ?? 0);
        if ($categoriaId > 0) {
            $condicoes[] = $prefixo . '`categoria_id` = :categoria';
            $parametros[':categoria'] = $categoriaId;
        }

        $congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
        if ($congregacaoId > 0) {
            $condicoes[] = $prefixo . '`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $congregacaoId;
        }

        $competencia = trim((string) ($filtros['competencia'] ?? ''));
        if (self::mesValido($competencia)) {
            $condicoes[] = $prefixo . '`competencia` = :competencia';
            $parametros[':competencia'] = $competencia;
        }

        $fechado = (string) ($filtros['fechado'] ?? '');
        if ($fechado === '0' || $fechado === '1') {
            $condicoes[] = $prefixo . '`fechado` = :fechado';
            $parametros[':fechado'] = (int) $fechado;
        }

        return [implode(' AND ', $condicoes), $parametros];
    }

    private static function normalizarCompetencia(string $valor): string
    {
        $valor = trim($valor);
        return self::mesValido($valor) ? $valor : date('Y-m');
    }

    private static function mesValido(string $valor): bool
    {
        $mes = DateTime::createFromFormat('!Y-m', $valor);
        return $mes !== false && $mes->format('Y-m') === $valor;
    }
}
