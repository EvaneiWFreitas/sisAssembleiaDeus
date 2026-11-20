<?php
declare(strict_types=1);

namespace App;

use DateTime;
use InvalidArgumentException;

final class CarteirinhaRepository
{
    public const TIPOS = ['membro' => 'Membro', 'obreiro' => 'Obreiro'];

    public const MODELOS = [
        'classico' => 'Clássico',
        'moderno' => 'Moderno',
        'horizontal' => 'Horizontal',
        'vertical' => 'Vertical',
        'institucional' => 'Institucional',
        'qrcode' => 'Com QR Code',
    ];

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(c.`codigo_validacao` LIKE :q1 OR COALESCE(m.`nome`, o.`nome`) LIKE :q2 OR COALESCE(m.`codigo`, o.`codigo`) LIKE :q3 OR cg.`nome` LIKE :q4)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
        }

        $tipo = (string) ($filtros['tipo'] ?? '');
        if (isset(self::TIPOS[$tipo])) {
            $condicoes[] = 'c.`tipo` = :tipo';
            $parametros[':tipo'] = $tipo;
        }

        $modelo = (string) ($filtros['modelo'] ?? '');
        if (isset(self::MODELOS[$modelo])) {
            $condicoes[] = 'c.`modelo` = :modelo';
            $parametros[':modelo'] = $modelo;
        }

        $congregacaoId = (int) ($filtros['congregacao_id'] ?? 0);
        if ($congregacaoId > 0) {
            $condicoes[] = 'COALESCE(m.`congregacao_id`, o.`congregacao_id`) = :congregacao';
            $parametros[':congregacao'] = $congregacaoId;
        }

        $segundaVia = (string) ($filtros['segunda_via'] ?? '');
        if ($segundaVia === '0' || $segundaVia === '1') {
            $condicoes[] = 'c.`segunda_via` = :segunda';
            $parametros[':segunda'] = (int) $segundaVia;
        }

        $situacao = (string) ($filtros['situacao'] ?? '');
        if ($situacao === 'valida') {
            $condicoes[] = '(c.`validade` IS NULL OR c.`validade` >= CURDATE())';
        } elseif ($situacao === 'vencida') {
            $condicoes[] = 'c.`validade` IS NOT NULL AND c.`validade` < CURDATE()';
        }

        return Database::run(
            'SELECT c.*,
                COALESCE(m.`nome`, o.`nome`) AS `pessoa_nome`,
                COALESCE(m.`codigo`, o.`codigo`) AS `pessoa_codigo`,
                COALESCE(m.`congregacao_id`, o.`congregacao_id`) AS `congregacao_id`,
                cg.`nome` AS `congregacao_nome`,
                u.`nome` AS `emitida_por_nome`
             FROM `carteirinhas` c
             LEFT JOIN `membros` m ON c.`tipo` = "membro" AND m.`id` = c.`referencia_id`
             LEFT JOIN `obreiros` o ON c.`tipo` = "obreiro" AND o.`id` = c.`referencia_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = COALESCE(m.`congregacao_id`, o.`congregacao_id`)
             LEFT JOIN `usuarios` u ON u.`id` = c.`emitida_por_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY c.`data_emissao` DESC, c.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT c.*,
                COALESCE(m.`nome`, o.`nome`) AS `pessoa_nome`,
                COALESCE(m.`codigo`, o.`codigo`) AS `pessoa_codigo`,
                COALESCE(m.`congregacao_id`, o.`congregacao_id`) AS `congregacao_id`,
                cg.`nome` AS `congregacao_nome`,
                u.`nome` AS `emitida_por_nome`
             FROM `carteirinhas` c
             LEFT JOIN `membros` m ON c.`tipo` = "membro" AND m.`id` = c.`referencia_id`
             LEFT JOIN `obreiros` o ON c.`tipo` = "obreiro" AND o.`id` = c.`referencia_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = COALESCE(m.`congregacao_id`, o.`congregacao_id`)
             LEFT JOIN `usuarios` u ON u.`id` = c.`emitida_por_id`
             WHERE c.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return list<array<string, mixed>> */
    public static function membros(): array
    {
        return SacramentoRepository::membros();
    }

    /** @return list<array<string, mixed>> */
    public static function obreiros(): array
    {
        return SacramentoRepository::obreiros();
    }

    /** @return list<array<string, mixed>> */
    public static function congregacoes(): array
    {
        return StructureRepository::congregacoes();
    }

    /** @return array<string, string> */
    public static function regras(): array
    {
        return [
            'tipo' => 'required|in:' . implode(',', array_keys(self::TIPOS)),
            'referencia_id' => 'required|integer|positive',
            'modelo' => 'required|in:' . implode(',', array_keys(self::MODELOS)),
            'validade' => 'date',
            'segunda_via' => 'in:0,1',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(?int $id, array $dados): int
    {
        $tipo = (string) $dados['tipo'];
        $referenciaId = (int) $dados['referencia_id'];
        if (!isset(self::TIPOS[$tipo]) || !self::referenciaValida($tipo, $referenciaId)) {
            throw new InvalidArgumentException('Pessoa selecionada não está disponível para emissão.');
        }

        $modelo = (string) $dados['modelo'];
        $validade = trim((string) ($dados['validade'] ?? ''));
        $validade = $validade === '' ? null : $validade;
        $segundaVia = (int) ($dados['segunda_via'] ?? 0) === 1 ? 1 : 0;

        if ($id !== null) {
            $registro = self::buscar($id);
            if ($registro === null) {
                throw new InvalidArgumentException('Carteirinha não encontrada.');
            }
            Database::run(
                'UPDATE `carteirinhas` SET `tipo` = :tipo, `referencia_id` = :referencia,
                    `modelo` = :modelo, `validade` = :validade, `segunda_via` = :segunda
                 WHERE `id` = :id',
                [
                    ':tipo' => $tipo,
                    ':referencia' => $referenciaId,
                    ':modelo' => $modelo,
                    ':validade' => $validade,
                    ':segunda' => $segundaVia,
                    ':id' => $id,
                ]
            );
            return $id;
        }

        $anterior = Database::run(
            'SELECT COUNT(*) AS `total` FROM `carteirinhas` WHERE `tipo` = :tipo AND `referencia_id` = :referencia',
            [':tipo' => $tipo, ':referencia' => $referenciaId]
        )->fetch();
        if ((int) ($anterior['total'] ?? 0) > 0) {
            $segundaVia = 1;
        }

        $codigo = self::gerarCodigo();
        Database::run(
            'INSERT INTO `carteirinhas`
                (`tipo`, `referencia_id`, `modelo`, `codigo_validacao`, `data_emissao`,
                 `validade`, `emitida_por_id`, `segunda_via`, `created_at`, `updated_at`)
             VALUES (:tipo, :referencia, :modelo, :codigo, NOW(), :validade, :usuario, :segunda, NOW(), NOW())',
            [
                ':tipo' => $tipo,
                ':referencia' => $referenciaId,
                ':modelo' => $modelo,
                ':codigo' => $codigo,
                ':validade' => $validade,
                ':usuario' => Auth::id(),
                ':segunda' => $segundaVia,
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id): void
    {
        Database::run(
            'DELETE FROM `documentos_emitidos` WHERE `tipo` = :tipo AND `referencia_id` = :id',
            [':tipo' => 'carteirinha', ':id' => $id]
        );
        Database::run('DELETE FROM `carteirinhas` WHERE `id` = :id', [':id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public static function validar(string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return null;
        }

        $registro = Database::run(
            'SELECT c.*,
                COALESCE(m.`nome`, o.`nome`) AS `pessoa_nome`,
                COALESCE(m.`codigo`, o.`codigo`) AS `pessoa_codigo`,
                COALESCE(m.`situacao`, o.`situacao`) AS `pessoa_situacao`,
                cg.`nome` AS `congregacao_nome`,
                u.`nome` AS `emitida_por_nome`
             FROM `carteirinhas` c
             LEFT JOIN `membros` m ON c.`tipo` = "membro" AND m.`id` = c.`referencia_id`
             LEFT JOIN `obreiros` o ON c.`tipo` = "obreiro" AND o.`id` = c.`referencia_id`
             LEFT JOIN `congregacoes` cg ON cg.`id` = COALESCE(m.`congregacao_id`, o.`congregacao_id`)
             LEFT JOIN `usuarios` u ON u.`id` = c.`emitida_por_id`
             WHERE c.`codigo_validacao` = :codigo LIMIT 1',
            [':codigo' => $codigo]
        )->fetch();

        if ($registro === false) {
            return null;
        }

        $vencida = (string) ($registro['validade'] ?? '') !== ''
            && (new DateTime((string) $registro['validade'])) < new DateTime('today');
        $situacaoPessoa = (string) ($registro['pessoa_situacao'] ?? '');
        $pessoaAtiva = !in_array($situacaoPessoa, ['inativo', 'transferido', 'falecido', 'desligado', 'licenciado'], true);

        if (!$pessoaAtiva) {
            $resultado = 'inativa';
            $mensagem = 'Carteirinha encontrada, mas o vínculo da pessoa está inativo.';
        } elseif ($vencida) {
            $resultado = 'vencida';
            $mensagem = 'Carteirinha encontrada, mas o prazo de validade expirou.';
        } else {
            $resultado = 'valida';
            $mensagem = 'Carteirinha válida.';
        }

        return [
            'resultado' => $resultado,
            'mensagem' => $mensagem,
            'carteirinha' => $registro,
        ];
    }

    /** @return array<string, mixed> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                SUM(CASE WHEN `segunda_via` = 1 THEN 1 ELSE 0 END) AS `segundas`,
                SUM(CASE WHEN `validade` IS NULL OR `validade` >= CURDATE() THEN 1 ELSE 0 END) AS `vigentes`,
                SUM(CASE WHEN `validade` IS NOT NULL AND `validade` < CURDATE() THEN 1 ELSE 0 END) AS `vencidas`
             FROM `carteirinhas`'
        )->fetch();

        return $linha === false ? [] : $linha;
    }

    /** @param array<string, mixed> $carteirinha @return array<string, mixed> */
    public static function registrarDocumento(array $carteirinha): int
    {
        return DocumentoRepository::registrar(
            'carteirinha',
            (int) $carteirinha['id'],
            (string) $carteirinha['codigo_validacao'],
            (string) $carteirinha['modelo'],
            [
                'codigo' => (string) $carteirinha['codigo_validacao'],
                'nome' => (string) ($carteirinha['pessoa_nome'] ?? ''),
                'tipo' => self::rotuloTipo((string) $carteirinha['tipo']),
                'congregacao' => (string) ($carteirinha['congregacao_nome'] ?? ''),
                'validade' => (string) ($carteirinha['validade'] ?? ''),
                'segunda_via' => (int) ($carteirinha['segunda_via'] ?? 0) === 1 ? 'Sim' : 'Não',
            ]
        );
    }

    /** @param array<string, mixed> $carteirinha @return array<string, mixed> */
    public static function valoresFormulario(array $carteirinha): array
    {
        return [
            'tipo' => (string) $carteirinha['tipo'],
            'referencia_id' => (string) $carteirinha['referencia_id'],
            'modelo' => (string) $carteirinha['modelo'],
            'validade' => (string) ($carteirinha['validade'] ?? ''),
            'segunda_via' => (int) ($carteirinha['segunda_via'] ?? 0) === 1 ? '1' : '0',
        ];
    }

    public static function rotuloTipo(string $tipo): string
    {
        return self::TIPOS[$tipo] ?? ucfirst($tipo);
    }

    public static function rotuloModelo(string $modelo): string
    {
        return self::MODELOS[$modelo] ?? ucfirst($modelo);
    }

    private static function referenciaValida(string $tipo, int $id): bool
    {
        $tabela = $tipo === 'membro' ? 'membros' : 'obreiros';
        $registro = Database::run(
            'SELECT `id` FROM `' . $tabela . '` WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro !== false;
    }

    private static function gerarCodigo(): string
    {
        $prefixo = 'CAR-' . date('Y') . '-';
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        for ($tentativa = 0; $tentativa < 20; $tentativa++) {
            $sufixo = '';
            for ($i = 0; $i < 6; $i++) {
                $sufixo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
            }
            $codigo = $prefixo . $sufixo;
            $existente = Database::run(
                'SELECT COUNT(*) AS `total` FROM `carteirinhas` WHERE `codigo_validacao` = :codigo',
                [':codigo' => $codigo]
            )->fetch();
            if ((int) ($existente['total'] ?? 0) === 0) {
                return $codigo;
            }
        }

        throw new InvalidArgumentException('Não foi possível gerar um código de validação único.');
    }
}
