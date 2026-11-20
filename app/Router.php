<?php
declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;
use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

final class Router
{
    /** @var list<array{method:string,pattern:string,handler:mixed,middlewares:list<MiddlewareInterface>,regex:string}> */
    private array $routes = [];

    public function get(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->add('GET', $pattern, $handler, $middlewares);
    }

    public function post(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->add('POST', $pattern, $handler, $middlewares);
    }

    public function put(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->add('PUT', $pattern, $handler, $middlewares);
    }

    public function patch(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->add('PATCH', $pattern, $handler, $middlewares);
    }

    public function delete(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middlewares);
    }

    /** @param list<MiddlewareInterface> $middlewares */
    private function add(string $method, string $pattern, mixed $handler, array $middlewares): void
    {
        $partes = explode('/', trim($pattern, '/'));
        $regexPartes = [];

        foreach ($partes as $parte) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $parte, $match) === 1) {
                $regexPartes[] = '(?P<' . $match[1] . '>[^/]+)';
                continue;
            }
            $regexPartes[] = preg_quote($parte, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'regex' => '#^/' . implode('/', $regexPartes) . '$#',
        ];
    }

    public function dispatch(Request $request): Response
    {
        $metodoBloqueado = false;

        foreach ($this->routes as $rota) {
            $parametros = [];
            if (preg_match($rota['regex'], $request->path, $parametros) !== 1) {
                continue;
            }

            if ($rota['method'] !== $request->method) {
                $metodoBloqueado = true;
                continue;
            }

            foreach ($rota['middlewares'] as $middleware) {
                $middleware->handle($request);
            }

            foreach ($parametros as $chave => $valor) {
                if (is_int($chave)) {
                    unset($parametros[$chave]);
                }
            }

            $handler = $this->resolveHandler($rota['handler']);
            $resultado = call_user_func_array($handler, $this->argumentos($handler, $request, $parametros));

            if ($resultado instanceof Response) {
                return $resultado;
            }
            if (is_array($resultado)) {
                return Response::json($resultado);
            }

            return Response::html((string) $resultado);
        }

        if ($metodoBloqueado) {
            throw new HttpException(405, 'Metodo nao permitido para esta rota.');
        }

        throw new HttpException(404, 'A rota solicitada nao existe.');
    }

    /** @param array<string, mixed> $parametros @return list<mixed> */
    private function argumentos(callable $handler, Request $request, array $parametros): array
    {
        $argumentos = array_values($parametros);

        try {
            if ($handler instanceof Closure || is_string($handler)) {
                $reflexao = new ReflectionFunction($handler);
            } elseif (is_array($handler) && is_object($handler[0] ?? null) && is_string($handler[1] ?? null)) {
                $reflexao = new ReflectionMethod($handler[0], $handler[1]);
            } else {
                return $argumentos;
            }

            if ($reflexao->getNumberOfRequiredParameters() > count($argumentos)) {
                array_unshift($argumentos, $request);
            }
        } catch (ReflectionException) {
            return $argumentos;
        }

        return $argumentos;
    }

    private function resolveHandler(mixed $handler): callable
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$nomeClasse, $metodo] = explode('@', $handler, 2);
            $classe = 'App\\Controllers\\' . $nomeClasse;
            if (!class_exists($classe)) {
                throw new RuntimeException('Controlador nao encontrado: ' . $nomeClasse);
            }
            $controlador = new $classe();
            if (method_exists($controlador, 'setRequest')) {
                $controlador->setRequest(Request::current());
            }
            return [$controlador, $metodo];
        }

        if (!is_callable($handler)) {
            throw new RuntimeException('Handler de rota invalido.');
        }

        return $handler;
    }

    /** @return list<string> */
    public function rotas(): array
    {
        return array_map(
            static fn(array $rota): string => $rota['method'] . ' ' . $rota['pattern'],
            $this->routes
        );
    }
}
