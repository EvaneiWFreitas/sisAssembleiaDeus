<?php
declare(strict_types=1);

namespace App;

use Throwable;

final class Audit
{
    public static function registrar(
        string $acao,
        string $modulo,
        ?string $entidade = null,
        ?int $entidadeId = null,
        mixed $anterior = null,
        mixed $novo = null
    ): void {
        $request = Request::current();

        try {
            Database::run(
                'INSERT INTO `auditoria`
                    (`usuario_id`, `acao`, `modulo`, `entidade`, `entidade_id`, `dados_anteriores`, `dados_novos`, `ip`, `user_agent`)
                 VALUES
                    (:usuario, :acao, :modulo, :entidade, :entidade_id, :anterior, :novo, :ip, :agente)',
                [
                    ':usuario' => Auth::id(),
                    ':acao' => mb_substr($acao, 0, 80),
                    ':modulo' => mb_substr($modulo, 0, 60),
                    ':entidade' => $entidade === null ? null : mb_substr($entidade, 0, 60),
                    ':entidade_id' => $entidadeId,
                    ':anterior' => self::codificar($anterior),
                    ':novo' => self::codificar($novo),
                    ':ip' => $request->ip !== '' ? $request->ip : null,
                    ':agente' => $request->userAgent !== '' ? $request->userAgent : null,
                ]
            );
        } catch (Throwable $exception) {
            error_log('Falha ao gravar auditoria: ' . $exception->getMessage());
        }
    }

    private static function codificar(mixed $dados): ?string
    {
        if ($dados === null) {
            return null;
        }
        $json = json_encode(self::sanitizar($dados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    private static function sanitizar(mixed $dados): mixed
    {
        if (is_array($dados)) {
            $limpo = [];
            foreach ($dados as $chave => $valor) {
                if (is_string($chave) && preg_match('/(senha|password|token|hash|secret)/i', $chave) === 1) {
                    continue;
                }
                $limpo[$chave] = self::sanitizar($valor);
            }
            return $limpo;
        }
        if (is_object($dados)) {
            return self::sanitizar((array) $dados);
        }
        if (is_bool($dados) || is_int($dados) || is_float($dados) || $dados === null) {
            return $dados;
        }

        return mb_substr((string) $dados, 0, 2000);
    }
}
