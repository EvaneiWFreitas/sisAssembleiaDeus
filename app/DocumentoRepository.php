<?php
declare(strict_types=1);

namespace App;

use DateTime;
use InvalidArgumentException;

final class DocumentoRepository
{
    public const TIPOS = [
        'carta_convite' => 'Carta-convite',
        'diploma_batismo' => 'Certificado de batismo',
        'diploma_consagracao' => 'Diploma de consagração',
        'carteirinha' => 'Carteirinha',
        'ficha_membro' => 'Ficha de membro',
        'ficha_obreiro' => 'Ficha de obreiro',
    ];

    public const MODELOS = [
        'classico' => 'Clássico',
        'moderno' => 'Moderno',
        'institucional' => 'Institucional',
    ];

    public const DADOS_JURIDICOS = [
        'razao_social' => 'Razão social',
        'cnpj' => 'CNPJ da igreja',
        'inscricao_estadual' => 'Inscrição estadual',
        'inscricao_municipal' => 'Inscrição municipal',
        'alvara_funcionamento' => 'Alvará de funcionamento',
        'registro_estatuto' => 'Registro do estatuto',
    ];

    private const DADOS_JURIDICOS_CONFIG = [
        'razao_social' => 'ministerio_razao_social',
        'cnpj' => 'ministerio_cnpj',
        'inscricao_estadual' => 'ministerio_inscricao_estadual',
        'inscricao_municipal' => 'ministerio_inscricao_municipal',
        'alvara_funcionamento' => 'ministerio_alvara_funcionamento',
        'registro_estatuto' => 'ministerio_registro_estatuto',
    ];

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function convites(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(c.`numero` LIKE :q1 OR c.`convidado` LIKE :q2 OR c.`nome_evento` LIKE :q3)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
        }

        $modelo = (string) ($filtros['modelo'] ?? '');
        if (isset(self::MODELOS[$modelo])) {
            $condicoes[] = 'c.`modelo` = :modelo';
            $parametros[':modelo'] = $modelo;
        }

        foreach (['de' => '>=', 'ate' => '<='] as $campo => $operador) {
            $valor = trim((string) ($filtros[$campo] ?? ''));
            if (self::dataValida($valor)) {
                $condicoes[] = 'DATE(c.`data_evento`) ' . $operador . ' :' . $campo;
                $parametros[':' . $campo] = $valor;
            }
        }

