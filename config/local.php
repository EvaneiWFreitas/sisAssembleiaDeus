<?php
declare(strict_types=1);

use App\Config;

return [
    'app' => [
        'env'       => 'local',
        'url'       => (string) Config::env('APP_URL', 'http://localhost/sisAssembleiaDeus'),
        'base_path' => (string) Config::env('APP_BASE_PATH', '/sisAssembleiaDeus'),
        'debug'     => filter_var(Config::env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL),
        'timezone'  => (string) Config::env('APP_TIMEZONE', 'America/Sao_Paulo'),
    ],
    'db' => [
        'host'    => (string) Config::env('DB_HOST', '127.0.0.1'),
        'port'    => (int) Config::env('DB_PORT', 3306),
        'name'    => (string) Config::env('DB_NAME', 'ezyro_43462517_gestao_igreja'),
        'user'    => (string) Config::env('DB_USER', 'root'),
        'pass'    => (string) Config::env('DB_PASS', ''),
        'charset' => (string) Config::env('DB_CHARSET', 'utf8mb4'),
    ],
    'security' => [
        'session_name'     => (string) Config::env('SESSION_NAME', 'sisad_session'),
        'session_lifetime' => (int) Config::env('SESSION_LIFETIME', 7200),
        'csrf_token_name'  => (string) Config::env('CSRF_TOKEN_NAME', '_csrf_token'),
        'upload_max_mb'    => (int) Config::env('UPLOAD_MAX_MB', 5),
        'cookie_secure'    => filter_var(Config::env('COOKIE_SECURE', 'false'), FILTER_VALIDATE_BOOL),
    ],
];
