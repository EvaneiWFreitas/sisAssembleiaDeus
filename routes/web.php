<?php
declare(strict_types=1);

use App\Auth;
use App\Database;
use App\Csrf;
use App\Exceptions\HttpException;
use App\Middleware\CsrfMiddleware;
use App\Request;
use App\Response;
use App\Router;
use App\Session;
use App\SiteRepository;
use App\Validator;
use App\View;

return static function (Router $router): void {
    $router->get('/', static function (): Response {
        return site_view('site/home', [
            'titulo' => 'Início',
            'descricao' => 'Portal institucional com cultos, eventos, notícias e informações da igreja.',
            'eventos' => SiteRepository::eventos(3),
            'noticias' => SiteRepository::noticias(3),
            'cultos' => SiteRepository::cultos(),
            'galeria' => SiteRepository::galeria(6),
            'palavra' => SiteRepository::palavra(),
        ], 'inicio');
    });

    $router->get('/sobre', static function (): Response {
        return site_view('site/sobre', [
            'titulo' => 'Sobre a igreja',
            'descricao' => 'Conheça a missão, a visão e os valores da nossa comunidade.',
        ], 'sobre');
    });

    $router->get('/cultos', static function (): Response {
        return site_view('site/cultos', [
            'titulo' => 'Cultos',
            'descricao' => 'Confira a programação de cultos e encontros da igreja.',
            'cultos' => SiteRepository::cultos(),
        ], 'cultos');
    });

    $router->get('/eventos', static function (): Response {
        return site_view('site/eventos', [
            'titulo' => 'Eventos',
            'descricao' => 'Próximos eventos e atividades da nossa comunidade.',
            'eventos' => SiteRepository::eventos(12),
        ], 'eventos');
    });

    $router->get('/noticias', static function (): Response {
        return site_view('site/noticias', [
            'titulo' => 'Notícias',
            'descricao' => 'Notícias e novidades da igreja.',
            'noticias' => SiteRepository::noticias(12),
        ], 'noticias');
    });

    $router->get('/noticias/{slug}', static function (string $slug): Response {
        $noticia = SiteRepository::noticiaPorSlug($slug);
        if ($noticia === null) {
            throw new HttpException(404, 'A notícia solicitada não existe.');
        }

        return site_view('site/noticia', [
            'titulo' => (string) ($noticia['titulo'] ?? 'Notícia'),
            'descricao' => (string) ($noticia['resumo'] ?? ''),
            'noticia' => $noticia,
        ], 'noticias');
    });

    $router->get('/galeria', static function (): Response {
        return site_view('site/galeria', [
            'titulo' => 'Galeria',
            'descricao' => 'Fotos dos momentos e atividades da igreja.',
            'galeria' => SiteRepository::galeria(24),
        ], 'galeria');
    });

    $router->get('/contato', static function (): Response {
        $sucesso = Session::takeFlash('contato_sucesso');
        $erroEnvio = Session::takeFlash('contato_erro');

        return site_view('site/contato', [
            'titulo' => 'Contato',
            'descricao' => 'Entre em contato com a nossa igreja.',
            'erros' => [],
            'valores' => [],
            'sucesso' => is_string($sucesso) ? $sucesso : '',
            'erroEnvio' => is_string($erroEnvio) ? $erroEnvio : '',
        ], 'contato');
    });

    $router->post('/contato', static function (Request $request): Response {
        $resultado = Validator::validate($_POST, [
            'nome' => 'required|min:3|max:150',
            'email' => 'required|email|max:190',
            'telefone' => 'max:30',
            'assunto' => 'max:180',
            'mensagem' => 'required|min:10|max:2000',
        ]);

        if (!$resultado['ok']) {
            return site_view('site/contato', [
                'titulo' => 'Contato',
                'descricao' => 'Entre em contato com a nossa igreja.',
                'erros' => $resultado['errors'],
                'valores' => $resultado['data'],
                'sucesso' => '',
                'erroEnvio' => '',
            ], 'contato');
        }

        try {
            SiteRepository::salvarMensagem($resultado['data'], $request->ip);
            Session::flash('contato_sucesso', 'Mensagem enviada com sucesso. Obrigado pelo contato!');
        } catch (Throwable) {
            Session::flash('contato_erro', 'Não foi possível enviar sua mensagem. Tente novamente em instantes.');
        }

        return redirect('contato');
    }, [new CsrfMiddleware()]);

    $router->get('/login', static function (Request $request): Response {
        if (Auth::check()) {
            return redirect('admin');
        }

        $erro = Session::takeFlash('login_erro');
        $aviso = Session::takeFlash('login_required');
        $email = Session::takeFlash('login_email');
        if ($request->query('sair') === '1') {
            $aviso = 'Sua sessão foi encerrada com sucesso.';
        }

        return Response::html(View::render('auth/login', [
            'titulo' => 'Acesso ao painel',
            'nomeIgreja' => SiteRepository::contexto()['nomeIgreja'],
            'logotipo' => SiteRepository::logotipo(),
            'erro' => is_string($erro) ? $erro : '',
            'aviso' => is_string($aviso) ? $aviso : '',
            'email' => is_string($email) ? $email : '',
        ], 'layouts/auth'));
    });

    $router->post('/login', static function (Request $request): Response {
        $resultado = Validator::validate($_POST, [
            'email' => 'required|email|max:190',
            'senha' => 'required|min:8',
        ]);

        if (!$resultado['ok']) {
            $primeiroErro = array_values($resultado['errors'])[0] ?? 'Confira os dados informados.';
            Session::flash('login_erro', (string) $primeiroErro);
            Session::flash('login_email', (string) ($resultado['data']['email'] ?? ''));
            return redirect('login');
        }

        $email = (string) $resultado['data']['email'];
        $senha = (string) $resultado['data']['senha'];

        if (!Auth::attempt($email, $senha)) {
            Session::flash('login_erro', 'E-mail ou senha inválidos. Verifique também se o usuário está ativo e desbloqueado.');
            Session::flash('login_email', $email);
            return redirect('login');
        }

        $destino = Session::takeFlash('login_destino');
        $destino = is_string($destino) && str_starts_with($destino, '/admin') ? $destino : '/admin';

        return redirect(ltrim($destino, '/'));
    }, [new CsrfMiddleware()]);

    $router->get('/status', static function (): Response {
        $banco = 'conectado';
        try {
            Database::connection()->query('SELECT 1');
        } catch (Throwable $exception) {
            $banco = 'falhou';
        }

        return Response::json([
            'sistema' => 'sisAssembleiaDeus',
            'etapa' => 26,
            'ambiente' => config('app.env'),
            'php' => PHP_VERSION,
            'banco' => $banco,
            'sessao' => Session::id(),
            'csrf' => Csrf::token(),
        ]);
    });

    $router->post('/status', static function (Request $request): Response {
        return Response::json([
            'ok' => true,
            'recebido' => (string) $request->post('mensagem', ''),
        ]);
    }, [new CsrfMiddleware()]);
};