        return Database::run(
            'SELECT c.*, u.`nome` AS `emitido_por_nome`
             FROM `convites` c
             LEFT JOIN `usuarios` u ON u.`id` = c.`emitido_por_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY c.`data_evento` DESC, c.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscarConvite(int $id): ?array
    {
        $registro = Database::run(
            'SELECT c.*, u.`nome` AS `emitido_por_nome`
             FROM `convites` c
             LEFT JOIN `usuarios` u ON u.`id` = c.`emitido_por_id`
             WHERE c.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    public static function proximoNumeroConvite(): string
    {
        $prefixo = 'CONV-' . date('Y') . '-';
        $linha = Database::run(
            'SELECT COUNT(*) AS `total` FROM `convites` WHERE `numero` LIKE :prefixo',
            [':prefixo' => $prefixo . '%']
        )->fetch();
        $proximo = (int) ($linha['total'] ?? 0) + 1;

        while ($proximo < 10000) {
            $numero = $prefixo . str_pad((string) $proximo, 4, '0', STR_PAD_LEFT);
            $existe = Database::run(
                'SELECT COUNT(*) AS `total` FROM `convites` WHERE `numero` = :numero',
                [':numero' => $numero]
            )->fetch();
            if ((int) ($existe['total'] ?? 0) === 0) {
                return $numero;
            }
            $proximo++;
        }

        throw new InvalidArgumentException('Não foi possível gerar um número de convite único.');
    }

    /** @param int|null $id @return array<string, string> */
    public static function regrasConvite(?int $id = null): array
    {
        return [
            'convidado' => 'required|max:180',
            'igreja_convidada' => 'max:180',
            'endereco' => 'max:1000',
            'nome_evento' => 'required|max:180',
            'data_evento' => 'required|datetime',
            'local' => 'max:200',
            'pastor_responsavel' => 'max:180',
            'assinaturas' => 'max:1000',
            'modelo' => 'required|in:' . implode(',', array_keys(self::MODELOS)),
            'razao_social' => 'max:180',
            'cnpj' => 'max:18',
            'inscricao_estadual' => 'max:80',
            'inscricao_municipal' => 'max:80',
            'alvara_funcionamento' => 'max:80',
            'registro_estatuto' => 'max:80',
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvarConvite(?int $id, array $dados): int
    {
        $valores = [
            ':convidado' => (string) $dados['convidado'],
            ':igreja' => (string) ($dados['igreja_convidada'] ?? ''),
            ':endereco' => (string) ($dados['endereco'] ?? ''),
            ':evento' => (string) $dados['nome_evento'],
            ':data' => str_replace('T', ' ', (string) $dados['data_evento']) . ':00',
            ':local' => (string) ($dados['local'] ?? ''),
            ':pastor' => (string) ($dados['pastor_responsavel'] ?? ''),
            ':assinaturas' => (string) ($dados['assinaturas'] ?? ''),
            ':modelo' => (string) $dados['modelo'],
        ];

        if ($id !== null) {
            $valores[':id'] = $id;
            Database::run(
                'UPDATE `convites` SET
                    `convidado` = :convidado, `igreja_convidada` = :igreja, `endereco` = :endereco,
                    `nome_evento` = :evento, `data_evento` = :data, `local` = :local,
                    `pastor_responsavel` = :pastor, `assinaturas` = :assinaturas, `modelo` = :modelo
                 WHERE `id` = :id',
                $valores
            );
            return $id;
        }

        $numero = self::proximoNumeroConvite();
        $valores[':numero'] = $numero;
        $valores[':usuario'] = Auth::id();
        Database::run(
            'INSERT INTO `convites`
                (`numero`, `convidado`, `igreja_convidada`, `endereco`, `nome_evento`, `data_evento`,
                 `local`, `pastor_responsavel`, `assinaturas`, `modelo`, `emitido_em`, `emitido_por_id`,
                 `created_at`, `updated_at`)
             VALUES
                (:numero, :convidado, :igreja, :endereco, :evento, :data,
                 :local, :pastor, :assinaturas, :modelo, NOW(), :usuario, NOW(), NOW())',
            $valores
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluirConvite(int $id): void
    {
        Database::run(
            'DELETE FROM `documentos_emitidos` WHERE `tipo` = :tipo AND `referencia_id` = :id',
            [':tipo' => 'carta_convite', ':id' => $id]
        );
        Database::run('DELETE FROM `convites` WHERE `id` = :id', [':id' => $id]);
    }

    /** @param array<string, mixed> $filtros @return list<array<string, mixed>> */
    public static function documentos(array $filtros = []): array
    {
        $condicoes = ['1=1'];
        $parametros = [];

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $condicoes[] = '(d.`numero` LIKE :q1 OR d.`tipo` LIKE :q2 OR d.`dados_json` LIKE :q3 OR u.`nome` LIKE :q4)';
            $parametros[':q1'] = '%' . $busca . '%';
            $parametros[':q2'] = '%' . $busca . '%';
            $parametros[':q3'] = '%' . $busca . '%';
            $parametros[':q4'] = '%' . $busca . '%';
        }

        $tipo = (string) ($filtros['tipo'] ?? '');
        if (isset(self::TIPOS[$tipo])) {
            $condicoes[] = 'd.`tipo` = :tipo';
            $parametros[':tipo'] = $tipo;
        }

        foreach (['de' => '>=', 'ate' => '<='] as $campo => $operador) {
            $valor = trim((string) ($filtros[$campo] ?? ''));
            if (self::dataValida($valor)) {
                $condicoes[] = 'DATE(d.`emitido_em`) ' . $operador . ' :' . $campo;
                $parametros[':' . $campo] = $valor;
            }
        }

        return Database::run(
            'SELECT d.*, u.`nome` AS `emitido_por_nome`
             FROM `documentos_emitidos` d
             LEFT JOIN `usuarios` u ON u.`id` = d.`emitido_por_id`
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY d.`emitido_em` DESC, d.`id` DESC',
            $parametros
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function buscarDocumento(int $id): ?array
    {
        $registro = Database::run(
            'SELECT d.*, u.`nome` AS `emitido_por_nome`
             FROM `documentos_emitidos` d
             LEFT JOIN `usuarios` u ON u.`id` = d.`emitido_por_id`
             WHERE d.`id` = :id LIMIT 1',
            [':id' => $id]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @return array<string, mixed>|null */
    public static function buscarDocumentoPorConvite(int $conviteId): ?array
    {
        $registro = Database::run(
            'SELECT d.*, u.`nome` AS `emitido_por_nome`
             FROM `documentos_emitidos` d
             LEFT JOIN `usuarios` u ON u.`id` = d.`emitido_por_id`
             WHERE d.`tipo` = :tipo AND d.`referencia_id` = :ref
             ORDER BY d.`id` DESC LIMIT 1',
            [':tipo' => 'carta_convite', ':ref' => $conviteId]
        )->fetch();

        return $registro === false ? null : $registro;
    }

    /** @param array<string, mixed> $dados */
    public static function registrar(
        string $tipo,
        int $referenciaId,
        string $numero,
        string $modelo,
        array $dados
    ): int {
        if (!isset(self::TIPOS[$tipo])) {
            throw new InvalidArgumentException('Tipo de documento não permitido.');
        }

        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $valores = [
            ':tipo' => $tipo,
            ':ref' => $referenciaId,
            ':numero' => $numero,
            ':modelo' => $modelo,
            ':dados' => $json === false ? null : $json,
            ':usuario' => Auth::id(),
        ];

        $existente = Database::run(
            'SELECT `id` FROM `documentos_emitidos`
             WHERE `tipo` = :tipo AND `referencia_id` = :ref LIMIT 1',
            [':tipo' => $tipo, ':ref' => $referenciaId]
        )->fetch();

        if ($existente !== false) {
            $id = (int) $existente['id'];
            Database::run(
                'UPDATE `documentos_emitidos` SET `numero` = :numero, `modelo` = :modelo,
                    `dados_json` = :dados, `emitido_por_id` = :usuario, `emitido_em` = NOW()
                 WHERE `id` = :id',
                [
                    ':numero' => $numero,
                    ':modelo' => $modelo,
                    ':dados' => $json === false ? null : $json,
                    ':usuario' => Auth::id(),
                    ':id' => $id,
                ]
            );
            return $id;
        }

        Database::run(
            'INSERT INTO `documentos_emitidos`
                (`tipo`, `referencia_id`, `numero`, `modelo`, `dados_json`, `emitido_por_id`, `emitido_em`)
             VALUES (:tipo, :ref, :numero, :modelo, :dados, :usuario, NOW())',
            $valores
        );

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluirDocumento(int $id): void
    {
        Database::run('DELETE FROM `documentos_emitidos` WHERE `id` = :id', [':id' => $id]);
    }

    /** @return array<string, mixed> */
    public static function estatisticas(): array
    {
        $linha = Database::run(
            'SELECT
                (SELECT COUNT(*) FROM `convites`) AS `convites`,
                (SELECT COUNT(*) FROM `documentos_emitidos`) AS `documentos`,
                (SELECT COUNT(*) FROM `documentos_emitidos` WHERE `tipo` = :convite) AS `cartas`,
                (SELECT COUNT(*) FROM `documentos_emitidos` WHERE `tipo` LIKE :diploma) AS `diplomas`
             FROM DUAL',
            [
                ':convite' => 'carta_convite',
                ':diploma' => 'diploma_%',
            ]
        )->fetch();

        return $linha === false ? [] : $linha;
    }

    /** @param array<string, mixed> $convite @param array<string, mixed> $dados @return array<string, mixed> */
    public static function dadosConvite(array $convite, array $dados = []): array
    {
        $registro = [
            'numero' => (string) ($convite['numero'] ?? ''),
            'convidado' => (string) ($convite['convidado'] ?? ''),
            'igreja_convidada' => (string) ($convite['igreja_convidada'] ?? ''),
            'endereco' => (string) ($convite['endereco'] ?? ''),
            'nome_evento' => (string) ($convite['nome_evento'] ?? ''),
            'data_evento' => (string) ($convite['data_evento'] ?? ''),
            'local' => (string) ($convite['local'] ?? ''),
            'pastor_responsavel' => (string) ($convite['pastor_responsavel'] ?? ''),
            'assinaturas' => (string) ($convite['assinaturas'] ?? ''),
            'modelo' => (string) ($convite['modelo'] ?? 'classico'),
        ];

        foreach (array_keys(self::DADOS_JURIDICOS) as $campo) {
            $registro[$campo] = (string) ($dados[$campo] ?? '');
        }

        return $registro;
    }

    /** @return array<string, mixed> */
    public static function dadosDocumento(array $documento): array
    {
        $json = (string) ($documento['dados_json'] ?? '');
        if ($json === '') {
            return [];
        }
        $dados = json_decode($json, true);
        return is_array($dados) ? $dados : [];
    }

    public static function rotuloTipo(string $tipo): string
    {
        return self::TIPOS[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }

    public static function rotuloModelo(string $modelo): string
    {
        return self::MODELOS[$modelo] ?? ucfirst($modelo);
    }

    public static function configuracao(string $chave, string $padrao = ''): string
    {
        return SiteRepository::config($chave, $padrao);
    }

    /** @return array<string, string> */
    public static function dadosJuridicos(): array
    {
        return self::DADOS_JURIDICOS;
    }

    public static function valorJuridicoPadrao(string $campo): string
    {
        $chave = self::DADOS_JURIDICOS_CONFIG[$campo] ?? '';
        if ($chave === '') {
            return '';
        }

        $valor = self::configuracao($chave, '');
        if ($campo === 'razao_social' && $valor === '') {
            return self::configuracao('ministerio_nome', 'Assembleia de Deus');
        }

        return $valor;
    }

    /** @param array<string, mixed> $convite @param array<string, mixed> $dados @return array<string, string> */
    public static function valoresFormulario(array $convite, array $dados = []): array
    {
        $data = (string) ($convite['data_evento'] ?? '');
        $valores = [
            'convidado' => (string) ($convite['convidado'] ?? ''),
            'igreja_convidada' => (string) ($convite['igreja_convidada'] ?? ''),
            'endereco' => (string) ($convite['endereco'] ?? ''),
            'nome_evento' => (string) ($convite['nome_evento'] ?? ''),
            'data_evento' => $data === '' ? '' : str_replace(' ', 'T', substr($data, 0, 16)),
            'local' => (string) ($convite['local'] ?? ''),
            'pastor_responsavel' => (string) ($convite['pastor_responsavel'] ?? ''),
            'assinaturas' => (string) ($convite['assinaturas'] ?? ''),
            'modelo' => (string) ($convite['modelo'] ?? 'classico'),
        ];

        foreach (array_keys(self::DADOS_JURIDICOS) as $campo) {
            $valores[$campo] = (string) ($dados[$campo] ?? '');
        }

        return $valores;
    }

    private static function dataValida(string $valor): bool
    {
        if ($valor === '') {
            return false;
        }
        $data = DateTime::createFromFormat('!Y-m-d', $valor);
        return $data !== false && $data->format('Y-m-d') === $valor;
    }
}
