<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class StructureRepository
{
    private const TIPOS = [
        'matriz' => [
            'tabela' => 'igrejas_matriz',
            'nome' => 'Matriz',
            'plural' => 'Matrizes',
            'rota' => 'matriz',
            'campo' => 'nome',
        ],
        'congregacao' => [
            'tabela' => 'congregacoes',
            'nome' => 'CongregaÃ§Ã£o',
            'plural' => 'CongregaÃ§Ãµes',
            'rota' => 'congregacao',
            'campo' => 'nome',
        ],
        'ponto' => [
            'tabela' => 'pontos_pregacao',
            'nome' => 'Ponto de pregaÃ§Ã£o',
            'plural' => 'Pontos de pregaÃ§Ã£o',
            'rota' => 'ponto',
            'campo' => 'identificacao',
        ],
    ];

    /** @return array<string, array<string, string>> */
    public static function tipos(): array
    {
        return self::TIPOS;
    }

    /** @return array<string, string>|null */
    public static function tipo(string $tipo): ?array
    {
        return self::TIPOS[$tipo] ?? null;
    }

    /** @return array<string, string> */
    public static function regras(string $tipo, ?int $id): array
    {
        $exclusao = $id === null ? '' : ',id,' . $id;
        $comum = [
            'situacao' => 'required|in:ativa,inativa',
            'observacoes' => 'max:1000',
        ];

        return match ($tipo) {
            'matriz' => $comum + [
                'nome' => 'required|max:150',
                'cnpj' => 'max:18|unique:igrejas_matriz,cnpj' . $exclusao,
                'logotipo' => 'max:255',
                'endereco' => 'max:200',
                'numero' => 'max:20',
                'complemento' => 'max:100',
                'bairro' => 'max:100',
                'cidade' => 'max:100',
                'estado' => 'max:2',
                'cep' => 'max:9',
                'telefone' => 'max:30',
                'email' => 'email|max:190',
                'pastor_responsavel_id' => 'integer',
                'data_fundacao' => 'date',
            ],
            'congregacao' => $comum + [
                'codigo' => 'required|max:30|unique:congregacoes,codigo' . $exclusao,
                'nome' => 'required|max:150',
                'igreja_matriz_id' => 'required|integer',
                'endereco' => 'max:200',
                'numero' => 'max:20',
                'complemento' => 'max:100',
                'bairro' => 'max:100',
                'cidade' => 'max:100',
                'estado' => 'max:2',
                'cep' => 'max:9',
                'telefone' => 'max:30',
                'email' => 'email|max:190',
                'pastor_responsavel_id' => 'integer',
                'data_abertura' => 'date',
            ],
            'ponto' => $comum + [
                'congregacao_id' => 'required|integer',
                'identificacao' => 'required|max:150',
                'endereco' => 'max:200',
                'numero' => 'max:20',
                'bairro' => 'max:100',
                'cidade' => 'max:100',
                'estado' => 'max:2',
                'cep' => 'max:9',
                'responsavel' => 'max:150',
                'dias_horarios' => 'max:255',
                'data_abertura' => 'date',
            ],
            default => throw new InvalidArgumentException('Tipo de estrutura invÃ¡lido.'),
        };
    }

    /** @return list<array<string, mixed>> */
    public static function listar(string $tipo): array
    {
        return match ($tipo) {
            'matriz' => Database::run(
                'SELECT m.*, o.`nome` AS `pastor_nome`,
                        (SELECT COUNT(*) FROM `congregacoes` c WHERE c.`igreja_matriz_id` = m.`id`) AS `total_congregacoes`
                 FROM `igrejas_matriz` m
                 LEFT JOIN `obreiros` o ON o.`id` = m.`pastor_responsavel_id`
                 ORDER BY m.`nome` ASC'
            )->fetchAll(),
            'congregacao' => Database::run(
                'SELECT c.*, m.`nome` AS `matriz_nome`, o.`nome` AS `pastor_nome`,
                        (SELECT COUNT(*) FROM `pontos_pregacao` p WHERE p.`congregacao_id` = c.`id`) AS `total_pontos`
                 FROM `congregacoes` c
                 INNER JOIN `igrejas_matriz` m ON m.`id` = c.`igreja_matriz_id`
                 LEFT JOIN `obreiros` o ON o.`id` = c.`pastor_responsavel_id`
                 ORDER BY c.`nome` ASC'
            )->fetchAll(),
            'ponto' => Database::run(
                'SELECT p.*, c.`nome` AS `congregacao_nome`, m.`nome` AS `matriz_nome`
                 FROM `pontos_pregacao` p
                 INNER JOIN `congregacoes` c ON c.`id` = p.`congregacao_id`
                 INNER JOIN `igrejas_matriz` m ON m.`id` = c.`igreja_matriz_id`
                 ORDER BY c.`nome`, p.`identificacao` ASC'
            )->fetchAll(),
            default => [],
        };
    }

    /** @return array<string, mixed>|null */
    public static function buscar(string $tipo, int $id): ?array
    {
        $linhas = self::listar($tipo);
        foreach ($linhas as $linha) {
            if ((int) $linha['id'] === $id) {
                return $linha;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(string $tipo, ?int $id, array $dados): int
    {
        $config = self::tipo($tipo);
        if ($config === null) {
            throw new InvalidArgumentException('Tipo de estrutura invÃ¡lido.');
        }

        $colunas = [];
        $valores = [];

        foreach (self::campos($tipo) as $campo) {
            $valor = $dados[$campo] ?? null;
            $valor = is_string($valor) ? trim($valor) : $valor;

            if (in_array($campo, ['cnpj', 'email', 'cep', 'estado', 'pastor_responsavel_id', 'data_fundacao', 'data_abertura'], true) && $valor === '') {
                $valor = null;
            }
            if (in_array($campo, ['igreja_matriz_id', 'congregacao_id', 'pastor_responsavel_id'], true)) {
                $valor = $valor === null || $valor === '' ? null : (int) $valor;
            }
            if ($campo === 'situacao' && ($valor === null || $valor === '')) {
                $valor = 'ativa';
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
        } else {
            $colunas[] = '`updated_by`';
            $valores[':atualizador'] = $usuarioId;
            $valores[':id'] = $id;
        }

        $marcadores = [];
        foreach (array_keys($valores) as $chave) {
            if ($chave !== ':id' && !($id === null && in_array($chave, [':criador', ':atualizador'], true))) {
                $marcadores[] = $chave;
            }
        }

        if ($id === null) {
            $marcadores = [];
            foreach ($valores as $chave => $valor) {
                $marcadores[] = $chave;
            }
            Database::run(
                'INSERT INTO `' . $config['tabela'] . '` (' . implode(', ', $colunas) . ')
                 VALUES (' . implode(', ', $marcadores) . ')',
                $valores
            );
            return (int) Database::connection()->lastInsertId();
        }

        $atribuicoes = [];
        foreach ($colunas as $indice => $coluna) {
            $atribuicoes[] = $coluna . ' = ' . $marcadores[$indice];
        }

        Database::run(
            'UPDATE `' . $config['tabela'] . '` SET ' . implode(', ', $atribuicoes) . ' WHERE `id` = :id',
            $valores
        );

        return $id;
    }

    public static function alternarSituacao(string $tipo, int $id): string
    {
        $config = self::tipo($tipo);
        if ($config === null) {
            throw new InvalidArgumentException('Tipo de estrutura invÃ¡lido.');
        }

        $atual = Database::run('SELECT `situacao` FROM `' . $config['tabela'] . '` WHERE `id` = :id LIMIT 1', [':id' => $id])->fetch();
        $novo = (($atual['situacao'] ?? '') === 'ativa') ? 'inativa' : 'ativa';

        Database::run(
            'UPDATE `' . $config['tabela'] . '` SET `situacao` = :situacao, `updated_by` = :usuario WHERE `id` = :id',
            [':situacao' => $novo, ':usuario' => Auth::id(), ':id' => $id]
        );

        return $novo;
    }

    /** @return array<string, array<string, int>> */
    public static function contadores(): array
    {
        $linha = Database::run(
            'SELECT
                (SELECT COUNT(*) FROM `igrejas_matriz`) AS `matriz_total`,
                (SELECT COUNT(*) FROM `igrejas_matriz` WHERE `situacao` = "ativa") AS `matriz_ativas`,
                (SELECT COUNT(*) FROM `congregacoes`) AS `congregacao_total`,
                (SELECT COUNT(*) FROM `congregacoes` WHERE `situacao` = "ativa") AS `congregacao_ativas`,
                (SELECT COUNT(*) FROM `pontos_pregacao`) AS `ponto_total`,
                (SELECT COUNT(*) FROM `pontos_pregacao` WHERE `situacao` = "ativa") AS `ponto_ativas`'
        )->fetch();

        return [
            'matriz' => ['total' => (int) ($linha['matriz_total'] ?? 0), 'ativas' => (int) ($linha['matriz_ativas'] ?? 0)],
            'congregacao' => ['total' => (int) ($linha['congregacao_total'] ?? 0), 'ativas' => (int) ($linha['congregacao_ativas'] ?? 0)],
            'ponto' => ['total' => (int) ($linha['ponto_total'] ?? 0), 'ativas' => (int) ($linha['ponto_ativas'] ?? 0)],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function obreiros(): array
    {
        return Database::run(
            'SELECT `id`, `nome` FROM `obreiros` WHERE `situacao` = "ativo" AND `deleted_at` IS NULL ORDER BY `nome` ASC'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function matrizes(?int $incluirId = null): array
    {
        $condicoes = ['`situacao` = "ativa"'];
        $parametros = [];
        if ($incluirId !== null) {
            $condicoes[] = '`id` = :id';
            $parametros[':id'] = $incluirId;
        }

        return Database::run(
            'SELECT `id`, `nome` FROM `igrejas_matriz` WHERE (' . implode(' OR ', $condicoes) . ') ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function congregacoes(?int $incluirId = null): array
    {
        $condicoes = ['`situacao` = "ativa"'];
        $parametros = [];
        if ($incluirId !== null) {
            $condicoes[] = '`id` = :id';
            $parametros[':id'] = $incluirId;
        }

        return Database::run(
            'SELECT `id`, `nome` FROM `congregacoes` WHERE (' . implode(' OR ', $condicoes) . ') ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return list<string> */
    private static function campos(string $tipo): array
    {
        return match ($tipo) {
            'matriz' => ['nome', 'cnpj', 'logotipo', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep', 'telefone', 'email', 'pastor_responsavel_id', 'data_fundacao', 'situacao', 'observacoes'],
            'congregacao' => ['codigo', 'nome', 'igreja_matriz_id', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep', 'telefone', 'email', 'pastor_responsavel_id', 'data_abertura', 'situacao', 'observacoes'],
            'ponto' => ['congregacao_id', 'identificacao', 'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep', 'responsavel', 'dias_horarios', 'data_abertura', 'situacao', 'observacoes'],
            default => [],
        };
    }
}

