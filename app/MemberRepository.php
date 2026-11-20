<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class MemberRepository
{
    private const SITUACOES = ['ativo', 'inativo', 'transferido', 'falecido'];
    private const SEXOS = ['masculino', 'feminino', 'outro', 'nao_informado'];
    private const ESTADOS_CIVIS = ['solteiro', 'casado', 'divorciado', 'viuvo', 'outro', 'nao_informado'];

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = [];
        $parametros = [];
        if (!empty($filtros['somente_excluidos'])) {
            $condicoes[] = 'm.`deleted_at` IS NOT NULL';
        } elseif (empty($filtros['incluir_excluidos'])) {
            $condicoes[] = 'm.`deleted_at` IS NULL';
        }

        $id = (int) ($filtros['id'] ?? 0);
        if ($id > 0) {
            $condicoes[] = 'm.`id` = :id';
            $parametros[':id'] = $id;
        }

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(m.`nome` LIKE :q1 OR m.`codigo` LIKE :q2 OR m.`cpf` LIKE :q3 OR m.`email` LIKE :q4 OR m.`telefone` LIKE :q5)';
            for ($i = 1; $i <= 5; $i++) {
                $parametros[':q' . $i] = '%' . $busca . '%';
            }
        }

        $situacao = (string) ($filtros['situacao'] ?? '');
        if (in_array($situacao, self::SITUACOES, true)) {
            $condicoes[] = 'm.`situacao` = :situacao';
            $parametros[':situacao'] = $situacao;
        }

        $congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
        if ($congregacaoId > 0) {
            $condicoes[] = 'm.`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = $congregacaoId;
        }

        return Database::run(
            'SELECT m.*, c.`nome` AS `congregacao_nome`, i.`nome` AS `igreja_nome`
             FROM `membros` m
             LEFT JOIN `congregacoes` c ON c.`id` = m.`congregacao_id`
             LEFT JOIN `igrejas_matriz` i ON i.`id` = m.`igreja_origem_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY m.`nome` ASC',
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
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NULL) AS `total`,
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NULL AND `situacao` = "ativo") AS `ativos`,
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NULL AND `situacao` = "inativo") AS `inativos`,
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NULL AND `situacao` = "transferido") AS `transferidos`,
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NULL AND `situacao` = "falecido") AS `falecidos`,
                (SELECT COUNT(*) FROM `membros` WHERE `deleted_at` IS NOT NULL) AS `excluidos`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'inativos' => (int) ($linha['inativos'] ?? 0),
            'transferidos' => (int) ($linha['transferidos'] ?? 0),
            'falecidos' => (int) ($linha['falecidos'] ?? 0),
            'excluidos' => (int) ($linha['excluidos'] ?? 0),
        ];
    }

    public static function proximoCodigo(): string
    {
        $linha = Database::run(
            'SELECT MAX(CAST(SUBSTRING(`codigo`, 5) AS UNSIGNED)) AS `ultimo`
             FROM `membros` WHERE `codigo` LIKE "MEM-%"'
        )->fetch();

        return sprintf('MEM-%04d', ((int) ($linha['ultimo'] ?? 0)) + 1);
    }

    /** @return array<string, string> */
    public static function regras(?int $id): array
    {
        $exclusao = $id === null ? '' : ',id,' . $id;

        return [
            'codigo' => 'required|max:30|unique:membros,codigo' . $exclusao,
            'nome' => 'required|max:180',
            'cpf' => 'cpf|unique:membros,cpf' . $exclusao,
            'rg' => 'max:30',
            'data_nascimento' => 'date',
            'sexo' => 'required|in:' . implode(',', self::SEXOS),
            'estado_civil' => 'required|in:' . implode(',', self::ESTADOS_CIVIS),
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
            'data_ingresso' => 'date',
            'igreja_origem_id' => 'integer',
            'congregacao_id' => 'integer',
            'data_batismo' => 'date',
            'situacao' => 'required|in:' . implode(',', self::SITUACOES),
            'observacoes' => 'max:1000',
            'foto' => 'max:255',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $campos = [
            'codigo', 'nome', 'foto', 'cpf', 'rg', 'data_nascimento', 'sexo', 'estado_civil',
            'telefone', 'whatsapp', 'email', 'endereco', 'numero', 'complemento', 'bairro',
            'cidade', 'estado', 'cep', 'data_ingresso', 'igreja_origem_id', 'congregacao_id',
            'data_batismo', 'situacao', 'observacoes',
        ];

        $colunas = [];
        $valores = [];
        foreach ($campos as $campo) {
            $valor = $dados[$campo] ?? null;
            $valor = is_string($valor) ? trim($valor) : $valor;

            if (in_array($campo, ['foto', 'cpf', 'email', 'cep', 'estado', 'data_nascimento', 'data_ingresso', 'data_batismo', 'igreja_origem_id', 'congregacao_id'], true) && $valor === '') {
                $valor = null;
            }
            if (in_array($campo, ['igreja_origem_id', 'congregacao_id'], true)) {
                $valor = $valor === null ? null : (int) $valor;
            }
            if ($campo === 'sexo' && ($valor === null || $valor === '')) {
                $valor = 'nao_informado';
            }
            if ($campo === 'estado_civil' && ($valor === null || $valor === '')) {
                $valor = 'nao_informado';
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
                'INSERT INTO `membros` (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')',
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
        Database::run('UPDATE `membros` SET ' . implode(', ', $atribuicoes) . ' WHERE `id` = :id', $valores);

        return $id;
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'UPDATE `membros` SET `deleted_at` = NOW(), `updated_by` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => $id]
        );
    }

    public static function restaurar(int $id): void
    {
        Database::run(
            'UPDATE `membros` SET `deleted_at` = NULL, `updated_by` = :usuario WHERE `id` = :id',
            [':usuario' => Auth::id(), ':id' => $id]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function historico(int $id): array
    {
        return Database::run(
            'SELECT a.`acao`, a.`modulo`, a.`entidade`, a.`dados_anteriores`, a.`dados_novos`, a.`created_at`,
                    u.`nome` AS `usuario`
             FROM `auditoria` a
             LEFT JOIN `usuarios` u ON u.`id` = a.`usuario_id`
             WHERE a.`modulo` = "membros" AND a.`entidade` = "membros" AND a.`entidade_id` = :id
             ORDER BY a.`created_at` DESC
             LIMIT 30',
            [':id' => $id]
        )->fetchAll();
    }
}
