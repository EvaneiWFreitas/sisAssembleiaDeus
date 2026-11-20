<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('Conexao com o banco de dados nao inicializada.');
        }

        return self::$pdo;
    }

    /** @param array<string, mixed> $parametros */
    public static function run(string $sql, array $parametros = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($parametros);

        return $stmt;
    }
}
