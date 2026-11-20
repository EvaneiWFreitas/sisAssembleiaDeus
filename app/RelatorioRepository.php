<?php
declare(strict_types=1);

namespace App;

final class RelatorioRepository
{
    public static function normalizarFiltros(array $filtros): array
    {
        $competencia = trim((string) ($filtros['competencia'] ?? ''));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia) !== 1) {
            $competencia = '';
        }

        $congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
        if ($congregacaoId <= 0) {
            $congregacaoId = 0;
        }

        $modulo = trim((string) ($filtros['modulo'] ?? ''));
        if ($modulo !== '' && preg_match('/^[a-z_]+$/', $modulo) !== 1) {
            $modulo = '';
        }

        return [
            'competencia' => $competencia,
            'congregacao_id' => $congregacaoId,
            'modulo' => $modulo,
        ];
    }

    public static function congregacoes(): array
    {
        return FinanceiroRepository::congregacoes();
    }

    public static function competencias(): array
    {
        return FinanceiroRepository::competencias();
    }

    public static function modulos(): array
    {
        $linhas = Database::run('SELECT DISTINCT `modulo` FROM `auditoria` ORDER BY `modulo` ASC')->fetchAll();
        $modulos = [];
        foreach ($linhas as $linha) {
            $modulos[] = (string) $linha['modulo'];
        }

        return $modulos;
    }

    public static function dados(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $financeiro = self::resumoFinanceiro($filtros);
        $cadastros = self::cadastros($filtros);
        $conteudo = self::conteudo($filtros);
        $auditoria = self::auditoria($filtros);

        return [
            'filtros' => $filtros,
            'secoes' => [
                'Financeiro' => [
                    'Lançamentos' => $financeiro['total'],
                    'Entradas' => $financeiro['entradas'],
                    'Saídas' => $financeiro['saidas'],
                    'Saldo' => $financeiro['saldo'],
                    'Categorias com lançamentos' => $financeiro['categorias'],
                ],
                'Cadastros' => $cadastros,
                'Conteúdo e atendimento' => $conteudo,
                'Auditoria' => $auditoria['resumo'],
            ],
            'categorias' => self::porCategoria($filtros),
            'modulos' => $auditoria['modulos'],
        ];
    }

    public static function resumoFinanceiro(array $filtros): array
    {
        [$where, $parametros] = self::condicaoFinanceira($filtros);
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    COALESCE(SUM(CASE WHEN m.`tipo` = "entrada" THEN m.`valor` ELSE 0 END), 0) AS `entradas`,
                    COALESCE(SUM(CASE WHEN m.`tipo` = "saida" THEN m.`valor` ELSE 0 END), 0) AS `saidas`,
                    COUNT(DISTINCT m.`categoria_id`) AS `categorias`
               FROM `movimentacoes` m
              WHERE ' . $where,
            $parametros
        )->fetch();

        $entradas = (float) ($linha['entradas'] ?? 0);
        $saidas = (float) ($linha['saidas'] ?? 0);

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
            'categorias' => (int) ($linha['categorias'] ?? 0),
        ];
    }

    public static function porCategoria(array $filtros): array
    {
        [$where, $parametros] = self::condicaoFinanceira($filtros);

        return Database::run(
            'SELECT cat.`nome` AS `categoria`, m.`tipo`, COUNT(*) AS `quantidade`, SUM(m.`valor`) AS `total`
               FROM `movimentacoes` m
              INNER JOIN `categorias_financeiras` cat ON cat.`id` = m.`categoria_id`
              WHERE ' . $where . '
              GROUP BY cat.`id`, cat.`nome`, m.`tipo`
              ORDER BY `total` DESC',
            $parametros
        )->fetchAll();
    }

    public static function cadastros(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $parametros = [];
        $condicao = 'm.`deleted_at` IS NULL';
        if ($filtros['congregacao_id'] > 0) {
            $condicao .= ' AND m.`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $filtros['congregacao_id'];
        }

        $membros = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(m.`situacao` = "ativo") AS `ativos`,
                    SUM(m.`situacao` = "inativo") AS `inativos`
               FROM `membros` m WHERE ' . $condicao,
            $parametros
        )->fetch();

        $parametrosObreiros = [];
        $condicaoObreiros = 'o.`deleted_at` IS NULL';
        if ($filtros['congregacao_id'] > 0) {
            $condicaoObreiros .= ' AND o.`congregacao_id` = :congregacao';
            $parametrosObreiros[':congregacao'] = $filtros['congregacao_id'];
        }
        $obreiros = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(o.`situacao` = "ativo") AS `ativos`,
                    SUM(o.`situacao` = "licenciado") AS `licenciados`
               FROM `obreiros` o WHERE ' . $condicaoObreiros,
            $parametrosObreiros
        )->fetch();

        return [
            'Membros cadastrados' => (int) ($membros['total'] ?? 0),
            'Membros ativos' => (int) ($membros['ativos'] ?? 0),
            'Membros inativos' => (int) ($membros['inativos'] ?? 0),
            'Obreiros cadastrados' => (int) ($obreiros['total'] ?? 0),
            'Obreiros ativos' => (int) ($obreiros['ativos'] ?? 0),
            'Obreiros licenciados' => (int) ($obreiros['licenciados'] ?? 0),
        ];
    }

    public static function conteudo(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $parametros = [];
        $condicao = '1=1';
        if ($filtros['congregacao_id'] > 0) {
            $condicao .= ' AND `congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $filtros['congregacao_id'];
        }
        $programacao = Database::run(
            'SELECT COUNT(*) AS `total` FROM `programacao_cultos` WHERE ' . $condicao,
            $parametros
        )->fetch();

        $geral = Database::run(
            'SELECT
                (SELECT COUNT(*) FROM `eventos` WHERE `deleted_at` IS NULL AND `data_inicio` >= NOW()) AS `eventos_futuros`,
                (SELECT COUNT(*) FROM `eventos` WHERE `deleted_at` IS NULL AND `publicado` = 1) AS `eventos_publicados`,
                (SELECT COUNT(*) FROM `noticias` WHERE `deleted_at` IS NULL AND `publicada` = 1) AS `noticias_publicadas`,
                (SELECT COUNT(*) FROM `galeria` WHERE `ativo` = 1) AS `fotos_ativas`,
                (SELECT COUNT(*) FROM `palavra_pastoral` WHERE `publicada` = 1) AS `palavras_publicadas`,
                (SELECT COUNT(*) FROM `versiculos_destaque` WHERE `ativo` = 1) AS `versiculos_ativos`,
                (SELECT COUNT(*) FROM `contato_mensagens` WHERE `lida` = 0) AS `contato_nao_lidas`,
                (SELECT COUNT(*) FROM `contato_mensagens` WHERE `respondida_em` IS NOT NULL) AS `contato_respondidas`'
        )->fetch();

        return [
            'Eventos futuros' => (int) ($geral['eventos_futuros'] ?? 0),
            'Eventos publicados' => (int) ($geral['eventos_publicados'] ?? 0),
            'Notícias publicadas' => (int) ($geral['noticias_publicadas'] ?? 0),
            'Fotos ativas' => (int) ($geral['fotos_ativas'] ?? 0),
            'Cultos ativos' => (int) ($programacao['total'] ?? 0),
            'Mensagens pastorais publicadas' => (int) ($geral['palavras_publicadas'] ?? 0),
            'Versículos ativos' => (int) ($geral['versiculos_ativos'] ?? 0),
            'Mensagens de contato não lidas' => (int) ($geral['contato_nao_lidas'] ?? 0),
            'Mensagens de contato respondidas' => (int) ($geral['contato_respondidas'] ?? 0),
        ];
    }

    public static function auditoria(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $condicoes = ['1=1'];
        $parametros = [];
        if ($filtros['modulo'] !== '') {
            $condicoes[] = '`modulo` = :modulo';
            $parametros[':modulo'] = $filtros['modulo'];
        }
        $where = implode(' AND ', $condicoes);

        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS `recentes`
               FROM `auditoria` WHERE ' . $where,
            $parametros
        )->fetch();

        $modulos = Database::run(
            'SELECT `modulo`, COUNT(*) AS `total`
               FROM `auditoria` WHERE ' . $where . '
              GROUP BY `modulo`
              ORDER BY `total` DESC, `modulo` ASC
              LIMIT 8',
            $parametros
        )->fetchAll();

        return [
            'resumo' => [
                'Ações registradas' => (int) ($linha['total'] ?? 0),
                'Ações em 7 dias' => (int) ($linha['recentes'] ?? 0),
                'Módulos com registros' => count($modulos),
            ],
            'modulos' => $modulos,
        ];
    }

    private static function condicaoFinanceira(array $filtros): array
    {
        $filtros = self::normalizarFiltros($filtros);
        $condicoes = ['1=1'];
        $parametros = [];

        if ($filtros['competencia'] !== '') {
            $condicoes[] = 'm.`competencia` = :competencia';
            $parametros[':competencia'] = $filtros['competencia'];
        }
        if ($filtros['congregacao_id'] > 0) {
            $condicoes[] = 'm.`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $filtros['congregacao_id'];
        }

        return [implode(' AND ', $condicoes), $parametros];
    }
}
