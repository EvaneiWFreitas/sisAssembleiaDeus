<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use finfo;
use RuntimeException;

final class Upload
{
    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** @param array<string, mixed> $arquivo */
    public static function image(array $arquivo, string $pasta, string $prefixo = ''): string
    {
        if (!isset($arquivo['error']) || !is_int($arquivo['error'])) {
            throw new InvalidArgumentException('Arquivo de imagem invalido.');
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Falha no envio da imagem. Código: ' . $arquivo['error']);
        }

        $tamanhoMaximo = max(1, (int) config('security.upload_max_mb', 5)) * 1024 * 1024;
        $tamanho = (int) ($arquivo['size'] ?? 0);
        if ($tamanho <= 0 || $tamanho > $tamanhoMaximo) {
            throw new InvalidArgumentException('A imagem deve ter entre 1 byte e ' . (int) config('security.upload_max_mb', 5) . ' MB.');
        }

        $temporario = (string) ($arquivo['tmp_name'] ?? '');
        if ($temporario === '' || !is_uploaded_file($temporario)) {
            throw new InvalidArgumentException('Envio de arquivo nao autorizado.');
        }

        $mime = new finfo(FILEINFO_MIME_TYPE);
        $tipo = (string) $mime->file($temporario);
        if (!isset(self::TIPOS[$tipo])) {
            throw new InvalidArgumentException('Formato de imagem nao permitido. Use JPG, PNG, WEBP ou GIF.');
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $pasta) !== 1) {
            throw new InvalidArgumentException('Pasta de destino invalida.');
        }

        $diretorio = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $pasta;
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Nao foi possivel criar a pasta de uploads.');
        }

        $nomeBase = slugify($prefixo);
        $nome = ($nomeBase === '' ? '' : $nomeBase . '-') . bin2hex(random_bytes(12)) . '.' . self::TIPOS[$tipo];
        $destino = $diretorio . DIRECTORY_SEPARATOR . $nome;

        if (!move_uploaded_file($temporario, $destino)) {
            throw new RuntimeException('Nao foi possivel salvar a imagem.');
        }

        return 'uploads/' . $pasta . '/' . $nome;
    }

    public static function excluir(?string $caminho): void
    {
        if ($caminho === null || $caminho === '') {
            return;
        }
        if (str_starts_with($caminho, 'uploads/') === false || str_contains($caminho, '..')) {
            return;
        }

        $arquivo = PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminho);
        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }
}
