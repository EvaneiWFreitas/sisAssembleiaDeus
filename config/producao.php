<?php
declare(strict_types=1);

use App\Config;

return [
    'app' => [
        'env'       => 'producao',
        'url'       => (string) Config::env('PROD_APP_URL', Config::env('APP_URL', '')),
        'base_path' => (string) Config::env('PROD_APP_BASE_PATH', ''),
        'debug'     => false,
        'timezone'  => (string) Config::env('APP_TIMEZONE', 'America/Sao_Paulo'),
    ],
    'db' => [
        'host'    => (string) Config::env('PROD_DB_HOST', 'sql303.ezyro.com'),
        'port'    => (int) Config::env('PROD_DB_PORT', 3306),
        'name'    => (string) Config::env('PROD_DB_NAME', 'ezyro_43462517_gestao_igreja'),
        'user'    => (string) Config::env('PROD_DB_USER', 'ezyro_43462517'),
        'pass'    => (string) Config::env('PROD_DB_PASS', ''),
        'charset' => (string) Config::env('PROD_DB_CHARSET', 'utf8mb4'),
    ],
    'security' => [
        'session_name'     => (string) Config::env('SESSION_NAME', 'sisad_session'),
        'session_lifetime' => (int) Config::env('SESSION_LIFETIME', 7200),
        'csrf_token_name'  => (string) Config::env('CSRF_TOKEN_NAME', '_csrf_token'),
        'upload_max_mb'    => (int) Config::env('UPLOAD_MAX_MB', 5),
        'cookie_secure'    => true,
    ],
];
