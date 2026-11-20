<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class BackupRepository
{
    private const LIMITES = [50, 100, 200, 500];
    private const STATUS = ['gerando', 'concluido', 'falhado'];

    public static function filtros(array $query): array
    {
        $limite = (int) ($query['limite'] ?? 100);
        $status = trim((string) ($query['status'] ?? ''));

        return [
            'de' => self::data((string) ($query['de'] ?? '')),
            'ate' => self::data((string) ($query['ate'] ?? '')),
            'status' => in_array($status, self::STATUS, true) ? $status : '',
            'q' => trim((string) ($query['q'] ?? '')),
            'limite' => in_array($limite, self::LIMITES, true) ? $limite : 100,
        ];
    }

    public static function listar(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);

        return Database::run(
            'SELECT b.*, u.`nome` AS `usuario_nome`
               FROM `backups` b
               LEFT JOIN `usuarios` u ON u.`id` = b.`gerado_por_id`
              WHERE 1 = 1' . $condicoes . '
              ORDER BY b.`gerado_em` DESC, b.`id` DESC
              LIMIT ' . (int) $filtros['limite'],
            $parametros
        )->fetchAll();
    }

    public static function estatisticas(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    COALESCE(SUM(b.`tamanho_bytes`), 0) AS `bytes`,
                    SUM(CASE WHEN b.`status` = "concluido" THEN 1 ELSE 0 END) AS `concluidos`,
                    SUM(CASE WHEN b.`status` = "falhado" THEN 1 ELSE 0 END) AS `falhados`
               FROM `backups` b
               LEFT JOIN `usuarios` u ON u.`id` = b.`gerado_por_id`
              WHERE 1 = 1' . $condicoes,
            $parametros
        )->fetch();

        return is_array($linha) ? $linha : ['total' => 0, 'bytes' => 0, 'concluidos' => 0, 'falhados' => 0];
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT b.*, u.`nome` AS `usuario_nome`
               FROM `backups` b
               LEFT JOIN `usuarios` u ON u.`id` = b.`gerado_por_id`
              WHERE b.`id` = :id
              LIMIT 1',
            [':id' => $id]
        )->fetch();

        return is_array($registro) ? $registro : null;
    }

    public static function criar(int $usuarioId): array
    {
        $nome = self::novoNome();
        Database::run(
            'INSERT INTO `backups` (`nome_arquivo`, `gerado_por_id`, `status`) VALUES (:arquivo, :usuario, :status)',
            [':arquivo' => $nome, ':usuario' => $usuarioId, ':status' => 'gerando']
        );
        $id = (int) Database::connection()->lastInsertId();
        $inicio = microtime(true);
        $caminho = self::caminho($nome);

        try {
            [$conteudo, $tabelas] = self::gerarDump();
            self::garantirDiretorio(dirname($caminho));
            self::escrever($caminho, $conteudo);
            $duracao = (int) round((microtime(true) - $inicio) * 1000);
            self::finalizar($id, strlen($conteudo), $tabelas, $duracao, 'concluido', null);
        } catch (Throwable $exception) {
            $duracao = (int) round((microtime(true) - $inicio) * 1000);
            if (is_file($caminho)) {
                @unlink($caminho);
            }
            if (is_file($caminho . '.tmp')) {
                @unlink($caminho . '.tmp');
            }
            self::finalizar($id, 0, 0, $duracao, 'falhado', mb_substr($exception->getMessage(), 0, 255));
            throw $exception;
        }

        $registro = self::buscar($id);

        return is_array($registro) ? $registro : [
            'id' => $id,
            'nome_arquivo' => $nome,
            'status' => 'concluido',
        ];
    }

    public static function conteudo(int $id): array
    {
        $registro = self::buscar($id);
        if ($registro === null) {
            throw new RuntimeException('Backup não encontrado.');
        }

        $caminho = self::caminho((string) $registro['nome_arquivo']);
        if (!is_file($caminho) || !is_readable($caminho)) {
            throw new RuntimeException('Arquivo de backup indisponível.');
        }

        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo de backup.');
        }

        return [$registro, $conteudo];
    }

    public static function excluir(int $id): ?array
    {
        $registro = self::buscar($id);
        if ($registro === null) {
            return null;
        }

        $caminho = self::caminho((string) $registro['nome_arquivo']);
        if (is_file($caminho) && !unlink($caminho)) {
            throw new RuntimeException('Não foi possível excluir o arquivo de backup.');
        }

        Database::run('DELETE FROM `backups` WHERE `id` = :id', [':id' => $id]);

        return $registro;
    }

    private static function finalizar(int $id, int $bytes, int $tabelas, int $duracao, string $status, ?string $observacoes): void
    {
        Database::run(
            'UPDATE `backups`
                SET `tamanho_bytes` = :bytes,
                    `tabelas` = :tabelas,
                    `duracao_ms` = :duracao,
                    `status` = :status,
                    `observacoes` = :observacoes
              WHERE `id` = :id',
            [
                ':bytes' => $bytes,
                ':tabelas' => $tabelas,
                ':duracao' => $duracao,
                ':status' => $status,
                ':observacoes' => $observacoes,
                ':id' => $id,
            ]
        );
    }

    private static function gerarDump(): array
    {
        $pdo = Database::connection();
        $tabelas = $pdo->query("SHOW FULL TABLES WHERE `Table_type` = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $linhas = [
            '-- sisAssembleiaDeus · dump de banco de dados',
            '-- Gerado em ' . date('Y-m-d H:i:s'),
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS = 0;',
            'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
            'START TRANSACTION;',
        ];

        foreach ($tabelas as $tabela) {
            $tabela = (string) $tabela;
            $estrutura = $pdo->query('SHOW CREATE TABLE ' . self::identificador($tabela))->fetch(PDO::FETCH_NUM);
            $criacao = rtrim((string) ($estrutura[1] ?? ''), "; \t\n\r\0\x0B");
            $registros = $pdo->query('SELECT * FROM ' . self::identificador($tabela))->fetchAll(PDO::FETCH_ASSOC);

            $linhas[] = '';
            $linhas[] = '-- Tabela: ' . $tabela;
            $linhas[] = 'DROP TABLE IF EXISTS ' . self::identificador($tabela) . ';';
            $linhas[] = $criacao . ';';

            if ($registros === []) {
                continue;
            }

            $colunas = array_keys($registros[0]);
            $linhas[] = 'INSERT INTO ' . self::identificador($tabela)
                . ' (' . implode(', ', array_map([self::class, 'identificador'], $colunas)) . ') VALUES';

            $blocos = [];
            foreach ($registros as $registro) {
                $valores = [];
                foreach ($colunas as $coluna) {
                    $valores[] = self::valorSql($pdo, $registro[$coluna] ?? null);
                }
                $blocos[] = '(' . implode(', ', $valores) . ')';
            }
            $linhas[] = implode(",\n", $blocos) . ';';
        }

        $linhas[] = '';
        $linhas[] = 'COMMIT;';
        $linhas[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        return [implode("\n", $linhas), count($tabelas)];
    }

    private static function identificador(string $nome): string
    {
        return '`' . str_replace('`', '``', $nome) . '`';
    }

    private static function valorSql(PDO $pdo, mixed $valor): string
    {
        if ($valor === null) {
            return 'NULL';
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_int($valor)) {
            return (string) $valor;
        }
        if (is_float($valor)) {
            return str_replace(',', '.', (string) $valor);
        }

        $quote = $pdo->quote((string) $valor);

        return $quote === false ? "''" : $quote;
    }

    private static function novoNome(): string
    {
        return 'backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
    }

    private static function caminho(string $arquivo): string
    {
        if (preg_match('/^backup_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\.sql$/', $arquivo) !== 1) {
            throw new RuntimeException('Nome de arquivo de backup inválido.');
        }

        return storage_path('backups/' . basename($arquivo));
    }

    private static function garantirDiretorio(string $diretorio): void
    {
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível criar o diretório de backups.');
        }
        if (!is_writable($diretorio)) {
            throw new RuntimeException('Diretório de backups sem permissão de escrita.');
        }
    }

    private static function escrever(string $caminho, string $conteudo): void
    {
        $temporario = $caminho . '.tmp';
        $handle = fopen($temporario, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Não foi possível criar o arquivo de backup.');
        }

        try {
            $total = strlen($conteudo);
            $offset = 0;
            while ($offset < $total) {
                $escrito = fwrite($handle, substr($conteudo, $offset));
                if ($escrito === false || $escrito === 0) {
                    throw new RuntimeException('Falha ao gravar o arquivo de backup.');
                }
                $offset += $escrito;
            }
        } finally {
            fclose($handle);
        }

        if (!rename($temporario, $caminho)) {
            @unlink($temporario);
            throw new RuntimeException('Não foi possível finalizar o arquivo de backup.');
        }
    }

    private static function data(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $data !== false && $data->format('Y-m-d') === $valor ? $valor : '';
    }

    private static function condicoes(array $filtros): array
    {
        $sql = '';
        $parametros = [];

        if (($filtros['de'] ?? '') !== '') {
            $sql .= ' AND b.`gerado_em` >= :de';
            $parametros[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (($filtros['ate'] ?? '') !== '') {
            $sql .= ' AND b.`gerado_em` <= :ate';
            $parametros[':ate'] = $filtros['ate'] . ' 23:59:59';
        }
        if (($filtros['status'] ?? '') !== '') {
            $sql .= ' AND b.`status` = :status';
            $parametros[':status'] = $filtros['status'];
        }
        if (($filtros['q'] ?? '') !== '') {
            $termo = '%' . $filtros['q'] . '%';
            $sql .= ' AND (b.`nome_arquivo` LIKE :q_arquivo OR u.`nome` LIKE :q_usuario OR b.`observacoes` LIKE :q_obs)';
            $parametros[':q_arquivo'] = $termo;
            $parametros[':q_usuario'] = $termo;
            $parametros[':q_obs'] = $termo;
        }

        return [$sql, $parametros];
    }
}
