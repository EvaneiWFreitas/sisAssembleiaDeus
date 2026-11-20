<?php
declare(strict_types=1);

$publicPath = __DIR__;
$basePath = dirname($publicPath);

define('PUBLIC_PATH', $publicPath);
define('BASE_PATH', $basePath);
define('DS', DIRECTORY_SEPARATOR);

require $basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
