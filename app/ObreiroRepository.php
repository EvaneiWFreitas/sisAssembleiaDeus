<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class ObreiroRepository
{
    private const SITUACOES = ['ativo', 'licenciado', 'desligado'];

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = [];
        $parametros = [];
        if (!empty($filtros['somente_excluidos'])) {
            $condicoes[] = 'o.`deleted_at` IS NOT NULL';
        } elseif (empty($filtros['incluir_excluidos'])) {
            $condicoes[] = 'o.`deleted_at` IS NULL';
        }

        $id = (int) ($filtros['id'] ?? 0);
        if ($id > 0) {
            $condicoes[] = 'o.`id` = :id';
            $parametros[':id'] = $id;
        }

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(o.`nome` LIKE :q1 OR o.`codigo` LIKE :q2 OR o.`cpf` LIKE :q3 OR o.`email` LIKE :q4 OR o.`registro_ministerial` LIKE :q5)';
            for ($i = 1; $i <= 5; $i++) {
                $parametros[':q' . $i] = '%' . $busca . '%';
            }
        }

        $situacao = (string) ($filtros['situacao'] ?? '');
        if (in_array($situacao, self::SITUACOES, true)) {
            $condicoes[] = 'o.`situacao` = :situacao';
            $parametros[':situacao'] = $situacao;
        }

        $cargoId = (int) ($filtros['cargo_id'] ?? 0);
        if ($cargoId > 0) {
            $condicoes[] = 'o.`cargo_id` = :cargo';
            $parametros[':cargo'] = $cargoId;
        }

        $congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
        if ($congregacaoId > 0) {
            $condicoes[] = 'o.`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $congregacaoId;
        }

        return Database::run(
            'SELECT o.*, cm.`nome` AS `cargo_nome`, c.`nome` AS `congregacao_nome`,
                    m.`nome` AS `matriz_nome`, mb.`nome` AS `membro_nome`
             FROM `obreiros` o
             INNER JOIN `cargos_ministeriais` cm ON cm.`id` = o.`cargo_id`
             INNER JOIN `congregacoes` c ON c.`id` = o.`congregacao_id`
             LEFT JOIN `igrejas_matriz` m ON m.`id` = o.`igreja_origem_id`
             LEFT JOIN `membros` mb ON mb.`id` = o.`membro_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY o.`nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id, bool $incluirExcluidos = false): ?array
    {
        $itens = self::listar(['id' => $id, 'incluir_excluidos' => $incluirExcluidos]);
        return $itens[0] ?? null;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT
                (SELECT COUNT(*) FROM `obreiros` WHERE `deleted_at` IS NULL) AS `total`,
                (SELECT COUNT(*) FROM `obreiros` WHERE `deleted_at` IS NULL AND `situacao` = "ativo") AS `ativos`,
                (SELECT COUNT(*) FROM `obreiros` WHERE `deleted_at` IS NULL AND `situacao` = "licenciado") AS `licenciados`,
                (SELECT COUNT(*) FROM `obreiros` WHERE `deleted_at` IS NULL AND `situacao` = "desligado") AS `desligados`,
                (SELECT COUNT(*) FROM `obreiros` WHERE `deleted_at` IS NOT NULL) AS `excluidos`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'licenciados' => (int) ($linha['licenciados'] ?? 0),
            'desligados' => (int) ($linha['desligados'] ?? 0),
            'excluidos' => (int) ($linha['excluidos'] ?? 0),
        ];
    }

    public static function proximoCodigo(): string
    {
        $linha = Database::run(
            'SELECT MAX(CAST(SUBSTRING(`codigo`, 5) AS UNSIGNED)) AS `ultimo`
             FROM `obreiros` WHERE `codigo` LIKE "OBR-%"'
        )->fetch();

        return sprintf('OBR-%04d', ((int) ($linha['ultimo'] ?? 0)) + 1);
    }

    /** @return array<string, string> */
    public static function regras(?int $id): array
    {
        $exclusao = $id === null ? '' : ',id,' . $id;

        return [
            'codigo' => 'required|max:30|unique:obreiros,codigo' . $exclusao,
            'membro_id' => 'integer|unique:obreiros,membro_id' . $exclusao,
            'nome' => 'required|max:180',
            'cpf' => 'cpf|unique:obreiros,cpf' . $exclusao,
            'rg' => 'max:30',
            'data_nascimento' => 'date',
            'telefone' => 'max:30',
            'whatsapp' => 'max:30',
            'email' => 'email|max:190',
            'endereco' => 'max:200',
            'numero' => 'max:20',
            'complemento' => 'max:100',
            'bairro' => 'max:100',
            'cidade' => 'max:100',
            'estado' => 'max:2',
            'cep' => 'max:9',
            'igreja_origem_id' => 'integer',
            'congregacao_id' => 'required|integer',
            'cargo_id' => 'required|integer',
            'data_consagracao' => 'date',
            'registro_ministerial' => 'max:50|unique:obreiros,registro_ministerial' . $exclusao,
            'situacao' => 'required|in:' . implode(',', self::SITUACOES),
            'observacoes' => 'max:1000',
            'foto' => 'max:255',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $campos = [
            'codigo', 'membro_id', 'nome', 'foto', 'cpf', 'rg', 'data_nascimento', 'telefone', 'whatsapp',
            'email', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep',
            'igreja_origem_id', 'congregacao_id', 'cargo_id', 'data_consagracao', 'registro_ministerial',
            'situacao', 'observacoes',
        ];

        $colunas = [];
        $valores = [];
        foreach ($campos as $campo) {
            $valor = $dados[$campo] ?? null;
            $valor = is_string($valor) ? trim($valor) : $valor;

            if (in_array($campo, ['foto', 'membro_id', 'cpf', 'email', 'cep', 'estado', 'data_nascimento', 'igreja_origem_id', 'data_consagracao', 'registro_ministerial'], true) && $valor === '') {
                $valor = null;
            }
            if (in_array($campo, ['membro_id', 'igreja_origem_id', 'congregacao_id', 'cargo_id'], true)) {
                $valor = $valor === null ? null : (int) $valor;
            }
            if ($campo === 'situacao' && ($valor === null || $valor === '')) {
                $valor = 'ativo';
            }

            $colunas[] = '`' . $campo . '`';
            $valores[':' . $campo] = $valor;
        }

        $usuarioId = Auth::id();
        if ($id === null) {
            $colunas[] = '`created_by`';
            $colunas[] = '`updated_by`';
            $valores[':criador'] = $usuarioId;
            $valores[':atualizador'] = $usuarioId;
            $marcadores = [];
            foreach ($valores as $chave => $valor) {
                $marcadores[] = $chave;
            }
            Database::run(
                'INSERT INTO `obreiros` (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')',
                $valores
            );
            return (int) Database::connection()->lastInsertId();
        }

        $atribuicoes = [];
        foreach ($campos as $campo) {
            $atribuicoes[] = '`' . $campo . '` = :' . $campo;
        }
        $atribuicoes[] = '`updated_by` = :atualizador';
        $valores[':atualizador'] = $usuarioId;
        $valores[':id'] = $id;
        Database::run('UPDATE `obreiros` SET ' . implode(', ', $atribuicoes) . ' WHERE `id` = :id', $valores);

        return $id;
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'UPDATE `obreiros` SET `deleted_at` = NOW(), `updated_by` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => $id]
        );
    }

    public static function restaurar(int $id): void
    {
        Database::run(
            'UPDATE `obreiros` SET `deleted_at` = NULL, `updated_by` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => $id]
        );
    }

    public static function transferir(int $id, int $novaCongregacaoId, ?string $descricao): void
    {
        $atual = Database::run('SELECT `congregacao_id` FROM `obreiros` WHERE `id` = :id LIMIT 1', [':id' => $id])->fetch();
        if ($atual === false) {
            throw new InvalidArgumentException('Obreiro não encontrado.');
        }

        Database::run(
            'UPDATE `obreiros` SET `congregacao_id` = :nova, `updated_by` = :usuario WHERE `id` = :id',
            [':nova' => $novaCongregacaoId, ':usuario' => Auth::id(), ':id' => $id]
        );

        self::registrarHistorico([
            'obreiro_id' => $id,
            'tipo' => 'transferencia',
            'congregacao_anterior_id' => (int) $atual['congregacao_id'],
            'congregacao_nova_id' => $novaCongregacaoId,
            'descricao' => $descricao,
        ]);
    }

    /** @param array<string, mixed> $anterior @param array<string, mixed> $novo */
    public static function registrarAlteracoes(int $id, array $anterior, array $novo): void
    {
        if ((int) ($anterior['cargo_id'] ?? 0) !== (int) ($novo['cargo_id'] ?? 0)) {
            self::registrarHistorico([
                'obreiro_id' => $id,
                'tipo' => 'cargo',
                'cargo_anterior_id' => $anterior['cargo_id'] ?? null,
                'cargo_novo_id' => $novo['cargo_id'] ?? null,
            ]);
        }

        if ((int) ($anterior['congregacao_id'] ?? 0) !== (int) ($novo['congregacao_id'] ?? 0)) {
            self::registrarHistorico([
                'obreiro_id' => $id,
                'tipo' => 'transferencia',
                'congregacao_anterior_id' => $anterior['congregacao_id'] ?? null,
                'congregacao_nova_id' => $novo['congregacao_id'] ?? null,
            ]);
        }

        if ((string) ($anterior['situacao'] ?? '') !== (string) ($novo['situacao'] ?? '')) {
            self::registrarHistorico([
                'obreiro_id' => $id,
                'tipo' => 'situacao',
                'situacao_anterior' => $anterior['situacao'] ?? null,
                'situacao_nova' => $novo['situacao'] ?? null,
            ]);
        }

        if ((string) ($anterior['data_consagracao'] ?? '') !== (string) ($novo['data_consagracao'] ?? '')) {
            self::registrarHistorico([
                'obreiro_id' => $id,
                'tipo' => 'consagracao',
                'descricao' => 'Data de consagração: ' . format_date($anterior['data_consagracao'] ?? null)
                    . ' → ' . format_date($novo['data_consagracao'] ?? null) . '.',
            ]);
        }

        if ((string) ($anterior['observacoes'] ?? '') !== (string) ($novo['observacoes'] ?? '')) {
            self::registrarHistorico([
                'obreiro_id' => $id,
                'tipo' => 'observacao',
                'descricao' => 'Observações do cadastro atualizadas.',
            ]);
        }
    }

    /** @param array<string, mixed> $dados */
    public static function registrarHistorico(array $dados): void
    {
        $permitidos = [
            'obreiro_id', 'tipo', 'cargo_anterior_id', 'cargo_novo_id',
            'congregacao_anterior_id', 'congregacao_nova_id', 'situacao_anterior',
            'situacao_nova', 'descricao',
        ];

        $colunas = ['`usuario_id`'];
        $valores = [':usuario' => Auth::id()];
        foreach ($permitidos as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }
            $colunas[] = '`' . $campo . '`';
            $valores[':' . $campo] = $dados[$campo];
        }

        $marcadores = [];
        foreach ($valores as $chave => $valor) {
            $marcadores[] = $chave;
        }

        Database::run(
            'INSERT INTO `obreiros_historico` (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')',
            $valores
        );
    }

    /** @return list<array<string, mixed>> */
    public static function historico(int $id): array
    {
        return Database::run(
            'SELECT h.*, u.`nome` AS `usuario`,
                    ca.`nome` AS `cargo_anterior_nome`, cn.`nome` AS `cargo_novo_nome`,
                    cga.`nome` AS `congregacao_anterior_nome`, cgn.`nome` AS `congregacao_nova_nome`
             FROM `obreiros_historico` h
             LEFT JOIN `usuarios` u ON u.`id` = h.`usuario_id`
             LEFT JOIN `cargos_ministeriais` ca ON ca.`id` = h.`cargo_anterior_id`
             LEFT JOIN `cargos_ministeriais` cn ON cn.`id` = h.`cargo_novo_id`
             LEFT JOIN `congregacoes` cga ON cga.`id` = h.`congregacao_anterior_id`
             LEFT JOIN `congregacoes` cgn ON cgn.`id` = h.`congregacao_nova_id`
             WHERE h.`obreiro_id` = :id
             ORDER BY h.`data_historico` DESC, h.`id` DESC',
            [':id' => $id]
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function cargos(?int $incluirId = null): array
    {
        $condicoes = ['`ativo` = 1'];
        $parametros = [];
        if ($incluirId !== null) {
            $condicoes[] = '`id` = :id';
            $parametros[':id'] = $incluirId;
        }

        return Database::run(
            'SELECT `id`, `nome` FROM `cargos_ministeriais` WHERE (' . implode(' OR ', $condicoes) . ') ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function membros(?int $incluirId = null): array
    {
        $condicoes = ['`deleted_at` IS NULL'];
        $parametros = [];
        if ($incluirId !== null) {
            $condicoes[] = '`id` = :id';
            $parametros[':id'] = $incluirId;
        }

        return Database::run(
            'SELECT `id`, `nome` FROM `membros` WHERE (' . implode(' OR ', $condicoes) . ') ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }
}
