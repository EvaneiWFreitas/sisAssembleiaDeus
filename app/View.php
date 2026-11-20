<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/site'): string
    {
        $conteudo = self::capture($view, $data);
        return self::capture($layout, array_merge($data, ['conteudo' => $conteudo]));
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        $arquivo = self::arquivo($view);
        extract($data, EXTR_SKIP);

        ob_start();
        require $arquivo;

        return (string) ob_get_clean();
    }

    private static function arquivo(string $view): string
    {
        $partes = [];
        foreach (explode('/', str_replace('\\', '/', $view)) as $parte) {
            if ($parte === '' || $parte === '.' || $parte === '..') {
                continue;
            }
            $partes[] = $parte;
        }

        $arquivo = base_path('app/views/' . implode('/', $partes) . '.php');
        if (!is_file($arquivo)) {
            throw new RuntimeException('View nao encontrada: ' . $view);
        }

        return $arquivo;
    }
}
