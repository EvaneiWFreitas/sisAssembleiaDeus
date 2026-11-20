<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class ProgramacaoRepository
{
    public const DIAS = [
        'domingo' => 'Domingo',
        'segunda' => 'Segunda-feira',
        'terca' => 'Terça-feira',
        'quarta' => 'Quarta-feira',
        'quinta' => 'Quinta-feira',
        'sexta' => 'Sexta-feira',
        'sabado' => 'Sábado',
    ];

    public static function listar(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(p.`tipo` LIKE :q1 OR p.`descricao` LIKE :q2 OR c.`nome` LIKE :q3)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
        }

        $dia = (string) ($filtros['dia_semana'] ?? '');
        if (isset(self::DIAS[$dia])) {
            $condicoes[] = 'p.`dia_semana` = :dia';
            $parametros[':dia'] = $dia;
        }

        $ativo = (string) ($filtros['ativo'] ?? '');
        if ($ativo === '0' || $ativo === '1') {
            $condicoes[] = 'p.`ativo` = :ativo';
            $parametros[':ativo'] = (int) $ativo;
        }

        $congregacao = (string) ($filtros['congregacao_id'] ?? '');
        if ($congregacao === 'global') {
            $condicoes[] = 'p.`congregacao_id` IS NULL';
        } elseif ($congregacao !== '' && filter_var($congregacao, FILTER_VALIDATE_INT) !== false) {
            $condicoes[] = 'p.`congregacao_id` = :congregacao';
            $parametros[':congregacao'] = (int) $congregacao;
        }

        return Database::run(
            'SELECT p.*, c.`nome` AS `congregacao_nome`
               FROM `programacao_cultos` p
               LEFT JOIN `congregacoes` c ON c.`id` = p.`congregacao_id`
              WHERE ' . implode(' AND ', $condicoes) . '
              ORDER BY FIELD(p.`dia_semana`, "domingo", "segunda", "terca", "quarta", "quinta", "sexta", "sabado"),
                       p.`ordem` ASC, p.`horario` ASC, p.`id` ASC',
            $parametros
        )->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT p.*, c.`nome` AS `congregacao_nome`
               FROM `programacao_cultos` p
               LEFT JOIN `congregacoes` c ON c.`id` = p.`congregacao_id`
              WHERE p.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function regras(): array
    {
        return [
            'congregacao_id' => 'integer',
            'dia_semana' => 'required|in:' . implode(',', array_keys(self::DIAS)),
            'horario' => 'required|max:5',
            'tipo' => 'max:80',
            'descricao' => 'max:255',
            'ordem' => 'integer|min:0',
            'ativo' => 'in:0,1',
        ];
    }

    public static function salvar(?int $id, array $dados): int
    {
        $atual = $id === null ? null : self::buscar($id);
        if ($id !== null && $atual === null) {
            throw new InvalidArgumentException('Horário de culto não encontrado.');
        }

        $dia = (string) ($dados['dia_semana'] ?? '');
        if (!isset(self::DIAS[$dia])) {
            throw new InvalidArgumentException('Selecione um dia da semana válido.');
        }

        $horario = self::normalizarHorario((string) ($dados['horario'] ?? ''));
        $congregacao = trim((string) ($dados['congregacao_id'] ?? ''));
        $congregacaoId = $congregacao === '' ? null : (int) $congregacao;
        if ($congregacaoId !== null && $congregacaoId <= 0) {
            $congregacaoId = null;
        }
        if ($congregacaoId !== null) {
            $existe = Database::run(
                'SELECT 1 FROM `congregacoes` WHERE `id` = :id LIMIT 1',
                [':id' => $congregacaoId]
            )->fetchColumn();
            if ($existe === false) {
                throw new InvalidArgumentException('Congregação selecionada é inválida.');
            }
        }

        $tipo = trim((string) ($dados['tipo'] ?? ''));
        $tipo = $tipo === '' ? null : $tipo;
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $descricao = $descricao === '' ? null : $descricao;
        $ordem = max(0, (int) ($dados['ordem'] ?? 0));
        $ativo = (int) ($dados['ativo'] ?? 0) === 1 ? 1 : 0;

        $parametros = [
            ':congregacao' => $congregacaoId,
            ':dia' => $dia,
            ':horario' => $horario,
            ':tipo' => $tipo,
            ':descricao' => $descricao,
            ':ordem' => $ordem,
            ':ativo' => $ativo,
        ];

        if ($id !== null) {
            Database::run(
                'UPDATE `programacao_cultos`
                    SET `congregacao_id` = :congregacao, `dia_semana` = :dia, `horario` = :horario,
                        `tipo` = :tipo, `descricao` = :descricao, `ordem` = :ordem,
                        `ativo` = :ativo, `updated_at` = NOW()
                  WHERE `id` = :id',
                $parametros + [':id' => $id]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `programacao_cultos`
                (`congregacao_id`, `dia_semana`, `horario`, `tipo`, `descricao`, `ordem`, `ativo`, `created_at`, `updated_at`)
             VALUES (:congregacao, :dia, :horario, :tipo, :descricao, :ordem, :ativo, NOW(), NOW())',
            $parametros
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        Database::run('DELETE FROM `programacao_cultos` WHERE `id` = :id', [':id' => $id]);
    }

    public static function alternarAtivo(int $id): int
    {
        $item = self::buscar($id);
        if ($item === null) {
            throw new InvalidArgumentException('Horário de culto não encontrado.');
        }

        $ativo = (int) $item['ativo'] === 1 ? 0 : 1;
        Database::run('UPDATE `programacao_cultos` SET `ativo` = :ativo, `updated_at` = NOW() WHERE `id` = :id', [
            ':ativo' => $ativo,
            ':id' => $id,
        ]);

        return $ativo;
    }

    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    SUM(`ativo` = 1) AS `ativos`,
                    SUM(`ativo` = 0) AS `inativos`
               FROM `programacao_cultos`'
        )->fetch();

        return [
            'total' => (int) ($linha['total'] ?? 0),
            'ativos' => (int) ($linha['ativos'] ?? 0),
            'inativos' => (int) ($linha['inativos'] ?? 0),
        ];
    }

    public static function proximaOrdem(): int
    {
        $linha = Database::run('SELECT COALESCE(MAX(`ordem`), 0) AS `ordem` FROM `programacao_cultos`')->fetch();

        return (int) ($linha['ordem'] ?? 0) + 10;
    }

    public static function valoresFormulario(array $item): array
    {
        return [
            'congregacao_id' => (string) ($item['congregacao_id'] ?? ''),
            'dia_semana' => (string) ($item['dia_semana'] ?? ''),
            'horario' => substr((string) ($item['horario'] ?? ''), 0, 5),
            'tipo' => (string) ($item['tipo'] ?? ''),
            'descricao' => (string) ($item['descricao'] ?? ''),
            'ordem' => (string) (int) ($item['ordem'] ?? 0),
            'ativo' => (string) (int) ($item['ativo'] ?? 0),
        ];
    }

    public static function normalizarHorario(string $valor): string
    {
        $valor = trim($valor);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) !== 1) {
            throw new InvalidArgumentException('Informe um horário válido no formato HH:MM.');
        }

        return $valor . ':00';
    }

    public static function rotuloDia(string $dia): string
    {
        return self::DIAS[$dia] ?? ucfirst($dia);
    }
}
