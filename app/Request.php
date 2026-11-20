<?php
declare(strict_types=1);

namespace App;

final class Request
{
    private static ?Request $current = null;

    /** @param array<string, mixed> $query @param array<string, mixed> $post @param array<string, mixed> $files @param array<string, mixed> $cookies @param array<string, mixed> $server */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $cookies,
        public readonly array $server,
        public readonly string $ip,
        public readonly string $userAgent,
        public readonly string $rawBody
    ) {
    }

    public static function createFromGlobals(): self
    {
        $server = $_SERVER;
        $query = $_GET;
        unset($query['rota']);

        $rota = isset($_GET['rota']) ? trim((string) $_GET['rota']) : '';
        if ($rota === '') {
            $uri = parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
            $uri = is_string($uri) ? $uri : '/';
            $base = (string) config('app.base_path', '');
            if ($base !== '' && str_starts_with($uri, $base)) {
                $uri = substr($uri, strlen($base));
            }
            $rota = $uri;
        }

        $rota = preg_replace('/[^A-Za-z0-9\/_\-\.]/', '', $rota) ?? '';
        $path = '/' . trim($rota, '/');

        $ip = (string) ($server['REMOTE_ADDR'] ?? '');
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = '';
        }

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $query,
            $_POST,
            $_FILES,
            $_COOKIE,
            $server,
            $ip,
            substr((string) ($server['HTTP_USER_AGENT'] ?? ''), 0, 255),
            (string) file_get_contents('php://input')
        );
    }

    public static function setCurrent(self $request): void
    {
        self::$current = $request;
    }

    public static function current(): self
    {
        if (self::$current === null) {
            self::$current = self::createFromGlobals();
        }

        return self::$current;
    }

    public function input(string $chave, mixed $padrao = null): mixed
    {
        if (array_key_exists($chave, $this->post)) {
            return $this->post[$chave];
        }

        return $this->query[$chave] ?? $padrao;
    }

    public function query(string $chave, mixed $padrao = null): mixed
    {
        return $this->query[$chave] ?? $padrao;
    }

    public function post(?string $chave = null, mixed $padrao = null): mixed
    {
        if ($chave === null) {
            return $this->post;
        }

        return $this->post[$chave] ?? $padrao;
    }

    /** @return array<string, mixed>|null */
    public function file(string $chave): ?array
    {
        $arquivo = $this->files[$chave] ?? null;
        return is_array($arquivo) ? $arquivo : null;
    }

    public function isMethod(string $metodo): bool
    {
        return $this->method === strtoupper($metodo);
    }

    public function header(string $nome): ?string
    {
        $chave = 'HTTP_' . strtoupper(str_replace('-', '_', $nome));
        $valor = $this->server[$chave] ?? null;

        return is_string($valor) ? $valor : null;
    }
}
