<?php
declare(strict_types=1);

$autoload = static function (string $classe): void {
    $prefixo = 'App\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }

    $relativo = substr($classe, strlen($prefixo));
    $arquivo = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $relativo) . '.php';

    if (is_file($arquivo)) {
        require $arquivo;
    }
};

spl_autoload_register($autoload);
