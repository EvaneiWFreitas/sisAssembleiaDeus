<?php
declare(strict_types=1);

use App\Config;

$host    = (string) Config::get('db.host', '127.0.0.1');
$port    = (int) Config::get('db.port', 3306);
$name    = (string) Config::get('db.name', 'ezyro_43462517_gestao_igreja');
$charset = (string) Config::get('db.charset', 'utf8mb4');
$user    = (string) Config::get('db.user', 'root');
$pass    = (string) Config::get('db.pass', '');

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_STRINGIFY_FETCHES  => false,
];

try {
    return new PDO($dsn, $user, $pass, $options);
} catch (PDOException $exception) {
    throw new RuntimeException('Nao foi possivel conectar ao banco de dados.', 0, $exception);
}
