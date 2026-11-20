<?php
declare(strict_types=1);

use App\Config;
use App\Csrf;
use App\Database;
use App\Exceptions\HttpException;
use App\Middleware\CsrfMiddleware;
use App\Request;
use App\Response;
use App\Router;
use App\Session;

require BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Autoload.php';
require BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helpers.php';

try {
    Config::loadEnv(BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . '.env');

    $ambiente = (string) Config::env('APP_ENV', 'local');
    $ambiente = in_array($ambiente, ['local', 'producao'], true) ? $ambiente : 'local';

    Config::loadSettings(BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $ambiente . '.php');

    date_default_timezone_set((string) config('app.timezone', 'America/Sao_Paulo'));
    error_reporting(E_ALL);
    ini_set('display_errors', (bool) config('app.debug', false) ? '1' : '0');
    ini_set('log_errors', '1');
    ini_set('error_log', storage_path('logs' . DIRECTORY_SEPARATOR . 'php-error.log'));

    Database::set(require BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php');

    Session::start();
    Session::sincronizar();
    Csrf::ensure();

    $request = Request::createFromGlobals();
    Request::setCurrent($request);

    $router = new Router();
    foreach (['web', 'admin'] as $arquivo) {
        $carregador = require BASE_PATH . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $arquivo . '.php';
        if (is_callable($carregador)) {
            $carregador($router);
        }
    }

    $response = $router->dispatch($request);
} catch (HttpException $exception) {
    $response = Response::error($exception->status(), $exception->getMessage());
} catch (Throwable $exception) {
    error_log('Erro na aplicação: ' . $exception->getMessage());
    $mensagem = (bool) config('app.debug', false)
        ? $exception->getMessage()
        : 'Erro interno do servidor. Tente novamente mais tarde.';
    $response = Response::error(500, $mensagem);
}

$response->send();
