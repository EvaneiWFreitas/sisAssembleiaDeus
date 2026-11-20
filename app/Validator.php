<?php
declare(strict_types=1);

namespace App;

use DateTime;

final class Validator
{
    /** @param array<string, mixed> $dados @param array<string, string> $regras @return array{ok:bool,errors:array<string,string>,data:array<string,mixed>} */
    public static function validate(array $dados, array $regras): array
    {
        $erros = [];
        $limpos = [];

        foreach ($regras as $campo => $definicao) {
            $valor = $dados[$campo] ?? null;
            $valor = is_string($valor) ? trim($valor) : $valor;
            $limpos[$campo] = $valor;

            $regrasCampo = explode('|', $definicao);
            $obrigatorio = in_array('required', $regrasCampo, true);
            $vazio = $valor === null || $valor === '';

            if ($vazio && !$obrigatorio) {
                continue;
            }

            foreach ($regrasCampo as $regra) {
                if ($regra === 'required') {
                    if ($vazio) {
                        $erros[$campo] = 'Este campo é obrigatório.';
                    }
                    continue;
                }
                if ($vazio) {
                    continue;
                }

                $partes = explode(':', $regra, 2);
                $nome = $partes[0];
                $argumento = $partes[1] ?? '';

                $mensagem = match ($nome) {
                    'email' => filter_var($valor, FILTER_VALIDATE_EMAIL) === false
                        ? 'Informe um e-mail válido.' : null,
                    'cpf' => self::cpf($valor) ? null : 'Informe um CPF válido.',
                    'numeric' => is_numeric($valor) ? null : 'Informe um valor numérico.',
                    'positive' => is_numeric($valor) && (float) $valor > 0 ? null : 'Informe um valor maior que zero.',
                    'integer' => filter_var($valor, FILTER_VALIDATE_INT) === false ? 'Informe um número inteiro.' : null,
                    'month' => self::mes($valor) ? null : 'Informe o mês no formato AAAA-MM.',
                    'date' => self::data($valor) ? null : 'Informe uma data válida (AAAA-MM-DD).',
                    'datetime' => self::dataHora($valor) ? null : 'Informe data e hora válidas (AAAA-MM-DDTHH:MM).',
                    'min' => mb_strlen((string) $valor) < (int) $argumento
                        ? 'Deve ter no mínimo ' . (int) $argumento . ' caracteres.' : null,
                    'max' => mb_strlen((string) $valor) > (int) $argumento
                        ? 'Deve ter no máximo ' . (int) $argumento . ' caracteres.' : null,
                    'in' => in_array((string) $valor, explode(',', $argumento), true)
                        ? null : 'Valor não permitido.',
                    'unique' => self::unique($valor, $argumento) ? 'Este valor já está cadastrado.' : null,
                    default => null,
                };

                if ($mensagem !== null && !isset($erros[$campo])) {
                    $erros[$campo] = $mensagem;
                }
            }
        }

        return ['ok' => $erros === [], 'errors' => $erros, 'data' => $limpos];
    }

    private static function data(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $data = DateTime::createFromFormat('!Y-m-d', $valor);
        return $data !== false && $data->format('Y-m-d') === $valor;
    }

    private static function mes(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $mes = DateTime::createFromFormat('!Y-m', $valor);
        return $mes !== false && $mes->format('Y-m') === $valor;
    }

    private static function dataHora(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $dataHora = DateTime::createFromFormat('!Y-m-d\TH:i', $valor);
        return $dataHora !== false && $dataHora->format('Y-m-d\TH:i') === $valor;
    }

    private static function unique(mixed $valor, string $argumento): bool
    {
        $partes = explode(',', $argumento);
        if (count($partes) < 2) {
            return false;
        }

        $tabela = $partes[0];
        $coluna = $partes[1];
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tabela) !== 1
            || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $coluna) !== 1) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS total FROM `' . $tabela . '` WHERE `' . $coluna . '` = :valor';
        $parametros = [':valor' => $valor];

        if (isset($partes[2], $partes[3])
            && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $partes[2]) === 1
            && is_numeric($partes[3])) {
            $sql .= ' AND `' . $partes[2] . '` <> :id';
            $parametros[':id'] = (int) $partes[3];
        }

        $stmt = Database::run($sql, $parametros);
        return (int) ($stmt->fetch()['total'] ?? 0) > 0;
    }

    public static function cpf(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $cpf = preg_replace('/\D/', '', $valor) ?? '';
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($tamanho = 9; $tamanho <= 10; $tamanho++) {
            $soma = 0;
            for ($i = 0; $i < $tamanho; $i++) {
                $soma += (int) $cpf[$i] * ($tamanho + 1 - $i);
            }
            $digest = ($soma * 10) % 11;
            $digest = $digest === 10 ? 0 : $digest;
            if ($digest !== (int) $cpf[$tamanho]) {
                return false;
            }
        }

        return true;
    }
}
