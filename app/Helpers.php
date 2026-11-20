<?php
declare(strict_types=1);

use App\AdminRepository;
use App\Auth;
use App\Csrf;
use App\Permission;
use App\Response;
use App\Session;
use App\SiteRepository;
use App\View;

if (!function_exists('e')) {
    function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $chave, mixed $padrao = null): mixed
    {
        return App\Config::get($chave, $padrao);
    }
}

if (!function_exists('url')) {
    function url(string $caminho = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $caminho = ltrim($caminho, '/');

        return $caminho === '' ? $base : $base . '/' . $caminho;
    }
}

if (!function_exists('asset')) {
    function asset(string $caminho): string
    {
        $relativo = ltrim($caminho, '/');
        $url = url('assets/' . $relativo);
        $arquivo = public_path('assets/' . $relativo);
        $modificado = is_file($arquivo) ? filemtime($arquivo) : false;

        return $modificado === false ? $url : $url . '?v=' . $modificado;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $caminho): string
    {
        return url(ltrim($caminho, '/'));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $caminho = ''): string
    {
        return BASE_PATH . ($caminho === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminho));
    }
}

if (!function_exists('public_path')) {
    function public_path(string $caminho = ''): string
    {
        return PUBLIC_PATH . ($caminho === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminho));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $caminho = ''): string
    {
        return base_path('storage' . ($caminho === '' ? '' : '/' . $caminho));
    }
}

if (!function_exists('old')) {
    function old(string $chave, string $padrao = ''): string
    {
        $valores = App\Session::get('old', []);
        return isset($valores[$chave]) ? e($valores[$chave]) : e($padrao);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('slugify')) {
    function slugify(string $texto): string
    {
        $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($convertido === false) {
            $convertido = $texto;
        }
        $convertido = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $convertido) ?? '');

        return trim($convertido, '-');
    }
}

if (!function_exists('format_money')) {
    function format_money(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $data, string $formato = 'd/m/Y'): string
    {
        if ($data === null || $data === '' || $data === '0000-00-00') {
            return '-';
        }
        $timestamp = strtotime($data);
        return $timestamp === false ? '-' : date($formato, $timestamp);
    }
}

if (!function_exists('mask_cpf')) {
    function mask_cpf(string $cpf): string
    {
        $digitos = preg_replace('/\D/', '', $cpf) ?? '';
        if (strlen($digitos) !== 11) {
            return $cpf;
        }
        return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.'
            . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $caminho, int $status = 302): Response
    {
        return Response::redirect(url($caminho), $status);
    }
}

if (!function_exists('site_view')) {
    function site_view(string $view, array $data = [], string $ativa = ''): Response
    {
        return Response::html(View::render($view, array_merge(SiteRepository::contexto($ativa), $data)));
    }
}

if (!function_exists('admin_view')) {
    function admin_view(string $view, array $data = [], string $ativa = '', ?string $modulo = null, string $acao = 'ver'): Response
    {
        if (!Auth::check()) {
            $destino = \App\Request::current()->path;
            if (str_starts_with($destino, '/admin')) {
                Session::flash('login_destino', $destino);
            }
            Session::flash('login_required', 'Entre com sua conta para acessar o painel administrativo.');
            return redirect('login');
        }

        if ($modulo !== null) {
            Permission::requirePermission($modulo, $acao);
        }

        return Response::html(View::render($view, array_merge(AdminRepository::contexto($ativa), $data), 'layouts/admin'));
    }
}
