<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;

final class AuditoriaRepository
{
    private const LIMITES = [50, 100, 200, 500];

    public static function filtros(array $query): array
    {
        $limite = (int) ($query['limite'] ?? 100);

        return [
            'de' => self::data((string) ($query['de'] ?? '')),
            'ate' => self::data((string) ($query['ate'] ?? '')),
            'usuario_id' => ctype_digit((string) ($query['usuario_id'] ?? '')) ? (int) $query['usuario_id'] : 0,
            'acao' => trim((string) ($query['acao'] ?? '')),
            'modulo' => trim((string) ($query['modulo'] ?? '')),
            'entidade' => trim((string) ($query['entidade'] ?? '')),
            'q' => trim((string) ($query['q'] ?? '')),
            'limite' => in_array($limite, self::LIMITES, true) ? $limite : 100,
        ];
    }

    public static function listar(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);

        return Database::run(
            'SELECT a.*, u.`nome` AS `usuario_nome`, u.`email` AS `usuario_email`
               FROM `auditoria` a
               LEFT JOIN `usuarios` u ON u.`id` = a.`usuario_id`
              WHERE 1 = 1' . $condicoes . '
              ORDER BY a.`created_at` DESC, a.`id` DESC
              LIMIT ' . (int) $filtros['limite'],
            $parametros
        )->fetchAll();
    }

    public static function estatisticas(array $filtros): array
    {
        [$condicoes, $parametros] = self::condicoes($filtros);
        $linha = Database::run(
            'SELECT COUNT(*) AS `total`,
                    COUNT(DISTINCT a.`modulo`) AS `modulos`,
                    COUNT(DISTINCT a.`usuario_id`) AS `usuarios`,
                    SUM(CASE WHEN a.`created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS `ultimos_7_dias`
               FROM `auditoria` a
               LEFT JOIN `usuarios` u ON u.`id` = a.`usuario_id`
              WHERE 1 = 1' . $condicoes,
            $parametros
        )->fetch();

        return is_array($linha) ? $linha : ['total' => 0, 'modulos' => 0, 'usuarios' => 0, 'ultimos_7_dias' => 0];
    }

    public static function buscar(int $id): ?array
    {
        $registro = Database::run(
            'SELECT a.*, u.`nome` AS `usuario_nome`, u.`email` AS `usuario_email`
               FROM `auditoria` a
               LEFT JOIN `usuarios` u ON u.`id` = a.`usuario_id`
              WHERE a.`id` = :id
              LIMIT 1',
            [':id' => $id]
        )->fetch();

        return is_array($registro) ? $registro : null;
    }

    public static function acoes(): array
    {
        return self::valoresDistintos('acao');
    }

    public static function modulos(): array
    {
        return self::valoresDistintos('modulo');
    }

    public static function entidades(): array
    {
        return self::valoresDistintos('entidade');
    }

    public static function usuarios(): array
    {
        return Database::run(
            'SELECT u.`id`, u.`nome`, u.`email` FROM `usuarios` u ORDER BY u.`nome` ASC'
        )->fetchAll();
    }

    public static function rotuloAcao(string $acao): string
    {
        return match ($acao) {
            'criar' => 'Criação',
            'editar' => 'Edição',
            'excluir' => 'Exclusão',
            'publicar' => 'Publicação',
            'exportar' => 'Exportação',
            'imprimir' => 'Impressão',
            default => ucfirst($acao),
        };
    }

    public static function jsonSeguro(?string $json): string
    {
        $valor = trim((string) $json);
        if ($valor === '') {
            return '—';
        }

        $dados = json_decode($valor, true);
        if (!is_array($dados)) {
            return $valor;
        }

        $limpos = self::redigir($dados);
        $formatado = json_encode($limpos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $formatado === false ? $valor : $formatado;
    }

    private static function valoresDistintos(string $coluna): array
    {
        $permitidas = ['acao', 'modulo', 'entidade'];
        if (!in_array($coluna, $permitidas, true)) {
            return [];
        }

        return Database::run(
            'SELECT DISTINCT `' . $coluna . '` AS `valor` FROM `auditoria`
              WHERE `' . $coluna . '` IS NOT NULL AND `' . $coluna . '` <> ""
              ORDER BY `' . $coluna . '` ASC'
        )->fetchAll();
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
            $sql .= ' AND a.`created_at` >= :de';
            $parametros[':de'] = $filtros['de'] . ' 00:00:00';
        }
        if (($filtros['ate'] ?? '') !== '') {
            $sql .= ' AND a.`created_at` <= :ate';
            $parametros[':ate'] = $filtros['ate'] . ' 23:59:59';
        }
        if ((int) ($filtros['usuario_id'] ?? 0) > 0) {
            $sql .= ' AND a.`usuario_id` = :usuario_id';
            $parametros[':usuario_id'] = (int) $filtros['usuario_id'];
        }
        if (($filtros['acao'] ?? '') !== '') {
            $sql .= ' AND a.`acao` = :acao';
            $parametros[':acao'] = $filtros['acao'];
        }
        if (($filtros['modulo'] ?? '') !== '') {
            $sql .= ' AND a.`modulo` = :modulo';
            $parametros[':modulo'] = $filtros['modulo'];
        }
        if (($filtros['entidade'] ?? '') !== '') {
            $sql .= ' AND a.`entidade` = :entidade';
            $parametros[':entidade'] = $filtros['entidade'];
        }
        if (($filtros['q'] ?? '') !== '') {
            $termo = '%' . $filtros['q'] . '%';
            $sql .= ' AND (a.`acao` LIKE :q_acao OR a.`modulo` LIKE :q_modulo OR a.`entidade` LIKE :q_entidade OR u.`nome` LIKE :q_usuario OR CAST(a.`entidade_id` AS CHAR) LIKE :q_id)';
            $parametros[':q_acao'] = $termo;
            $parametros[':q_modulo'] = $termo;
            $parametros[':q_entidade'] = $termo;
            $parametros[':q_usuario'] = $termo;
            $parametros[':q_id'] = $termo;
        }

        return [$sql, $parametros];
    }

    private static function redigir(mixed $valor): mixed
    {
        if (!is_array($valor)) {
            return $valor;
        }

        $limpo = [];
        foreach ($valor as $chave => $item) {
            if (is_string($chave) && preg_match('/(senha|password|token|hash|secret)/i', $chave) === 1) {
                $limpo[$chave] = '***';
                continue;
            }
            $limpo[$chave] = self::redigir($item);
        }

        return $limpo;
    }
}
