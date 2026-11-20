<?php
declare(strict_types=1);

namespace App;

final class Response
{
    private int $status;

    /** @var array<string, string> */
    private array $headers;

    public function __construct(
        private readonly string $body = '',
        int $status = 200,
        array $headers = []
    ) {
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** @param array<string, mixed> $dados */
    public static function json(array $dados, int $status = 200): self
    {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"erro":"Falha ao codificar resposta"}';
            $status = 500;
        }

        return new self($json, $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public static function redirect(string $destino, int $status = 302): self
    {
        return new self('', $status, ['Location' => $destino]);
    }

    public static function error(int $status, string $mensagem): self
    {
        $titulos = [
            400 => 'Requisição inválida',
            403 => 'Acesso negado',
            404 => 'Página não encontrada',
            405 => 'Método não permitido',
            419 => 'Sessão expirada',
            500 => 'Erro interno do servidor',
        ];
        $titulo = $titulos[$status] ?? 'Erro';
        $escape = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $status . ' - ' . $escape($titulo) . '</title>'
            . '<style>body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;color:#1f2937;display:grid;place-items:center;min-height:100vh}'
            . '.box{background:#fff;border-top:4px solid #c9a227;border-radius:10px;padding:36px;max-width:520px;box-shadow:0 2px 16px rgba(11,37,69,.12)}'
            . 'h1{margin:0 0 8px;color:#0b2545;font-size:1.5rem}p{margin:0;color:#4b5563}</style></head><body><div class="box">'
            . '<h1>' . $status . ' - ' . $escape($titulo) . '</h1><p>' . $escape($mensagem) . '</p></div></body></html>';

        return self::html($html, $status);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function withHeader(string $nome, string $valor): self
    {
        $this->headers[$nome] = $valor;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $nome => $valor) {
            header($nome . ': ' . $valor, true);
        }
        echo $this->body;
    }
}
