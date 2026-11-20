<?php
declare(strict_types=1);

namespace App;

final class ConfiguracaoRepository
{
    private const GRUPOS = ['identidade', 'contato', 'redes', 'pastoral', 'publico', 'geral', 'seguranca'];

    public static function listar(): array
    {
        return Database::run(
            'SELECT * FROM `configuracoes`
              ORDER BY FIELD(`grupo`, "identidade", "contato", "redes", "pastoral", "publico", "geral", "seguranca") ASC, `id` ASC'
        )->fetchAll();
    }

    public static function agrupar(array $configuracoes): array
    {
        $grupos = [];
        foreach (self::GRUPOS as $grupo) {
            $grupos[$grupo] = [];
        }

        foreach ($configuracoes as $configuracao) {
            $grupo = (string) ($configuracao['grupo'] ?? 'geral');
            if (!isset($grupos[$grupo])) {
                $grupos[$grupo] = [];
            }
            $grupos[$grupo][] = $configuracao;
        }

        return array_filter($grupos, static fn (array $itens): bool => $itens !== []);
    }

    public static function rotuloGrupo(string $grupo): string
    {
        return match ($grupo) {
            'identidade' => 'Identidade visual',
            'contato' => 'Contato e endereço',
            'redes' => 'Redes sociais',
            'pastoral' => 'Pastoral',
            'publico' => 'Conteúdo público',
            'geral' => 'Geral',
            'seguranca' => 'Segurança',
            default => ucfirst($grupo),
        };
    }

    public static function valores(array $configuracoes): array
    {
        $valores = [];
        foreach ($configuracoes as $configuracao) {
            $chave = (string) ($configuracao['chave'] ?? '');
            if ($chave !== '') {
                $valores[$chave] = (string) ($configuracao['valor'] ?? '');
            }
        }

        return $valores;
    }

    public static function salvar(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            Database::run(
                'UPDATE `configuracoes` SET `valor` = :valor, `updated_at` = NOW() WHERE `chave` = :chave',
                [
                    ':valor' => (string) $valor,
                    ':chave' => (string) $chave,
                ]
            );
        }
    }
}
