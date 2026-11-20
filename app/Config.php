<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class Config
{
    /** @var array<string, string> */
    private static array $env = [];

    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $envFile, string $settingsFile): void
    {
        self::loadEnv($envFile);
        self::loadSettings($settingsFile);
    }

    public static function loadEnv(string $envFile): void
    {
        self::$env = self::parseEnv($envFile);
    }

    public static function loadSettings(string $settingsFile): void
    {
        $settings = require $settingsFile;
        if (!is_array($settings)) {
            throw new RuntimeException('Arquivo de configuracao invalido: ' . basename($settingsFile));
        }

        self::$items = $settings;
    }

    /** @return array<string, string> */
    private static function parseEnv(string $envFile): array
    {
        if (!is_file($envFile)) {
            return [];
        }

        $linhas = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhas === false) {
            return [];
        }

        $dados = [];
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }
            if (str_starts_with($linha, 'export ')) {
                $linha = trim(substr($linha, 7));
            }
            $posicao = strpos($linha, '=');
            if ($posicao === false) {
                continue;
            }
            $chave = trim(substr($linha, 0, $posicao));
            $valor = trim(substr($linha, $posicao + 1));
            if ($valor !== '' && in_array($valor[0], ['"', "'"], true)) {
                $aspas = $valor[0];
                $valor = substr($valor, 1);
                if (str_ends_with($valor, $aspas)) {
                    $valor = substr($valor, 0, -1);
                }
            }
            if ($chave !== '') {
                $dados[$chave] = $valor;
            }
        }

        return $dados;
    }

    public static function env(string $chave, mixed $padrao = null): mixed
    {
        return self::$env[$chave] ?? $padrao;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        $partes = explode('.', $chave);
        $atual = self::$items;

        foreach ($partes as $parte) {
            if (!is_array($atual) || !array_key_exists($parte, $atual)) {
                return $padrao;
            }
            $atual = $atual[$parte];
        }

        return $atual;
    }
}
