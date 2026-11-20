<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class SacramentoRepository
{
    /** @return array<string, string> */
    private static function config(string $tipo): array
    {
        $configs = [
            'batismo' => [
                'titulo' => 'Batismo',
                'tabela' => 'batismos',
                'data' => 'data_batismo',
                'numero' => 'numero_certificado',
                'pessoa' => 'membro_id',
                'pastor' => 'pastor_celebrante_id',
                'tabela_diploma' => 'diplomas_batismo',
                'campo_diploma' => 'batismo_id',
                'numero_diploma' => 'numero_certificado',
                'nome_diploma' => 'batizado_nome',
                'data_diploma' => 'data_batismo',
                'pastor_diploma' => 'pastor_celebrante',
                'prefixo' => 'BAT',
            ],
            'consagracao' => [
                'titulo' => 'Consagração',
                'tabela' => 'consagracoes',
                'data' => 'data_consagracao',
                'numero' => 'numero_diploma',
                'pessoa' => 'obreiro_id',
                'pastor' => 'pastor_presidente_id',
                'tabela_diploma' => 'diplomas_consagracao',
                'campo_diploma' => 'consagracao_id',
                'numero_diploma' => 'numero_diploma',
                'nome_diploma' => 'obreiro_nome',
                'data_diploma' => 'data_consagracao',
                'pastor_diploma' => 'pastor_presidente',
                'prefixo' => 'CON',
            ],
        ];

        if (!isset($configs[$tipo])) {
            throw new InvalidArgumentException('Tipo de sacramento inválido.');
        }

        return $configs[$tipo];
    }

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function listar(string $tipo, array $filtros = []): array
    {
        $config = self::config($tipo);
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(p.`nome` LIKE :q1 OR r.`' . $config['numero'] . '` LIKE :q2 OR r.`local` LIKE :q3)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
        }

        $ano = (string) ($filtros['ano'] ?? '');
        if (ctype_digit($ano) && strlen($ano) === 4) {
            $condicoes[] = 'YEAR(r.`' . $config['data'] . '`) = :ano';
            $parametros[':ano'] = (int) $ano;
        }

        $diploma = (string) ($filtros['diploma'] ?? '');
        if ($diploma === '1') {
            $condicoes[] = 'd.`id` IS NOT NULL';
        } elseif ($diploma === '0') {
            $condicoes[] = 'd.`id` IS NULL';
        }

        if ($tipo === 'batismo') {
            $sql = 'SELECT r.*, p.`nome` AS `pessoa_nome`, p.`codigo` AS `pessoa_codigo`,
                           pa.`nome` AS `pastor_nome`, d.`id` AS `diploma_id`
                    FROM `batismos` r
                    INNER JOIN `membros` p ON p.`id` = r.`membro_id`
                    LEFT JOIN `obreiros` pa ON pa.`id` = r.`pastor_celebrante_id`
                    LEFT JOIN `diplomas_batismo` d ON d.`batismo_id` = r.`id`';
        } else {
            $sql = 'SELECT r.*, p.`nome` AS `pessoa_nome`, p.`codigo` AS `pessoa_codigo`,
                           ca.`nome` AS `cargo_nome`, pa.`nome` AS `pastor_nome`, d.`id` AS `diploma_id`
                    FROM `consagracoes` r
                    INNER JOIN `obreiros` p ON p.`id` = r.`obreiro_id`
                    INNER JOIN `cargos_ministeriais` ca ON ca.`id` = r.`cargo_id`
                    LEFT JOIN `obreiros` pa ON pa.`id` = r.`pastor_presidente_id`
                    LEFT JOIN `diplomas_consagracao` d ON d.`consagracao_id` = r.`id`';
        }

        return Database::run(
            $sql . ' WHERE ' . implode(' AND ', $condicoes) . ' ORDER BY r.`' . $config['data'] . '` DESC, r.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscar(string $tipo, int $id): ?array
    {
        $config = self::config($tipo);
        if ($tipo === 'batismo') {
            $sql = 'SELECT r.*, p.`nome` AS `pessoa_nome`, p.`codigo` AS `pessoa_codigo`, pa.`nome` AS `pastor_nome`
                    FROM `batismos` r
                    INNER JOIN `membros` p ON p.`id` = r.`membro_id`
                    LEFT JOIN `obreiros` pa ON pa.`id` = r.`pastor_celebrante_id`
                    WHERE r.`id` = :id LIMIT 1';
        } else {
            $sql = 'SELECT r.*, p.`nome` AS `pessoa_nome`, p.`codigo` AS `pessoa_codigo`,
                           ca.`nome` AS `cargo_nome`, pa.`nome` AS `pastor_nome`
                    FROM `consagracoes` r
                    INNER JOIN `obreiros` p ON p.`id` = r.`obreiro_id`
                    INNER JOIN `cargos_ministeriais` ca ON ca.`id` = r.`cargo_id`
                    LEFT JOIN `obreiros` pa ON pa.`id` = r.`pastor_presidente_id`
                    WHERE r.`id` = :id LIMIT 1';
        }

        $registro = Database::run($sql, [':id' => $id])->fetch();
        return $registro === false ? null : $registro;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT (SELECT COUNT(*) FROM `batismos`) AS `batismos`,
                    (SELECT COUNT(*) FROM `consagracoes`) AS `consagracoes`,
                    (SELECT COUNT(*) FROM `diplomas_batismo`) AS `diplomas_batismo`,
                    (SELECT COUNT(*) FROM `diplomas_consagracao`) AS `diplomas_consagracao`'
        )->fetch();

        return [
            'batismos' => (int) ($linha['batismos'] ?? 0),
            'consagracoes' => (int) ($linha['consagracoes'] ?? 0),
            'diplomas_batismo' => (int) ($linha['diplomas_batismo'] ?? 0),
            'diplomas_consagracao' => (int) ($linha['diplomas_consagracao'] ?? 0),
        ];
    }

    /** @return array<string, string> */
    public static function regras(string $tipo, ?int $id = null): array
    {
        self::config($tipo);
        $exclusao = $id !== null ? ',id,' . $id : '';

        if ($tipo === 'batismo') {
            return [
                'membro_id' => 'required|integer',
                'data_batismo' => 'required|date',
                'local' => 'max:200',
                'pastor_celebrante_id' => 'integer',
                'numero_certificado' => 'max:60|unique:batismos,numero_certificado' . $exclusao,
                'observacoes' => 'max:1000',
            ];
        }

        return [
            'obreiro_id' => 'required|integer',
            'cargo_id' => 'required|integer',
            'data_consagracao' => 'required|date',
            'local' => 'max:200',
            'pastor_presidente_id' => 'integer',
            'numero_diploma' => 'max:60|unique:consagracoes,numero_diploma' . $exclusao,
            'observacoes' => 'max:1000',
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function membros(?int $incluirId = null): array
    {
        $condicao = $incluirId !== null ? '(`deleted_at` IS NULL OR `id` = :id)' : '`deleted_at` IS NULL';
        $parametros = $incluirId !== null ? [':id' => $incluirId] : [];

        return Database::run(
            'SELECT `id`, `codigo`, `nome` FROM `membros` WHERE ' . $condicao . ' ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function obreiros(?int $incluirId = null): array
    {
        $condicao = $incluirId !== null ? '(`deleted_at` IS NULL OR `id` = :id)' : '`deleted_at` IS NULL';
        $parametros = $incluirId !== null ? [':id' => $incluirId] : [];

        return Database::run(
            'SELECT `id`, `codigo`, `nome` FROM `obreiros` WHERE ' . $condicao . ' ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function cargos(?int $incluirId = null): array
    {
        $condicao = $incluirId !== null ? '(`ativo` = 1 OR `id` = :id)' : '`ativo` = 1';
        $parametros = $incluirId !== null ? [':id' => $incluirId] : [];

        return Database::run(
            'SELECT `id`, `nome` FROM `cargos_ministeriais` WHERE ' . $condicao . ' ORDER BY `nome` ASC',
            $parametros
        )->fetchAll();
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(string $tipo, ?int $id, array $dados): int
    {
        $config = self::config($tipo);
        $campos = $tipo === 'batismo'
            ? ['membro_id', 'data_batismo', 'local', 'pastor_celebrante_id', 'numero_certificado', 'observacoes']
            : ['obreiro_id', 'cargo_id', 'data_consagracao', 'local', 'pastor_presidente_id', 'numero_diploma', 'observacoes'];
        $nullable = ['local', 'pastor_celebrante_id', 'pastor_presidente_id', 'numero_certificado', 'numero_diploma', 'observacoes'];

        $colunas = [];
        $valores = [];
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }
            $valor = $dados[$campo];
            if (is_string($valor) && trim($valor) === '' && in_array($campo, $nullable, true)) {
                $valor = null;
            }
            $colunas[] = '`' . $campo . '`';
            $valores[':' . $campo] = $valor;
        }

        if ($id === null) {
            $colunas[] = '`created_by`';
            $colunas[] = '`updated_by`';
            $valores[':created_by'] = Auth::id();
            $valores[':updated_by'] = Auth::id();
            Database::run(
                'INSERT INTO `' . $config['tabela'] . '` (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', array_keys($valores)) . ')',
                $valores
            );
            return (int) Database::connection()->lastInsertId();
        }

        $atribuicoes = ['`updated_by` = :usuario'];
        foreach ($colunas as $coluna) {
            $campo = trim($coluna, '`');
            $atribuicoes[] = '`' . $campo . '` = :' . $campo;
        }
        $valores[':usuario'] = Auth::id();
        $valores[':id'] = $id;
        Database::run(
            'UPDATE `' . $config['tabela'] . '` SET ' . implode(', ', $atribuicoes) . ' WHERE `id` = :id',
            $valores
        );

        return $id;
    }

    public static function excluir(string $tipo, int $id): void
    {
        $config = self::config($tipo);
        Database::run('DELETE FROM `' . $config['tabela'] . '` WHERE `id` = :id', [':id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public static function diploma(string $tipo, int $id): ?array
    {
        $config = self::config($tipo);
        $registro = Database::run(
            'SELECT * FROM `' . $config['tabela_diploma'] . '` WHERE `' . $config['campo_diploma'] . '` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @param array<string, mixed> $dados */
    public static function emitirDiploma(string $tipo, int $id, array $dados): int
    {
        $config = self::config($tipo);
        $registro = self::buscar($tipo, $id);
        if ($registro === null) {
            throw new InvalidArgumentException('Registro de sacramento não encontrado.');
        }

        $numero = (string) ($registro[$config['numero']] ?? '');
        if ($numero === '') {
            $numero = self::proximoNumero($tipo);
        }

        $valores = [
            ':numero' => $numero,
            ':nome' => (string) $registro['pessoa_nome'],
            ':data' => (string) $registro[$config['data']],
            ':local' => (string) ($registro['local'] ?? ''),
            ':pastor' => (string) ($registro['pastor_nome'] ?? ''),
            ':assinaturas' => (string) ($dados['assinaturas'] ?? ''),
            ':modelo' => (string) ($dados['modelo'] ?? 'classico'),
            ':ref' => $id,
        ];
        if ($tipo === 'consagracao') {
            $valores[':cargo'] = (string) ($registro['cargo_nome'] ?? '');
        }

        $diploma = self::diploma($tipo, $id);
        if ($diploma !== null) {
            $atualizacoes = [
                '`' . $config['numero_diploma'] . '` = :numero',
                '`' . $config['nome_diploma'] . '` = :nome',
                '`' . $config['data_diploma'] . '` = :data',
                '`local` = :local',
                '`' . $config['pastor_diploma'] . '` = :pastor',
                '`assinaturas` = :assinaturas',
                '`modelo` = :modelo',
            ];
            if ($tipo === 'consagracao') {
                $atualizacoes[] = '`cargo_ministerial` = :cargo';
            }

            Database::run(
                'UPDATE `' . $config['tabela_diploma'] . '` SET ' . implode(', ', $atualizacoes) . ' WHERE `id` = :ref',
                $valores
            );
            self::registrarDocumento($tipo, (int) $diploma['id'], $registro, $dados, $numero, $config);
            return (int) $diploma['id'];
        }

        $colunas = [
            '`' . $config['campo_diploma'] . '`',
            '`' . $config['nome_diploma'] . '`',
            '`' . $config['data_diploma'] . '`',
            '`local`',
            '`' . $config['pastor_diploma'] . '`',
            '`' . $config['numero_diploma'] . '`',
            '`assinaturas`',
            '`modelo`',
            '`created_at`',
        ];
        $marcadores = [':ref', ':nome', ':data', ':local', ':pastor', ':numero', ':assinaturas', ':modelo', 'NOW()'];
        if ($tipo === 'consagracao') {
            $colunas[] = '`cargo_ministerial`';
            $marcadores[] = ':cargo';
        }

        Database::run(
            'INSERT INTO `' . $config['tabela_diploma'] . '` (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')',
            $valores
        );

        $diplomaId = (int) Database::connection()->lastInsertId();
        self::registrarDocumento($tipo, $diplomaId, $registro, $dados, $numero, $config);
        return $diplomaId;
    }

    /** @param array<string, mixed> $registro @param array<string, mixed> $dados @param array<string, string> $config */
    private static function registrarDocumento(
        string $tipo,
        int $diplomaId,
        array $registro,
        array $dados,
        string $numero,
        array $config
    ): void {
        DocumentoRepository::registrar(
            $tipo === 'batismo' ? 'diploma_batismo' : 'diploma_consagracao',
            $diplomaId,
            $numero,
            (string) ($dados['modelo'] ?? 'classico'),
            [
                'numero' => $numero,
                'nome' => (string) $registro['pessoa_nome'],
                'data' => (string) $registro[$config['data']],
                'local' => (string) ($registro['local'] ?? ''),
                'pastor' => (string) ($registro['pastor_nome'] ?? ''),
                'assinaturas' => (string) ($dados['assinaturas'] ?? ''),
                'cargo' => (string) ($registro['cargo_nome'] ?? ''),
            ]
        );
    }

    public static function proximoNumero(string $tipo): string
    {
        $config = self::config($tipo);
        $ano = date('Y');
        $prefixo = $config['prefixo'] . '-' . $ano . '-';
        $linha = Database::run(
            'SELECT COUNT(*) AS `total` FROM `' . $config['tabela'] . '` WHERE `' . $config['numero'] . '` LIKE :prefixo',
            [':prefixo' => $prefixo . '%']
        )->fetch();

        return $prefixo . str_pad((string) ((int) ($linha['total'] ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, string> */
    public static function regrasDiploma(): array
    {
        return [
            'modelo' => 'required|in:classico,moderno,institucional',
            'assinaturas' => 'max:1000',
        ];
    }
}
