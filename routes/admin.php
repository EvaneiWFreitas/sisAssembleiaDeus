<?php
declare(strict_types=1);

use App\AclRepository;
use App\AdminRepository;
use App\Audit;
use App\AuditoriaRepository;
use App\Auth;
use App\BackupRepository;
use App\CarteirinhaRepository;
use App\ConfiguracaoRepository;
use App\ContatoRepository;
use App\DocumentoRepository;
use App\EventoRepository;
use App\Exceptions\HttpException;
use App\FinanceiroRepository;
use App\GaleriaRepository;
use App\LgpdRepository;
use App\Middleware\CsrfMiddleware;
use App\Middleware\PermissionMiddleware;
use App\MemberRepository;
use App\NoticiaRepository;
use App\ObreiroRepository;
use App\PalavraPastoralRepository;
use App\ProgramacaoRepository;
use App\RelatorioRepository;
use App\ArquivoMortoRepository;
use App\SacramentoRepository;
use App\Request;
use App\Response;
use App\Router;
use App\Session;
use App\StructureRepository;
use App\Upload;
use App\View;
use App\UserRepository;
use App\Validator;

return static function (Router $router): void {
    $estadoFormulario = static function (): array {
        $erros = Session::takeFlash('form_erros');
        $valores = Session::takeFlash('form_valores');

        return [
            'erros' => is_array($erros) ? $erros : [],
            'valores' => is_array($valores) ? $valores : [],
        ];
    };

    $router->get('/admin', static function (): Response {
        return admin_view('admin/dashboard', [
            'titulo' => 'Visão geral',
            'subtitulo' => 'Acompanhe os principais indicadores do sistema.',
        ], 'dashboard');
    });

    $router->get('/admin/perfil', static function (): Response {
        return admin_view('admin/perfil', [
            'titulo' => 'Meu perfil',
            'subtitulo' => 'Dados da conta conectada ao sistema.',
        ], 'perfil');
    });

    $router->get('/admin/modulos/usuarios', static function (Request $request): Response {
        $busca = trim((string) $request->query('q', ''));

        return admin_view('admin/usuarios/index', [
            'titulo' => 'Usuários',
            'subtitulo' => 'Contas de acesso, perfis e situação dos usuários do sistema.',
            'usuarios' => UserRepository::listar($busca),
            'perfis' => UserRepository::perfis(),
            'estatisticas' => UserRepository::estatisticas(),
            'busca' => $busca,
        ], 'usuarios', 'usuarios', 'ver');
    });

    $router->get('/admin/modulos/usuarios/novo', static function () use ($estadoFormulario): Response {
        $estado = $estadoFormulario();

        return admin_view('admin/usuarios/form', [
            'titulo' => 'Novo usuário',
            'subtitulo' => 'Cadastre uma nova conta de acesso ao sistema.',
            'usuarioFormulario' => null,
            'perfis' => UserRepository::perfis(),
            'valores' => array_merge(['nome' => '', 'email' => '', 'perfil_id' => '', 'ativo' => 1, 'observacoes' => '', 'senha' => ''], $estado['valores']),
            'erros' => $estado['erros'],
            'historico' => [],
        ], 'usuarios', 'usuarios', 'criar');
    });

    $router->get('/admin/modulos/usuarios/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $usuario = UserRepository::buscar((int) $id);
        if ($usuario === null) {
            throw new HttpException(404, 'Usuário não encontrado.');
        }

        $estado = $estadoFormulario();
        $valores = [
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
            'perfil_id' => (int) $usuario['perfil_id'],
            'ativo' => (int) $usuario['ativo'],
            'observacoes' => (string) ($usuario['observacoes'] ?? ''),
            'senha' => '',
        ];

        return admin_view('admin/usuarios/form', [
            'titulo' => 'Editar usuário',
            'subtitulo' => 'Atualize os dados e as permissões da conta selecionada.',
            'usuarioFormulario' => $usuario,
            'perfis' => UserRepository::perfis(),
            'valores' => array_merge($valores, $estado['valores']),
            'erros' => $estado['erros'],
            'historico' => UserRepository::historico((int) $usuario['id']),
        ], 'usuarios', 'usuarios', 'editar');
    });

    $router->post('/admin/modulos/usuarios', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), [
            'nome' => 'required|max:150',
            'email' => 'required|email|max:190|unique:usuarios,email',
            'perfil_id' => 'required|integer',
            'senha' => 'required|min:8',
            'ativo' => 'required|in:0,1',
            'observacoes' => 'max:500',
        ]);

        if (!$validacao['ok'] || AclRepository::perfil((int) ($validacao['data']['perfil_id'] ?? 0)) === null) {
            if (!$validacao['ok'] && !isset($validacao['errors']['perfil_id'])) {
                $validacao['errors']['perfil_id'] = 'Selecione um perfil válido.';
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $validacao['data']);
            Session::flash('erro', 'Revise os campos destacados.');
            return redirect('admin/modulos/usuarios/novo');
        }

        $dados = $validacao['data'];
        $novoId = UserRepository::criar($dados);
        Audit::registrar('criar', 'usuarios', 'usuarios', $novoId, null, [
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'perfil_id' => $dados['perfil_id'],
            'ativo' => $dados['ativo'],
        ]);
        Session::flash('sucesso', 'Usuário criado com sucesso.');
        return redirect('admin/modulos/usuarios/' . $novoId . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('usuarios', 'criar')]);

    $router->post('/admin/modulos/usuarios/{id}', static function (Request $request, string $id): Response {
        $usuario = UserRepository::buscar((int) $id);
        if ($usuario === null) {
            throw new HttpException(404, 'Usuário não encontrado.');
        }

        $validacao = Validator::validate($request->post(), [
            'nome' => 'required|max:150',
            'email' => 'required|email|max:190|unique:usuarios,email,id,' . (int) $id,
            'perfil_id' => 'required|integer',
            'senha' => 'min:8',
            'ativo' => 'required|in:0,1',
            'observacoes' => 'max:500',
        ]);
        $perfil = AclRepository::perfil((int) ($validacao['data']['perfil_id'] ?? 0));

        if (!$validacao['ok'] || $perfil === null) {
            if (!$validacao['ok'] && !isset($validacao['errors']['perfil_id'])) {
                $validacao['errors']['perfil_id'] = 'Selecione um perfil válido.';
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $validacao['data']);
            Session::flash('erro', 'Revise os campos destacados.');
            return redirect('admin/modulos/usuarios/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $novoStatus = (int) $dados['ativo'];

        if ((int) $usuario['id'] === Auth::id() && $novoStatus !== 1) {
            Session::flash('erro', 'Você não pode desativar a sua própria conta.');
            return redirect('admin/modulos/usuarios/' . (int) $id . '/editar');
        }

        $ehSuperAdmin = ($usuario['perfil_slug'] ?? '') === 'superadministrador';
        $ultimoSuperAdmin = $ehSuperAdmin
            && UserRepository::contarSuperadministradoresAtivos((int) $id) === 0
            && ((int) $dados['perfil_id'] !== 1 || $novoStatus !== 1);

        if ($ultimoSuperAdmin) {
            Session::flash('erro', 'Não é possível remover o acesso do último superadministrador ativo.');
            return redirect('admin/modulos/usuarios/' . (int) $id . '/editar');
        }

        $anterior = [
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
            'perfil_id' => (int) $usuario['perfil_id'],
            'ativo' => (int) $usuario['ativo'],
        ];
        UserRepository::atualizar((int) $id, $dados);
        Audit::registrar('editar', 'usuarios', 'usuarios', (int) $id, $anterior, [
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'perfil_id' => $dados['perfil_id'],
            'ativo' => $dados['ativo'],
            'senha_alterada' => ($dados['senha'] ?? '') !== '' ? 'sim' : 'nao',
        ]);
        Session::flash('sucesso', 'Usuário atualizado com sucesso.');
        return redirect('admin/modulos/usuarios/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('usuarios', 'editar')]);

    $router->post('/admin/modulos/usuarios/{id}/status', static function (string $id): Response {
        $usuario = UserRepository::buscar((int) $id);
        if ($usuario === null) {
            throw new HttpException(404, 'Usuário não encontrado.');
        }

        if ((int) $usuario['id'] === Auth::id()) {
            Session::flash('erro', 'Você não pode alterar o status da sua própria conta.');
            return redirect('admin/modulos/usuarios');
        }

        $novoStatus = (int) $usuario['ativo'] === 1 ? 0 : 1;
        if ((int) $usuario['ativo'] === 1
            && ($usuario['perfil_slug'] ?? '') === 'superadministrador'
            && UserRepository::contarSuperadministradoresAtivos((int) $id) === 0) {
            Session::flash('erro', 'Não é possível desativar o último superadministrador ativo.');
            return redirect('admin/modulos/usuarios');
        }

        UserRepository::definirStatus((int) $id, $novoStatus);
        Audit::registrar($novoStatus === 1 ? 'ativar' : 'desativar', 'usuarios', 'usuarios', (int) $id, [
            'ativo' => (int) $usuario['ativo'],
        ], ['ativo' => $novoStatus]);
        Session::flash('sucesso', $novoStatus === 1 ? 'Usuário ativado.' : 'Usuário desativado.');
        return redirect('admin/modulos/usuarios');
    }, [new CsrfMiddleware(), new PermissionMiddleware('usuarios', 'excluir')]);

    $router->get('/admin/modulos/permissoes', static function (): Response {
        return admin_view('admin/permissoes/index', [
            'titulo' => 'Perfis e permissões',
            'subtitulo' => 'Perfis de acesso e permissões disponíveis por módulo.',
            'perfis' => AclRepository::perfis(),
            'catalogo' => AclRepository::catalogo(),
            'totalPermissoes' => count(AclRepository::chavesValidas()),
        ], 'permissoes', 'permissoes', 'ver');
    });

    $router->get('/admin/modulos/permissoes/{id}', static function (string $id): Response {
        $perfil = AclRepository::perfil((int) $id);
        if ($perfil === null) {
            throw new HttpException(404, 'Perfil não encontrado.');
        }

        return admin_view('admin/permissoes/form', [
            'titulo' => 'Permissões do perfil',
            'subtitulo' => (string) ($perfil['descricao'] ?? $perfil['nome']),
            'perfil' => $perfil,
            'catalogo' => AclRepository::catalogo(),
            'selecionadas' => AclRepository::permissoesDoPerfil((int) $id),
            'bloqueado' => ($perfil['slug'] ?? '') === 'superadministrador',
        ], 'permissoes', 'permissoes', 'editar');
    });

    $router->post('/admin/modulos/permissoes/{id}', static function (string $id): Response {
        $perfil = AclRepository::perfil((int) $id);
        if ($perfil === null) {
            throw new HttpException(404, 'Perfil não encontrado.');
        }

        $marcadas = Request::current()->post('permissoes', []);
        if (!is_array($marcadas)) {
            $marcadas = [];
        }

        $anteriores = AclRepository::permissoesDoPerfil((int) $id);
        AclRepository::salvarPermissoes((int) $id, array_map('intval', $marcadas));
        $novas = AclRepository::permissoesDoPerfil((int) $id);

        Audit::registrar('editar', 'permissoes', 'perfis', (int) $id, [
            'total' => count($anteriores),
        ], [
            'total' => count($novas),
            'adicionadas' => array_values(array_diff($novas, $anteriores)),
            'removidas' => array_values(array_diff($anteriores, $novas)),
        ]);
        Session::flash('sucesso', 'Permissões do perfil atualizadas.');
        return redirect('admin/modulos/permissoes/' . (int) $id);
    }, [new CsrfMiddleware(), new PermissionMiddleware('permissoes', 'editar')]);

    $estruturaTipo = static function (string $tipo): array {
        $config = StructureRepository::tipo($tipo);
        if ($config === null) {
            throw new HttpException(404, 'O tipo de estrutura solicitado não existe.');
        }

        return $config;
    };

    $estruturaValores = static function (string $tipo): array {
        $valores = [];
        foreach (array_keys(StructureRepository::regras($tipo, null)) as $campo) {
            $valores[$campo] = '';
        }
        $valores['situacao'] = 'ativa';

        return $valores;
    };

    $router->get('/admin/modulos/estrutura', static function (): Response {
        return admin_view('admin/estrutura/index', [
            'titulo' => 'Estrutura ministerial',
            'subtitulo' => 'Matriz, congregações e pontos de pregação.',
            'contadores' => StructureRepository::contadores(),
            'matrizes' => StructureRepository::listar('matriz'),
            'congregacoes' => StructureRepository::listar('congregacao'),
            'pontos' => StructureRepository::listar('ponto'),
        ], 'estrutura', 'estrutura', 'ver');
    }, [new PermissionMiddleware('estrutura', 'ver')]);

    $router->get('/admin/modulos/estrutura/{tipo}/novo', static function (string $tipo) use ($estruturaTipo, $estruturaValores, $estadoFormulario): Response {
        $config = $estruturaTipo($tipo);
        $estado = $estadoFormulario();

        return admin_view('admin/estrutura/form', [
            'titulo' => 'Cadastrar ' . mb_strtolower($config['nome']),
            'subtitulo' => 'Preencha os dados do novo registro da estrutura ministerial.',
            'tipo' => $tipo,
            'config' => $config,
            'item' => null,
            'valores' => array_merge($estruturaValores($tipo), $estado['valores']),
            'erros' => $estado['erros'],
            'obreiros' => StructureRepository::obreiros(),
            'matrizes' => StructureRepository::matrizes(),
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'estrutura', 'estrutura', 'criar');
    }, [new PermissionMiddleware('estrutura', 'criar')]);

    $router->get('/admin/modulos/estrutura/{tipo}/{id}/editar', static function (string $tipo, string $id) use ($estruturaTipo, $estruturaValores, $estadoFormulario): Response {
        $config = $estruturaTipo($tipo);
        $item = StructureRepository::buscar($tipo, (int) $id);
        if ($item === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();

        return admin_view('admin/estrutura/form', [
            'titulo' => 'Editar ' . mb_strtolower($config['nome']),
            'subtitulo' => 'Atualize os dados do registro da estrutura ministerial.',
            'tipo' => $tipo,
            'config' => $config,
            'item' => $item,
            'valores' => array_merge($estruturaValores($tipo), $item, $estado['valores']),
            'erros' => $estado['erros'],
            'obreiros' => StructureRepository::obreiros(),
            'matrizes' => StructureRepository::matrizes($tipo === 'congregacao' ? (int) $item['igreja_matriz_id'] : null),
            'congregacoes' => StructureRepository::congregacoes($tipo === 'ponto' ? (int) $item['congregacao_id'] : null),
        ], 'estrutura', 'estrutura', 'editar');
    }, [new PermissionMiddleware('estrutura', 'editar')]);

    $router->post('/admin/modulos/estrutura/{tipo}', static function (Request $request, string $tipo) use ($estruturaTipo): Response {
        $config = $estruturaTipo($tipo);
        $resultado = Validator::validate($request->post(), StructureRepository::regras($tipo, null));

        if (!$resultado['ok']) {
            Session::flash('form_erros', $resultado['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/estrutura/' . $config['rota'] . '/novo');
        }

        $id = StructureRepository::salvar($tipo, null, $resultado['data']);
        Audit::registrar('criar', 'estrutura', $config['tabela'], $id, null, $resultado['data']);
        Session::flash('sucesso', 'Registro criado com sucesso.');
        return redirect('admin/modulos/estrutura/' . $config['rota'] . '/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('estrutura', 'criar')]);

    $router->post('/admin/modulos/estrutura/{tipo}/{id}', static function (Request $request, string $tipo, string $id) use ($estruturaTipo): Response {
        $config = $estruturaTipo($tipo);
        $item = StructureRepository::buscar($tipo, (int) $id);
        if ($item === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $resultado = Validator::validate($request->post(), StructureRepository::regras($tipo, (int) $id));
        if (!$resultado['ok']) {
            Session::flash('form_erros', $resultado['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/estrutura/' . $config['rota'] . '/' . (int) $id . '/editar');
        }

        StructureRepository::salvar($tipo, (int) $id, $resultado['data']);
        Audit::registrar('editar', 'estrutura', $config['tabela'], (int) $id, $item, $resultado['data']);
        Session::flash('sucesso', 'Registro atualizado com sucesso.');
        return redirect('admin/modulos/estrutura/' . $config['rota'] . '/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('estrutura', 'editar')]);

    $router->post('/admin/modulos/estrutura/{tipo}/{id}/situacao', static function (string $tipo, string $id) use ($estruturaTipo): Response {
        $config = $estruturaTipo($tipo);
        $item = StructureRepository::buscar($tipo, (int) $id);
        if ($item === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $nova = StructureRepository::alternarSituacao($tipo, (int) $id);
        Audit::registrar('editar', 'estrutura', $config['tabela'], (int) $id, ['situacao' => $item['situacao']], ['situacao' => $nova]);
        Session::flash('sucesso', 'Situação alterada para ' . $nova . '.');
        return redirect('admin/modulos/estrutura');
    }, [new CsrfMiddleware(), new PermissionMiddleware('estrutura', 'excluir')]);

    $membroFoto = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('foto');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'membros', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $membroPadrao = static function (): array {
        $valores = [];
        foreach (array_keys(MemberRepository::regras(null)) as $campo) {
            $valores[$campo] = '';
        }
        $valores['codigo'] = MemberRepository::proximoCodigo();
        $valores['sexo'] = 'nao_informado';
        $valores['estado_civil'] = 'nao_informado';
        $valores['situacao'] = 'ativo';

        return $valores;
    };

    $router->get('/admin/modulos/membros', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'situacao' => (string) $request->query('situacao', ''),
            'congregacao_id' => (int) $request->query('congregacao_id', 0),
            'excluidos' => (string) $request->query('excluidos', '') === '1',
        ];

        $consulta = $filtros;
        $consulta['somente_excluidos'] = $consulta['excluidos'];
        unset($consulta['excluidos']);

        return admin_view('admin/membros/index', [
            'titulo' => 'Membros',
            'subtitulo' => 'Cadastro, fichas e situação dos membros.',
            'membros' => MemberRepository::listar($consulta),
            'estatisticas' => MemberRepository::estatisticas(),
            'filtros' => $filtros,
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'membros', 'membros', 'ver');
    }, [new PermissionMiddleware('membros', 'ver')]);

    $router->get('/admin/modulos/membros/novo', static function () use ($membroPadrao, $estadoFormulario): Response {
        $estado = $estadoFormulario();

        return admin_view('admin/membros/form', [
            'titulo' => 'Cadastrar membro',
            'subtitulo' => 'Informe os dados pessoais, contato e vínculo com a igreja.',
            'membro' => null,
            'valores' => array_merge($membroPadrao(), $estado['valores']),
            'erros' => $estado['erros'],
            'matrizes' => StructureRepository::matrizes(),
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'membros', 'membros', 'criar');
    }, [new PermissionMiddleware('membros', 'criar')]);

    $router->get('/admin/modulos/membros/{id}/editar', static function (string $id) use ($membroPadrao, $estadoFormulario): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $origemId = (int) ($estado['valores']['igreja_origem_id'] ?? $membro['igreja_origem_id'] ?? 0);
        $congregacaoId = (int) ($estado['valores']['congregacao_id'] ?? $membro['congregacao_id'] ?? 0);

        return admin_view('admin/membros/form', [
            'titulo' => 'Editar membro',
            'subtitulo' => 'Atualize os dados cadastrais do membro.',
            'membro' => $membro,
            'valores' => array_merge($membroPadrao(), $membro, $estado['valores']),
            'erros' => $estado['erros'],
            'matrizes' => StructureRepository::matrizes($origemId > 0 ? $origemId : null),
            'congregacoes' => StructureRepository::congregacoes($congregacaoId > 0 ? $congregacaoId : null),
        ], 'membros', 'membros', 'editar');
    }, [new PermissionMiddleware('membros', 'editar')]);

    $router->get('/admin/modulos/membros/{id}/historico', static function (string $id): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        return admin_view('admin/membros/historico', [
            'titulo' => 'Histórico do membro',
            'subtitulo' => 'Alterações registradas para ' . (string) $membro['nome'] . '.',
            'membro' => $membro,
            'historico' => MemberRepository::historico((int) $id),
        ], 'membros', 'membros', 'historico');
    }, [new PermissionMiddleware('membros', 'historico')]);

    $router->get('/admin/modulos/membros/{id}/ficha', static function (string $id): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        return Response::html(View::render('admin/membros/ficha', [
            'titulo' => 'Ficha do membro',
            'membro' => $membro,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('membros', 'imprimir')]);

    $router->post('/admin/modulos/membros', static function (Request $request) use ($membroFoto): Response {
        $validacao = Validator::validate($request->post(), MemberRepository::regras(null));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/membros/novo');
        }

        $dados = $validacao['data'];
        $foto = $membroFoto($request, 'membro-novo');
        if ($foto['erro'] !== null) {
            Session::flash('form_erros', ['foto' => $foto['erro']]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/membros/novo');
        }
        if ($foto['caminho'] !== null) {
            $dados['foto'] = $foto['caminho'];
        }

        $id = MemberRepository::salvar(null, $dados);
        Audit::registrar('criar', 'membros', 'membros', $id, null, $dados);
        Session::flash('sucesso', 'Membro cadastrado com sucesso.');
        return redirect('admin/modulos/membros/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('membros', 'criar')]);

    $router->post('/admin/modulos/membros/{id}', static function (Request $request, string $id) use ($membroFoto): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), MemberRepository::regras((int) $id));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/membros/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $foto = $membroFoto($request, 'membro-' . (int) $id);
        if ($foto['erro'] !== null) {
            Session::flash('form_erros', ['foto' => $foto['erro']]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/membros/' . (int) $id . '/editar');
        }

        $anterior = $membro['foto'] ?? null;
        if ($foto['caminho'] !== null) {
            $dados['foto'] = $foto['caminho'];
            if ($anterior !== null && $anterior !== '') {
                Upload::excluir((string) $anterior);
            }
        } elseif ((string) $request->post('remover_foto', '') === '1') {
            $dados['foto'] = '';
            if ($anterior !== null && $anterior !== '') {
                Upload::excluir((string) $anterior);
            }
        }

        MemberRepository::salvar((int) $id, $dados);
        Audit::registrar('editar', 'membros', 'membros', (int) $id, $membro, $dados);
        Session::flash('sucesso', 'Membro atualizado com sucesso.');
        return redirect('admin/modulos/membros/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('membros', 'editar')]);

    $router->post('/admin/modulos/membros/{id}/excluir', static function (string $id): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        MemberRepository::excluir((int) $id);
        Audit::registrar('excluir', 'membros', 'membros', (int) $id, ['deleted_at' => $membro['deleted_at']], ['deleted_at' => date('Y-m-d H:i:s')]);
        Session::flash('sucesso', 'Membro movido para os excluídos.');
        return redirect('admin/modulos/membros');
    }, [new CsrfMiddleware(), new PermissionMiddleware('membros', 'excluir')]);

    $router->post('/admin/modulos/membros/{id}/restaurar', static function (string $id): Response {
        $membro = MemberRepository::buscar((int) $id, true);
        if ($membro === null) {
            throw new HttpException(404, 'O membro solicitado não foi encontrado.');
        }

        MemberRepository::restaurar((int) $id);
        Audit::registrar('editar', 'membros', 'membros', (int) $id, ['deleted_at' => $membro['deleted_at']], ['deleted_at' => null]);
        Session::flash('sucesso', 'Membro restaurado com sucesso.');
        return redirect('admin/modulos/membros');
    }, [new CsrfMiddleware(), new PermissionMiddleware('membros', 'excluir')]);

    $obreiroFoto = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('foto');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'obreiros', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $obreiroPadrao = static function (): array {
        $valores = [];
        foreach (array_keys(ObreiroRepository::regras(null)) as $campo) {
            $valores[$campo] = '';
        }
        $valores['codigo'] = ObreiroRepository::proximoCodigo();
        $valores['situacao'] = 'ativo';

        return $valores;
    };

    $router->get('/admin/modulos/obreiros', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'situacao' => (string) $request->query('situacao', ''),
            'cargo_id' => (int) $request->query('cargo_id', 0),
            'congregacao_id' => (int) $request->query('congregacao_id', 0),
            'excluidos' => (string) $request->query('excluidos', '') === '1',
        ];

        $consulta = $filtros;
        $consulta['somente_excluidos'] = $consulta['excluidos'];
        unset($consulta['excluidos']);

        return admin_view('admin/obreiros/index', [
            'titulo' => 'Obreiros',
            'subtitulo' => 'Cadastro ministerial, cargos e histórico dos obreiros.',
            'obreiros' => ObreiroRepository::listar($consulta),
            'estatisticas' => ObreiroRepository::estatisticas(),
            'filtros' => $filtros,
            'cargos' => ObreiroRepository::cargos(),
            'congregacoes' => StructureRepository::congregacoes(),
            'arquivadosIds' => ArquivoMortoRepository::arquivadosIds(),
        ], 'obreiros', 'obreiros', 'ver');
    }, [new PermissionMiddleware('obreiros', 'ver')]);

    $router->get('/admin/modulos/obreiros/novo', static function () use ($obreiroPadrao, $estadoFormulario): Response {
        $estado = $estadoFormulario();

        return admin_view('admin/obreiros/form', [
            'titulo' => 'Cadastrar obreiro',
            'subtitulo' => 'Informe os dados pessoais e o vínculo ministerial.',
            'obreiro' => null,
            'valores' => array_merge($obreiroPadrao(), $estado['valores']),
            'erros' => $estado['erros'],
            'matrizes' => StructureRepository::matrizes(),
            'congregacoes' => StructureRepository::congregacoes(),
            'cargos' => ObreiroRepository::cargos(),
            'membros' => ObreiroRepository::membros(),
        ], 'obreiros', 'obreiros', 'criar');
    }, [new PermissionMiddleware('obreiros', 'criar')]);

    $router->get('/admin/modulos/obreiros/{id}/editar', static function (string $id) use ($obreiroPadrao, $estadoFormulario): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $origemId = (int) ($estado['valores']['igreja_origem_id'] ?? $obreiro['igreja_origem_id'] ?? 0);
        $congregacaoId = (int) ($estado['valores']['congregacao_id'] ?? $obreiro['congregacao_id'] ?? 0);
        $cargoId = (int) ($estado['valores']['cargo_id'] ?? $obreiro['cargo_id'] ?? 0);
        $membroId = (int) ($estado['valores']['membro_id'] ?? $obreiro['membro_id'] ?? 0);

        return admin_view('admin/obreiros/form', [
            'titulo' => 'Editar obreiro',
            'subtitulo' => 'Atualize os dados cadastrais e ministeriais.',
            'obreiro' => $obreiro,
            'valores' => array_merge($obreiroPadrao(), $obreiro, $estado['valores']),
            'erros' => $estado['erros'],
            'matrizes' => StructureRepository::matrizes($origemId > 0 ? $origemId : null),
            'congregacoes' => StructureRepository::congregacoes($congregacaoId > 0 ? $congregacaoId : null),
            'cargos' => ObreiroRepository::cargos($cargoId > 0 ? $cargoId : null),
            'membros' => ObreiroRepository::membros($membroId > 0 ? $membroId : null),
        ], 'obreiros', 'obreiros', 'editar');
    }, [new PermissionMiddleware('obreiros', 'editar')]);

    $router->get('/admin/modulos/obreiros/{id}/transferir', static function (string $id) use ($estadoFormulario): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $valores = array_merge([
            'congregacao_id' => (string) $obreiro['congregacao_id'],
            'descricao' => '',
        ], $estado['valores']);

        return admin_view('admin/obreiros/transferir', [
            'titulo' => 'Transferir obreiro',
            'subtitulo' => 'Registre a mudança de congregação de ' . (string) $obreiro['nome'] . '.',
            'obreiro' => $obreiro,
            'valores' => $valores,
            'erros' => $estado['erros'],
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'obreiros', 'obreiros', 'transferir');
    }, [new PermissionMiddleware('obreiros', 'transferir')]);

    $router->get('/admin/modulos/obreiros/{id}/historico', static function (string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        return admin_view('admin/obreiros/historico', [
            'titulo' => 'Histórico ministerial',
            'subtitulo' => 'Cargos, transferências e situações de ' . (string) $obreiro['nome'] . '.',
            'obreiro' => $obreiro,
            'historico' => ObreiroRepository::historico((int) $id),
        ], 'obreiros', 'obreiros', 'historico');
    }, [new PermissionMiddleware('obreiros', 'historico')]);

    $router->get('/admin/modulos/obreiros/{id}/ficha', static function (string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        return Response::html(View::render('admin/obreiros/ficha', [
            'titulo' => 'Ficha do obreiro',
            'obreiro' => $obreiro,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('obreiros', 'imprimir')]);

    $router->post('/admin/modulos/obreiros', static function (Request $request) use ($obreiroFoto): Response {
        $validacao = Validator::validate($request->post(), ObreiroRepository::regras(null));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/obreiros/novo');
        }

        $dados = $validacao['data'];
        $foto = $obreiroFoto($request, 'obreiro-novo');
        if ($foto['erro'] !== null) {
            Session::flash('form_erros', ['foto' => $foto['erro']]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/obreiros/novo');
        }
        if ($foto['caminho'] !== null) {
            $dados['foto'] = $foto['caminho'];
        }

        $id = ObreiroRepository::salvar(null, $dados);
        ObreiroRepository::registrarHistorico([
            'obreiro_id' => $id,
            'tipo' => 'situacao',
            'situacao_nova' => $dados['situacao'] ?? 'ativo',
            'descricao' => 'Cadastro criado.',
        ]);
        Audit::registrar('criar', 'obreiros', 'obreiros', $id, null, $dados);
        Session::flash('sucesso', 'Obreiro cadastrado com sucesso.');
        return redirect('admin/modulos/obreiros/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('obreiros', 'criar')]);

    $router->post('/admin/modulos/obreiros/{id}', static function (Request $request, string $id) use ($obreiroFoto): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), ObreiroRepository::regras((int) $id));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/obreiros/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $foto = $obreiroFoto($request, 'obreiro-' . (int) $id);
        if ($foto['erro'] !== null) {
            Session::flash('form_erros', ['foto' => $foto['erro']]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/obreiros/' . (int) $id . '/editar');
        }

        $anterior = $obreiro['foto'] ?? null;
        if ($foto['caminho'] !== null) {
            $dados['foto'] = $foto['caminho'];
            if ($anterior !== null && $anterior !== '') {
                Upload::excluir((string) $anterior);
            }
        } elseif ((string) $request->post('remover_foto', '') === '1') {
            $dados['foto'] = '';
            if ($anterior !== null && $anterior !== '') {
                Upload::excluir((string) $anterior);
            }
        }

        ObreiroRepository::salvar((int) $id, $dados);
        ObreiroRepository::registrarAlteracoes((int) $id, $obreiro, $dados);
        Audit::registrar('editar', 'obreiros', 'obreiros', (int) $id, $obreiro, $dados);
        Session::flash('sucesso', 'Obreiro atualizado com sucesso.');
        return redirect('admin/modulos/obreiros/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('obreiros', 'editar')]);

    $router->post('/admin/modulos/obreiros/{id}/transferir', static function (Request $request, string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), [
            'congregacao_id' => 'required|integer',
            'descricao' => 'max:500',
        ]);

        $nova = (int) ($validacao['data']['congregacao_id'] ?? 0);
        if ($validacao['ok'] && $nova === (int) $obreiro['congregacao_id']) {
            $validacao['ok'] = false;
            $validacao['errors']['congregacao_id'] = 'Selecione uma congregação diferente da atual.';
        }

        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/obreiros/' . (int) $id . '/transferir');
        }

        ObreiroRepository::transferir((int) $id, $nova, $validacao['data']['descricao'] ?? null);
        Audit::registrar('editar', 'obreiros', 'obreiros', (int) $id, [
            'congregacao_id' => $obreiro['congregacao_id'],
        ], [
            'congregacao_id' => $nova,
            'descricao' => $validacao['data']['descricao'] ?? null,
        ]);
        Session::flash('sucesso', 'Transferência registrada com sucesso.');
        return redirect('admin/modulos/obreiros/' . (int) $id . '/historico');
    }, [new CsrfMiddleware(), new PermissionMiddleware('obreiros', 'transferir')]);

    $router->post('/admin/modulos/obreiros/{id}/excluir', static function (string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        ObreiroRepository::excluir((int) $id);
        Audit::registrar('excluir', 'obreiros', 'obreiros', (int) $id, ['deleted_at' => $obreiro['deleted_at']], ['deleted_at' => date('Y-m-d H:i:s')]);
        Session::flash('sucesso', 'Obreiro movido para os excluídos.');
        return redirect('admin/modulos/obreiros');
    }, [new CsrfMiddleware(), new PermissionMiddleware('obreiros', 'excluir')]);

    $router->post('/admin/modulos/obreiros/{id}/restaurar', static function (string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        ObreiroRepository::restaurar((int) $id);
        Audit::registrar('editar', 'obreiros', 'obreiros', (int) $id, ['deleted_at' => $obreiro['deleted_at']], ['deleted_at' => null]);
        Session::flash('sucesso', 'Obreiro restaurado com sucesso.');
        return redirect('admin/modulos/obreiros');
    }, [new CsrfMiddleware(), new PermissionMiddleware('obreiros', 'excluir')]);

    $router->get('/admin/modulos/arquivo_morto', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'situacao' => (string) $request->query('situacao', ''),
            'restaurado' => (string) $request->query('restaurado', ''),
            'data_inicio' => (string) $request->query('data_inicio', ''),
            'data_fim' => (string) $request->query('data_fim', ''),
        ];

        return admin_view('admin/arquivo_morto/index', [
            'titulo' => 'Arquivo Morto',
            'subtitulo' => 'Histórico de obreiros desligados, licenciados e restaurados.',
            'registros' => ArquivoMortoRepository::listar($filtros),
            'estatisticas' => ArquivoMortoRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'arquivo_morto', 'arquivo_morto', 'ver');
    }, [new PermissionMiddleware('arquivo_morto', 'ver')]);

    $router->get('/admin/modulos/arquivo_morto/arquivar', static function (): Response {
        return admin_view('admin/arquivo_morto/selecionar', [
            'titulo' => 'Arquivar obreiro',
            'subtitulo' => 'Selecione o obreiro que deve ser enviado para o Arquivo Morto.',
            'obreiros' => ArquivoMortoRepository::arquivaveis(),
        ], 'arquivo_morto', 'arquivo_morto', 'transferir');
    }, [new PermissionMiddleware('arquivo_morto', 'transferir')]);

    $router->get('/admin/modulos/arquivo_morto/arquivar/{id}', static function (string $id) use ($estadoFormulario): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }
        if (ArquivoMortoRepository::buscarPorObreiro((int) $id) !== null) {
            Session::flash('erro', 'Este obreiro já possui registro no Arquivo Morto.');
            return redirect('admin/modulos/arquivo_morto');
        }

        $estado = $estadoFormulario();
        $situacaoAtual = (string) $obreiro['situacao'];
        $padrao = [
            'motivo' => '',
            'data_desligamento' => date('Y-m-d'),
            'situacao' => in_array($situacaoAtual, ['licenciado', 'desligado'], true) ? $situacaoAtual : 'desligado',
            'responsavel_id' => (string) (Auth::id() ?? ''),
            'observacoes' => '',
        ];

        return admin_view('admin/arquivo_morto/form', [
            'titulo' => 'Arquivar obreiro',
            'subtitulo' => 'Registre o motivo e a data de saída de ' . (string) $obreiro['nome'] . '.',
            'obreiro' => $obreiro,
            'valores' => array_merge($padrao, $estado['valores']),
            'erros' => $estado['erros'],
            'responsaveis' => UserRepository::listar(),
        ], 'arquivo_morto', 'arquivo_morto', 'transferir');
    }, [new PermissionMiddleware('arquivo_morto', 'transferir')]);

    $router->get('/admin/modulos/arquivo_morto/{id}/ficha', static function (string $id): Response {
        $registro = ArquivoMortoRepository::buscar((int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        return Response::html(View::render('admin/arquivo_morto/ficha', [
            'titulo' => 'Ficha histórica do Arquivo Morto',
            'registro' => $registro,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('arquivo_morto', 'imprimir')]);

    $router->post('/admin/modulos/arquivo_morto/arquivar/{id}', static function (Request $request, string $id): Response {
        $obreiro = ObreiroRepository::buscar((int) $id, true);
        if ($obreiro === null) {
            throw new HttpException(404, 'O obreiro solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), ArquivoMortoRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/arquivo_morto/arquivar/' . (int) $id);
        }

        $arquivoId = ArquivoMortoRepository::arquivar((int) $id, $validacao['data']);
        Audit::registrar('transferir', 'arquivo_morto', 'arquivo_morto', $arquivoId, null, $validacao['data']);
        Session::flash('sucesso', 'Obreiro enviado para o Arquivo Morto com sucesso.');
        return redirect('admin/modulos/arquivo_morto');
    }, [new CsrfMiddleware(), new PermissionMiddleware('arquivo_morto', 'transferir')]);

    $router->post('/admin/modulos/arquivo_morto/{id}/restaurar', static function (string $id): Response {
        $registro = ArquivoMortoRepository::buscar((int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        ArquivoMortoRepository::restaurar((int) $id);
        Audit::registrar('restaurar', 'arquivo_morto', 'arquivo_morto', (int) $id, [
            'restaurado_em' => $registro['restaurado_em'],
        ], [
            'restaurado_em' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('sucesso', 'Obreiro restaurado do Arquivo Morto com sucesso.');
        return redirect('admin/modulos/arquivo_morto');
    }, [new CsrfMiddleware(), new PermissionMiddleware('arquivo_morto', 'restaurar')]);

    $sacramentoTipo = static function (string $tipo): string {
        if (!in_array($tipo, ['batismo', 'consagracao'], true)) {
            throw new HttpException(404, 'Tipo de sacramento inválido.');
        }

        return $tipo;
    };

    $router->get('/admin/modulos/sacramentos', static function (Request $request) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo((string) $request->query('tipo', 'batismo'));
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'ano' => (string) $request->query('ano', ''),
            'diploma' => (string) $request->query('diploma', ''),
        ];

        return admin_view('admin/sacramentos/index', [
            'titulo' => 'Sacramentos',
            'subtitulo' => 'Registros de batismos, consagrações e diplomas emitidos.',
            'tipo' => $tipo,
            'tituloTipo' => $tipo === 'batismo' ? 'Batismos' : 'Consagrações',
            'registros' => SacramentoRepository::listar($tipo, $filtros),
            'estatisticas' => SacramentoRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'sacramentos', 'sacramentos', 'ver');
    }, [new PermissionMiddleware('sacramentos', 'ver')]);

    $router->get('/admin/modulos/sacramentos/{tipo}/novo', static function (string $tipo) use ($sacramentoTipo, $estadoFormulario): Response {
        $tipo = $sacramentoTipo($tipo);
        $estado = $estadoFormulario();
        $numero = SacramentoRepository::proximoNumero($tipo);
        $padrao = [
            'data_' . ($tipo === 'batismo' ? 'batismo' : 'consagracao') => date('Y-m-d'),
            'numero_' . ($tipo === 'batismo' ? 'certificado' : 'diploma') => $numero,
            'local' => '',
            'membro_id' => '',
            'obreiro_id' => '',
            'cargo_id' => '',
            'pastor_celebrante_id' => '',
            'pastor_presidente_id' => '',
            'observacoes' => '',
        ];

        return admin_view('admin/sacramentos/form', [
            'titulo' => 'Novo ' . ($tipo === 'batismo' ? 'batismo' : 'consagração'),
            'subtitulo' => 'Preencha os dados do sacramento e do celebrante.',
            'tipo' => $tipo,
            'registro' => null,
            'valores' => array_merge($padrao, $estado['valores']),
            'erros' => $estado['erros'],
            'membros' => SacramentoRepository::membros(),
            'obreiros' => SacramentoRepository::obreiros(),
            'cargos' => SacramentoRepository::cargos(),
        ], 'sacramentos', 'sacramentos', 'criar');
    }, [new PermissionMiddleware('sacramentos', 'criar')]);

    $router->get('/admin/modulos/sacramentos/{tipo}/{id}/editar', static function (string $tipo, string $id) use ($sacramentoTipo, $estadoFormulario): Response {
        $tipo = $sacramentoTipo($tipo);
        $registro = SacramentoRepository::buscar($tipo, (int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $membroId = (int) ($estado['valores']['membro_id'] ?? $registro['membro_id'] ?? 0);
        $obreiroId = (int) ($estado['valores']['obreiro_id'] ?? $registro['obreiro_id'] ?? 0);
        $cargoId = (int) ($estado['valores']['cargo_id'] ?? $registro['cargo_id'] ?? 0);

        return admin_view('admin/sacramentos/form', [
            'titulo' => 'Editar ' . ($tipo === 'batismo' ? 'batismo' : 'consagração'),
            'subtitulo' => 'Atualize os dados do registro ministerial.',
            'tipo' => $tipo,
            'registro' => $registro,
            'valores' => array_merge($registro, $estado['valores']),
            'erros' => $estado['erros'],
            'membros' => SacramentoRepository::membros($membroId > 0 ? $membroId : null),
            'obreiros' => SacramentoRepository::obreiros($obreiroId > 0 ? $obreiroId : null),
            'cargos' => SacramentoRepository::cargos($cargoId > 0 ? $cargoId : null),
        ], 'sacramentos', 'sacramentos', 'editar');
    }, [new PermissionMiddleware('sacramentos', 'editar')]);

    $router->get('/admin/modulos/sacramentos/{tipo}/{id}/diploma', static function (string $tipo, string $id) use ($sacramentoTipo, $estadoFormulario): Response {
        $tipo = $sacramentoTipo($tipo);
        $registro = SacramentoRepository::buscar($tipo, (int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $diploma = SacramentoRepository::diploma($tipo, (int) $id);
        $estado = $estadoFormulario();
        $padrao = [
            'modelo' => (string) ($diploma['modelo'] ?? 'classico'),
            'assinaturas' => (string) ($diploma['assinaturas'] ?? ''),
        ];

        return admin_view('admin/sacramentos/diploma', [
            'titulo' => 'Emitir diploma',
            'subtitulo' => 'Configure o modelo e as assinaturas antes de imprimir.',
            'tipo' => $tipo,
            'registro' => $registro,
            'diploma' => $diploma,
            'valores' => array_merge($padrao, $estado['valores']),
            'erros' => $estado['erros'],
        ], 'sacramentos', 'sacramentos', 'editar');
    }, [new PermissionMiddleware('sacramentos', 'editar')]);

    $router->get('/admin/modulos/sacramentos/{tipo}/{id}/diploma/imprimir', static function (string $tipo, string $id) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo($tipo);
        $diploma = SacramentoRepository::diploma($tipo, (int) $id);
        if ($diploma === null) {
            Session::flash('erro', 'Emita o diploma antes de imprimir.');
            return redirect('admin/modulos/sacramentos/' . $tipo . '/' . (int) $id . '/diploma');
        }

        return Response::html(View::render('admin/sacramentos/diploma_print', [
            'titulo' => 'Diploma',
            'tipo' => $tipo,
            'diploma' => $diploma,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('sacramentos', 'ver')]);

    $router->post('/admin/modulos/sacramentos/{tipo}', static function (Request $request, string $tipo) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo($tipo);
        $validacao = Validator::validate($request->post(), SacramentoRepository::regras($tipo));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/sacramentos/' . $tipo . '/novo');
        }

        $id = SacramentoRepository::salvar($tipo, null, $validacao['data']);
        Audit::registrar('criar', 'sacramentos', $tipo === 'batismo' ? 'batismos' : 'consagracoes', $id, null, $validacao['data']);
        Session::flash('sucesso', 'Registro criado com sucesso.');
        return redirect('admin/modulos/sacramentos/' . $tipo . '/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('sacramentos', 'criar')]);

    $router->post('/admin/modulos/sacramentos/{tipo}/{id}', static function (Request $request, string $tipo, string $id) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo($tipo);
        $registro = SacramentoRepository::buscar($tipo, (int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), SacramentoRepository::regras($tipo, (int) $id));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/sacramentos/' . $tipo . '/' . (int) $id . '/editar');
        }

        SacramentoRepository::salvar($tipo, (int) $id, $validacao['data']);
        Audit::registrar('editar', 'sacramentos', $tipo === 'batismo' ? 'batismos' : 'consagracoes', (int) $id, $registro, $validacao['data']);
        Session::flash('sucesso', 'Registro atualizado com sucesso.');
        return redirect('admin/modulos/sacramentos/' . $tipo . '/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('sacramentos', 'editar')]);

    $router->post('/admin/modulos/sacramentos/{tipo}/{id}/diploma', static function (Request $request, string $tipo, string $id) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo($tipo);
        $validacao = Validator::validate($request->post(), SacramentoRepository::regrasDiploma());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/sacramentos/' . $tipo . '/' . (int) $id . '/diploma');
        }

        $diplomaId = SacramentoRepository::emitirDiploma($tipo, (int) $id, $validacao['data']);
        Audit::registrar('criar', 'sacramentos', $tipo === 'batismo' ? 'diplomas_batismo' : 'diplomas_consagracao', $diplomaId, null, $validacao['data']);
        Session::flash('sucesso', 'Diploma emitido com sucesso.');
        return redirect('admin/modulos/sacramentos/' . $tipo . '/' . (int) $id . '/diploma/imprimir');
    }, [new CsrfMiddleware(), new PermissionMiddleware('sacramentos', 'editar')]);

    $router->post('/admin/modulos/sacramentos/{tipo}/{id}/excluir', static function (string $tipo, string $id) use ($sacramentoTipo): Response {
        $tipo = $sacramentoTipo($tipo);
        $registro = SacramentoRepository::buscar($tipo, (int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'O registro solicitado não foi encontrado.');
        }

        SacramentoRepository::excluir($tipo, (int) $id);
        Audit::registrar('excluir', 'sacramentos', $tipo === 'batismo' ? 'batismos' : 'consagracoes', (int) $id, $registro, null);
        Session::flash('sucesso', 'Registro excluído com sucesso.');
        return redirect('admin/modulos/sacramentos?tipo=' . $tipo);
    }, [new CsrfMiddleware(), new PermissionMiddleware('sacramentos', 'excluir')]);

    $filtrosFinanceiro = static function (Request $request): array {
        return [
            'q' => trim((string) $request->query('q', '')),
            'tipo' => (string) $request->query('tipo', ''),
            'origem' => (string) $request->query('origem', ''),
            'categoria_id' => (string) $request->query('categoria_id', ''),
            'congregacao_id' => (string) $request->query('congregacao_id', ''),
            'competencia' => (string) $request->query('competencia', ''),
            'fechado' => (string) $request->query('fechado', ''),
        ];
    };

    $buscarCategoriaFinanceira = static function (int $id): ?array {
        foreach (FinanceiroRepository::categoriasParaGestao() as $categoria) {
            if ((int) $categoria['id'] === $id) {
                return $categoria;
            }
        }

        return null;
    };

    $router->get('/admin/modulos/financeiro', static function (Request $request) use ($filtrosFinanceiro): Response {
        $filtros = $filtrosFinanceiro($request);

        return admin_view('admin/financeiro/index', [
            'titulo' => 'Financeiro',
            'subtitulo' => 'Dízimos, ofertas, entradas, saídas e fechamentos mensais.',
            'filtros' => $filtros,
            'registros' => FinanceiroRepository::listar($filtros),
            'estatisticas' => FinanceiroRepository::estatisticas($filtros),
            'categorias' => FinanceiroRepository::categorias(),
            'congregacoes' => FinanceiroRepository::congregacoes(),
        ], 'financeiro', 'financeiro', 'ver');
    }, [new PermissionMiddleware('financeiro', 'ver')]);

    $router->get('/admin/modulos/financeiro/novo', static function () use ($estadoFormulario): Response {
        $estado = $estadoFormulario();
        $padrao = [
            'tipo' => 'entrada',
            'origem' => 'geral',
            'categoria_id' => '',
            'congregacao_id' => '',
            'descricao' => '',
            'valor' => '',
            'data_movimentacao' => date('Y-m-d'),
            'forma_pagamento' => 'dinheiro',
            'referencia_mes' => '',
            'competencia' => date('Y-m'),
            'membro_id' => '',
            'ofertante_id' => '',
            'tipo_oferta' => '',
            'observacoes' => '',
        ];

        return admin_view('admin/financeiro/form', [
            'titulo' => 'Nova movimentação',
            'subtitulo' => 'Lance dízimos, ofertas, entradas e saídas do período.',
            'registro' => null,
            'valores' => array_merge($padrao, $estado['valores']),
            'erros' => $estado['erros'],
            'categorias' => FinanceiroRepository::categorias(),
            'congregacoes' => FinanceiroRepository::congregacoes(),
            'membros' => FinanceiroRepository::membros(),
        ], 'financeiro', 'financeiro', 'lancar');
    }, [new PermissionMiddleware('financeiro', 'lancar')]);

    $router->get('/admin/modulos/financeiro/categorias', static function (Request $request) use ($estadoFormulario, $buscarCategoriaFinanceira): Response {
        $estado = $estadoFormulario();
        $editar = (int) $request->query('editar', 0);
        $categoria = $editar > 0 ? $buscarCategoriaFinanceira($editar) : null;

        return admin_view('admin/financeiro/categorias', [
            'titulo' => 'Categorias financeiras',
            'subtitulo' => 'Classifique entradas e saídas do caixa da igreja.',
            'categorias' => FinanceiroRepository::categoriasParaGestao(),
            'categoria' => $categoria,
            'valores' => $estado['valores'],
            'erros' => $estado['erros'],
        ], 'financeiro', 'financeiro', 'ver');
    }, [new PermissionMiddleware('financeiro', 'ver')]);

    $router->get('/admin/modulos/financeiro/fechamentos', static function () use ($estadoFormulario): Response {
        $estado = $estadoFormulario();

        return admin_view('admin/financeiro/fechamentos', [
            'titulo' => 'Fechamentos mensais',
            'subtitulo' => 'Consolide entradas, saídas e saldo por competência.',
            'fechamentos' => FinanceiroRepository::fechamentos(),
            'congregacoes' => FinanceiroRepository::congregacoes(),
            'competencias' => FinanceiroRepository::competencias(),
            'valores' => $estado['valores'],
            'erros' => $estado['erros'],
        ], 'financeiro', 'financeiro', 'ver');
    }, [new PermissionMiddleware('financeiro', 'ver')]);

    $router->get('/admin/modulos/financeiro/relatorio', static function (Request $request) use ($filtrosFinanceiro): Response {
        $filtros = $filtrosFinanceiro($request);

        return admin_view('admin/financeiro/relatorio', [
            'titulo' => 'Relatório financeiro',
            'subtitulo' => 'Totais agrupados por categoria e tipo de movimentação.',
            'filtros' => $filtros,
            'relatorio' => FinanceiroRepository::relatorio($filtros),
            'estatisticas' => FinanceiroRepository::estatisticas($filtros),
            'categorias' => FinanceiroRepository::categorias(),
            'congregacoes' => FinanceiroRepository::congregacoes(),
            'competencias' => FinanceiroRepository::competencias(),
        ], 'financeiro', 'financeiro', 'relatorio');
    }, [new PermissionMiddleware('financeiro', 'relatorio')]);

    $router->get('/admin/modulos/financeiro/relatorio/imprimir', static function (Request $request) use ($filtrosFinanceiro): Response {
        $filtros = $filtrosFinanceiro($request);

        return Response::html(View::render('admin/financeiro/relatorio_print', [
            'titulo' => 'Relatório financeiro',
            'filtros' => $filtros,
            'relatorio' => FinanceiroRepository::relatorio($filtros),
            'estatisticas' => FinanceiroRepository::estatisticas($filtros),
        ], 'layouts/print'));
    }, [new PermissionMiddleware('financeiro', 'relatorio')]);

    $router->get('/admin/modulos/financeiro/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $registro = FinanceiroRepository::buscar((int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'A movimentação solicitada não foi encontrada.');
        }

        $origem = FinanceiroRepository::origem((int) $id);
        $dadosOrigem = $origem === 'dizimo'
            ? ['origem' => 'dizimo', 'membro_id' => $registro['dizimista_id'] ?? '', 'referencia_mes' => $registro['referencia_dizimo'] ?? '']
            : ($origem === 'oferta'
                ? ['origem' => 'oferta', 'ofertante_id' => $registro['ofertante_id'] ?? '', 'tipo_oferta' => $registro['tipo_oferta'] ?? '']
                : ['origem' => 'geral']);
        $estado = $estadoFormulario();

        return admin_view('admin/financeiro/form', [
            'titulo' => 'Editar movimentação',
            'subtitulo' => 'Atualize os dados do lançamento financeiro.',
            'registro' => $registro,
            'valores' => array_merge($registro, $dadosOrigem, $estado['valores']),
            'erros' => $estado['erros'],
            'categorias' => FinanceiroRepository::categorias(),
            'congregacoes' => FinanceiroRepository::congregacoes(),
            'membros' => FinanceiroRepository::membros(),
        ], 'financeiro', 'financeiro', 'editar');
    }, [new PermissionMiddleware('financeiro', 'editar')]);

    $router->post('/admin/modulos/financeiro', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), FinanceiroRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/novo');
        }

        $id = FinanceiroRepository::salvar(null, $validacao['data']);
        Audit::registrar('criar', 'financeiro', 'movimentacoes', $id, null, $validacao['data']);
        Session::flash('sucesso', 'Lançamento criado com sucesso.');
        return redirect('admin/modulos/financeiro/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'lancar')]);

    $router->post('/admin/modulos/financeiro/categorias', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), FinanceiroRepository::regrasCategoria());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/categorias');
        }

        try {
            $id = FinanceiroRepository::salvarCategoria(null, $validacao['data']);
        } catch (\InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/categorias');
        }

        Audit::registrar('criar', 'financeiro', 'categorias_financeiras', $id, null, $validacao['data']);
        Session::flash('sucesso', 'Categoria criada com sucesso.');
        return redirect('admin/modulos/financeiro/categorias');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'lancar')]);

    $router->post('/admin/modulos/financeiro/categorias/{id}', static function (Request $request, string $id) use ($buscarCategoriaFinanceira): Response {
        $anterior = $buscarCategoriaFinanceira((int) $id);
        if ($anterior === null) {
            throw new HttpException(404, 'A categoria solicitada não foi encontrada.');
        }

        $validacao = Validator::validate($request->post(), FinanceiroRepository::regrasCategoria());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/categorias?editar=' . (int) $id);
        }

        try {
            FinanceiroRepository::salvarCategoria((int) $id, $validacao['data']);
        } catch (\InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/categorias?editar=' . (int) $id);
        }

        Audit::registrar('editar', 'financeiro', 'categorias_financeiras', (int) $id, $anterior, $validacao['data']);
        Session::flash('sucesso', 'Categoria atualizada com sucesso.');
        return redirect('admin/modulos/financeiro/categorias');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'editar')]);

    $router->post('/admin/modulos/financeiro/categorias/{id}/excluir', static function (string $id) use ($buscarCategoriaFinanceira): Response {
        $anterior = $buscarCategoriaFinanceira((int) $id);
        if ($anterior === null) {
            throw new HttpException(404, 'A categoria solicitada não foi encontrada.');
        }

        try {
            FinanceiroRepository::excluirCategoria((int) $id);
        } catch (\InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            return redirect('admin/modulos/financeiro/categorias');
        }

        Audit::registrar('excluir', 'financeiro', 'categorias_financeiras', (int) $id, $anterior, null);
        Session::flash('sucesso', 'Categoria excluída com sucesso.');
        return redirect('admin/modulos/financeiro/categorias');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'excluir')]);

    $router->post('/admin/modulos/financeiro/fechamentos', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), [
            'competencia' => 'required|month',
            'congregacao_id' => 'integer',
            'observacoes' => 'max:1000',
        ]);
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/fechamentos');
        }

        $dados = $validacao['data'];
        $id = FinanceiroRepository::fechar(
            (string) $dados['competencia'],
            (int) ($dados['congregacao_id'] ?? 0),
            (string) ($dados['observacoes'] ?? '')
        );
        Audit::registrar('fechar', 'financeiro', 'fechamentos_mensais', $id, null, $dados);
        Session::flash('sucesso', 'Fechamento mensal realizado com sucesso.');
        return redirect('admin/modulos/financeiro/fechamentos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'fechar')]);

    $router->post('/admin/modulos/financeiro/{id}', static function (Request $request, string $id): Response {
        $registro = FinanceiroRepository::buscar((int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'A movimentação solicitada não foi encontrada.');
        }
        if ((int) $registro['fechado'] === 1) {
            Session::flash('erro', 'Não é possível alterar uma movimentação de competência fechada.');
            return redirect('admin/modulos/financeiro');
        }

        $validacao = Validator::validate($request->post(), FinanceiroRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/financeiro/' . (int) $id . '/editar');
        }

        FinanceiroRepository::salvar((int) $id, $validacao['data']);
        Audit::registrar('editar', 'financeiro', 'movimentacoes', (int) $id, $registro, $validacao['data']);
        Session::flash('sucesso', 'Lançamento atualizado com sucesso.');
        return redirect('admin/modulos/financeiro/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'editar')]);

    $router->post('/admin/modulos/financeiro/{id}/excluir', static function (string $id): Response {
        $registro = FinanceiroRepository::buscar((int) $id);
        if ($registro === null) {
            throw new HttpException(404, 'A movimentação solicitada não foi encontrada.');
        }
        if ((int) $registro['fechado'] === 1) {
            Session::flash('erro', 'Não é possível excluir uma movimentação de competência fechada.');
            return redirect('admin/modulos/financeiro');
        }

        FinanceiroRepository::excluir((int) $id);
        Audit::registrar('excluir', 'financeiro', 'movimentacoes', (int) $id, $registro, null);
        Session::flash('sucesso', 'Lançamento excluído com sucesso.');
        return redirect('admin/modulos/financeiro');
    }, [new CsrfMiddleware(), new PermissionMiddleware('financeiro', 'excluir')]);

    $router->get('/admin/modulos/documentos', static function (Request $request) use ($estadoFormulario): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'modelo' => (string) $request->query('modelo', ''),
            'de' => (string) $request->query('de', ''),
            'ate' => (string) $request->query('ate', ''),
        ];

        return admin_view('admin/documentos/index', [
            'titulo' => 'Documentos',
            'subtitulo' => 'Emissão de cartas-convite e acompanhamento dos documentos gerados.',
            'convites' => DocumentoRepository::convites($filtros),
            'estatisticas' => DocumentoRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'documentos', 'documentos', 'ver');
    }, [new PermissionMiddleware('documentos', 'ver')]);

    $router->get('/admin/modulos/documentos/novo', static function () use ($estadoFormulario): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/documentos/form', [
            'titulo' => 'Novo convite',
            'subtitulo' => 'Preencha os dados para emitir a carta-convite.',
            'convite' => null,
            'valores' => $estado['valores'],
            'erros' => $estado['erros'],
        ], 'documentos', 'documentos', 'emitir');
    }, [new PermissionMiddleware('documentos', 'emitir')]);

    $router->get('/admin/modulos/documentos/emitidos', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'tipo' => (string) $request->query('tipo', ''),
            'de' => (string) $request->query('de', ''),
            'ate' => (string) $request->query('ate', ''),
        ];

        return admin_view('admin/documentos/emitidos', [
            'titulo' => 'Documentos emitidos',
            'subtitulo' => 'Histórico central de emissões realizadas no sistema.',
            'documentos' => DocumentoRepository::documentos($filtros),
            'filtros' => $filtros,
        ], 'documentos', 'documentos', 'ver');
    }, [new PermissionMiddleware('documentos', 'ver')]);

    $router->get('/admin/modulos/documentos/emitidos/{id}/imprimir', static function (string $id): Response {
        $documento = DocumentoRepository::buscarDocumento((int) $id);
        if ($documento === null) {
            throw new HttpException(404, 'O documento solicitado não foi encontrado.');
        }
        if ((string) $documento['tipo'] === 'carta_convite') {
            return redirect('admin/modulos/documentos/' . (int) $documento['referencia_id'] . '/imprimir');
        }

        return Response::html(View::render('admin/documentos/documento_print', [
            'titulo' => DocumentoRepository::rotuloTipo((string) $documento['tipo']),
            'documento' => $documento,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('documentos', 'ver')]);

    $router->get('/admin/modulos/documentos/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $convite = DocumentoRepository::buscarConvite((int) $id);
        if ($convite === null) {
            throw new HttpException(404, 'O convite solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $valores = $estado['valores'] === [] ? DocumentoRepository::valoresFormulario($convite) : $estado['valores'];
        return admin_view('admin/documentos/form', [
            'titulo' => 'Editar convite',
            'subtitulo' => 'Atualize os dados e reemita o documento.',
            'convite' => $convite,
            'valores' => $valores,
            'erros' => $estado['erros'],
        ], 'documentos', 'documentos', 'emitir');
    }, [new PermissionMiddleware('documentos', 'emitir')]);

    $router->get('/admin/modulos/documentos/{id}/imprimir', static function (string $id): Response {
        $convite = DocumentoRepository::buscarConvite((int) $id);
        if ($convite === null) {
            throw new HttpException(404, 'O convite solicitado não foi encontrado.');
        }

        return Response::html(View::render('admin/documentos/convite_print', [
            'titulo' => 'Carta-convite nº ' . (string) $convite['numero'],
            'convite' => $convite,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('documentos', 'ver')]);

    $router->post('/admin/modulos/documentos', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), DocumentoRepository::regrasConvite());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/documentos/novo');
        }

        $id = DocumentoRepository::salvarConvite(null, $validacao['data']);
        $convite = DocumentoRepository::buscarConvite($id);
        $documentoId = DocumentoRepository::registrar(
            'carta_convite',
            $id,
            (string) $convite['numero'],
            (string) $validacao['data']['modelo'],
            DocumentoRepository::dadosConvite($convite)
        );
        Audit::registrar('criar', 'documentos', 'convites', $id, null, $validacao['data']);
        Audit::registrar('emitir', 'documentos', 'documentos_emitidos', $documentoId, null, $convite['numero']);
        Session::flash('sucesso', 'Convite emitido com sucesso.');
        return redirect('admin/modulos/documentos/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('documentos', 'emitir')]);

    $router->post('/admin/modulos/documentos/emitidos/{id}/excluir', static function (string $id): Response {
        $documento = DocumentoRepository::buscarDocumento((int) $id);
        if ($documento === null) {
            throw new HttpException(404, 'O documento solicitado não foi encontrado.');
        }

        if ((string) $documento['tipo'] === 'carta_convite') {
            $referencia = (int) $documento['referencia_id'];
            $convite = DocumentoRepository::buscarConvite($referencia);
            DocumentoRepository::excluirConvite($referencia);
            Audit::registrar('excluir', 'documentos', 'convites', $referencia, $convite, null);
        } else {
            DocumentoRepository::excluirDocumento((int) $id);
            Audit::registrar('excluir', 'documentos', 'documentos_emitidos', (int) $id, $documento, null);
        }

        Session::flash('sucesso', 'Documento cancelado com sucesso.');
        return redirect('admin/modulos/documentos/emitidos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('documentos', 'excluir')]);

    $router->post('/admin/modulos/documentos/{id}', static function (Request $request, string $id): Response {
        $convite = DocumentoRepository::buscarConvite((int) $id);
        if ($convite === null) {
            throw new HttpException(404, 'O convite solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), DocumentoRepository::regrasConvite((int) $id));
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/documentos/' . (int) $id . '/editar');
        }

        DocumentoRepository::salvarConvite((int) $id, $validacao['data']);
        $atualizado = DocumentoRepository::buscarConvite((int) $id);
        $documentoId = DocumentoRepository::registrar(
            'carta_convite',
            (int) $id,
            (string) $atualizado['numero'],
            (string) $validacao['data']['modelo'],
            DocumentoRepository::dadosConvite($atualizado)
        );
        Audit::registrar('editar', 'documentos', 'convites', (int) $id, $convite, $validacao['data']);
        Audit::registrar('emitir', 'documentos', 'documentos_emitidos', $documentoId, null, $atualizado['numero']);
        Session::flash('sucesso', 'Convite atualizado e reemitido com sucesso.');
        return redirect('admin/modulos/documentos/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('documentos', 'emitir')]);

    $router->post('/admin/modulos/documentos/{id}/excluir', static function (string $id): Response {
        $convite = DocumentoRepository::buscarConvite((int) $id);
        if ($convite === null) {
            throw new HttpException(404, 'O convite solicitado não foi encontrado.');
        }

        DocumentoRepository::excluirConvite((int) $id);
        Audit::registrar('excluir', 'documentos', 'convites', (int) $id, $convite, null);
        Session::flash('sucesso', 'Convite cancelado com sucesso.');
        return redirect('admin/modulos/documentos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('documentos', 'excluir')]);

    $router->get('/admin/modulos/carteirinhas', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'tipo' => (string) $request->query('tipo', ''),
            'modelo' => (string) $request->query('modelo', ''),
            'congregacao_id' => (string) $request->query('congregacao_id', ''),
            'situacao' => (string) $request->query('situacao', ''),
            'segunda_via' => (string) $request->query('segunda_via', ''),
        ];

        return admin_view('admin/carteirinhas/index', [
            'titulo' => 'Carteirinhas',
            'subtitulo' => 'Emissão, segundas vias e validação de carteirinhas eclesiásticas.',
            'carteirinhas' => CarteirinhaRepository::listar($filtros),
            'estatisticas' => CarteirinhaRepository::estatisticas(),
            'congregacoes' => CarteirinhaRepository::congregacoes(),
            'filtros' => $filtros,
        ], 'carteirinhas', 'carteirinhas', 'ver');
    }, [new PermissionMiddleware('carteirinhas', 'ver')]);

    $router->get('/admin/modulos/carteirinhas/novo', static function () use ($estadoFormulario): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/carteirinhas/form', [
            'titulo' => 'Nova carteirinha',
            'subtitulo' => 'Emita uma carteirinha com código de validação único.',
            'carteirinha' => null,
            'membros' => CarteirinhaRepository::membros(),
            'obreiros' => CarteirinhaRepository::obreiros(),
            'valores' => $estado['valores'],
            'erros' => $estado['erros'],
        ], 'carteirinhas', 'carteirinhas', 'emitir');
    }, [new PermissionMiddleware('carteirinhas', 'emitir')]);

    $router->get('/admin/modulos/carteirinhas/validar', static function (Request $request): Response {
        return admin_view('admin/carteirinhas/validar', [
            'titulo' => 'Validar carteirinha',
            'subtitulo' => 'Confira a autenticidade pelo código ou QR Code.',
            'codigo' => trim((string) $request->query('codigo', '')),
            'resultado' => null,
            'erros' => [],
        ], 'carteirinhas', 'carteirinhas', 'validar');
    }, [new PermissionMiddleware('carteirinhas', 'validar')]);

    $router->get('/admin/modulos/carteirinhas/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $carteirinha = CarteirinhaRepository::buscar((int) $id);
        if ($carteirinha === null) {
            throw new HttpException(404, 'A carteirinha solicitada não foi encontrada.');
        }

        $estado = $estadoFormulario();
        $valores = $estado['valores'] === [] ? CarteirinhaRepository::valoresFormulario($carteirinha) : $estado['valores'];
        return admin_view('admin/carteirinhas/form', [
            'titulo' => 'Editar carteirinha',
            'subtitulo' => 'Atualize os dados e reemita o documento.',
            'carteirinha' => $carteirinha,
            'membros' => CarteirinhaRepository::membros(),
            'obreiros' => CarteirinhaRepository::obreiros(),
            'valores' => $valores,
            'erros' => $estado['erros'],
        ], 'carteirinhas', 'carteirinhas', 'emitir');
    }, [new PermissionMiddleware('carteirinhas', 'emitir')]);

    $router->get('/admin/modulos/carteirinhas/{id}/imprimir', static function (string $id): Response {
        $carteirinha = CarteirinhaRepository::buscar((int) $id);
        if ($carteirinha === null) {
            throw new HttpException(404, 'A carteirinha solicitada não foi encontrada.');
        }

        return Response::html(View::render('admin/carteirinhas/carteirinha_print', [
            'titulo' => 'Carteirinha ' . (string) $carteirinha['codigo_validacao'],
            'carteirinha' => $carteirinha,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('carteirinhas', 'ver')]);

    $router->post('/admin/modulos/carteirinhas', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), CarteirinhaRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/carteirinhas/novo');
        }

        try {
            $id = CarteirinhaRepository::salvar(null, $validacao['data']);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            return redirect('admin/modulos/carteirinhas/novo');
        }

        $carteirinha = CarteirinhaRepository::buscar($id);
        $documentoId = CarteirinhaRepository::registrarDocumento($carteirinha);
        Audit::registrar('emitir', 'carteirinhas', 'carteirinhas', $id, null, $validacao['data']);
        Audit::registrar('emitir', 'carteirinhas', 'documentos_emitidos', $documentoId, null, $carteirinha['codigo_validacao']);
        Session::flash('sucesso', 'Carteirinha emitida com sucesso.');
        return redirect('admin/modulos/carteirinhas/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('carteirinhas', 'emitir')]);

    $router->post('/admin/modulos/carteirinhas/validar', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), ['codigo' => 'required|max:64']);
        $codigo = trim((string) ($validacao['data']['codigo'] ?? ''));
        if (!$validacao['ok']) {
            return admin_view('admin/carteirinhas/validar', [
                'titulo' => 'Validar carteirinha',
                'subtitulo' => 'Confira a autenticidade pelo código ou QR Code.',
                'codigo' => $codigo,
                'resultado' => null,
                'erros' => $validacao['errors'],
            ], 'carteirinhas', 'carteirinhas', 'validar');
        }

        $resultado = CarteirinhaRepository::validar($codigo);
        Audit::registrar(
            'validar',
            'carteirinhas',
            'carteirinhas',
            $resultado === null ? null : (int) $resultado['carteirinha']['id'],
            null,
            ['codigo' => $codigo, 'resultado' => $resultado['resultado'] ?? 'nao_encontrada']
        );

        return admin_view('admin/carteirinhas/validar', [
            'titulo' => 'Validar carteirinha',
            'subtitulo' => 'Confira a autenticidade pelo código ou QR Code.',
            'codigo' => $codigo,
            'resultado' => $resultado,
            'erros' => [],
        ], 'carteirinhas', 'carteirinhas', 'validar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('carteirinhas', 'validar')]);

    $router->post('/admin/modulos/carteirinhas/{id}', static function (Request $request, string $id): Response {
        $carteirinha = CarteirinhaRepository::buscar((int) $id);
        if ($carteirinha === null) {
            throw new HttpException(404, 'A carteirinha solicitada não foi encontrada.');
        }

        $validacao = Validator::validate($request->post(), CarteirinhaRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/carteirinhas/' . (int) $id . '/editar');
        }

        try {
            CarteirinhaRepository::salvar((int) $id, $validacao['data']);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            return redirect('admin/modulos/carteirinhas/' . (int) $id . '/editar');
        }

        $atualizada = CarteirinhaRepository::buscar((int) $id);
        $documentoId = CarteirinhaRepository::registrarDocumento($atualizada);
        Audit::registrar('editar', 'carteirinhas', 'carteirinhas', (int) $id, $carteirinha, $validacao['data']);
        Audit::registrar('emitir', 'carteirinhas', 'documentos_emitidos', $documentoId, null, $atualizada['codigo_validacao']);
        Session::flash('sucesso', 'Carteirinha atualizada com sucesso.');
        return redirect('admin/modulos/carteirinhas/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('carteirinhas', 'emitir')]);

    $router->post('/admin/modulos/carteirinhas/{id}/excluir', static function (string $id): Response {
        $carteirinha = CarteirinhaRepository::buscar((int) $id);
        if ($carteirinha === null) {
            throw new HttpException(404, 'A carteirinha solicitada não foi encontrada.');
        }

        CarteirinhaRepository::excluir((int) $id);
        Audit::registrar('excluir', 'carteirinhas', 'carteirinhas', (int) $id, $carteirinha, null);
        Session::flash('sucesso', 'Carteirinha cancelada com sucesso.');
        return redirect('admin/modulos/carteirinhas');
    }, [new CsrfMiddleware(), new PermissionMiddleware('carteirinhas', 'excluir')]);

    $eventoFoto = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('foto');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'eventos', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $eventoPadrao = static function (): array {
        return [
            'titulo' => '',
            'descricao' => '',
            'tipo' => 'culto',
            'data_inicio' => date('Y-m-d\TH:i', strtotime('+1 day')),
            'data_fim' => '',
            'horario' => '',
            'local' => '',
            'responsavel_id' => '0',
            'foto' => '',
            'publicado' => '0',
            'destaque' => '0',
        ];
    };

    $router->get('/admin/modulos/eventos', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'tipo' => (string) $request->query('tipo', ''),
            'publicado' => (string) $request->query('publicado', ''),
            'periodo' => (string) $request->query('periodo', ''),
            'destaque' => (string) $request->query('destaque', ''),
        ];

        return admin_view('admin/eventos/index', [
            'titulo' => 'Eventos',
            'subtitulo' => 'Agenda e publicação de eventos no site institucional.',
            'eventos' => EventoRepository::listar($filtros),
            'estatisticas' => EventoRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'eventos', 'eventos', 'ver');
    }, [new PermissionMiddleware('eventos', 'ver')]);

    $router->get('/admin/modulos/eventos/novo', static function () use ($estadoFormulario, $eventoPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/eventos/form', [
            'titulo' => 'Novo evento',
            'subtitulo' => 'Cadastre um evento e defina sua publicação no site.',
            'evento' => null,
            'responsaveis' => EventoRepository::responsaveis(),
            'valores' => $estado['valores'] === [] ? $eventoPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'eventos', 'eventos', 'criar');
    }, [new PermissionMiddleware('eventos', 'criar')]);

    $router->get('/admin/modulos/eventos/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $evento = EventoRepository::buscar((int) $id);
        if ($evento === null) {
            throw new HttpException(404, 'O evento solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $valores = $estado['valores'] === [] ? EventoRepository::valoresFormulario($evento) : $estado['valores'];
        return admin_view('admin/eventos/form', [
            'titulo' => 'Editar evento',
            'subtitulo' => 'Atualize os dados e a situação de publicação.',
            'evento' => $evento,
            'responsaveis' => EventoRepository::responsaveis(),
            'valores' => $valores,
            'erros' => $estado['erros'],
        ], 'eventos', 'eventos', 'editar');
    }, [new PermissionMiddleware('eventos', 'editar')]);

    $router->post('/admin/modulos/eventos', static function (Request $request) use ($eventoFoto): Response {
        $foto = $eventoFoto($request, 'evento');
        $validacao = Validator::validate($request->post(), EventoRepository::regras());
        if ($foto['erro'] !== null || !$validacao['ok']) {
            if ($foto['caminho'] !== null) {
                Upload::excluir($foto['caminho']);
            }
            if ($foto['erro'] !== null) {
                $validacao['errors']['foto'] = $foto['erro'];
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/eventos/novo');
        }

        $dados = $validacao['data'];
        $dados['foto'] = $foto['caminho'] ?? (string) ($dados['foto'] ?? '');
        if (!Auth::hasPermission('eventos', 'publicar')) {
            $dados['publicado'] = '0';
            $dados['destaque'] = '0';
        }

        try {
            $id = EventoRepository::salvar(null, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/eventos/novo');
        }

        Audit::registrar('criar', 'eventos', 'eventos', $id, null, $dados);
        Session::flash('sucesso', 'Evento cadastrado com sucesso.');
        return redirect('admin/modulos/eventos/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('eventos', 'criar')]);

    $router->post('/admin/modulos/eventos/{id}', static function (Request $request, string $id) use ($eventoFoto): Response {
        $evento = EventoRepository::buscar((int) $id);
        if ($evento === null) {
            throw new HttpException(404, 'O evento solicitado não foi encontrado.');
        }

        $foto = $eventoFoto($request, 'evento-' . (int) $id);
        $validacao = Validator::validate($request->post(), EventoRepository::regras());
        if ($foto['erro'] !== null || !$validacao['ok']) {
            if ($foto['caminho'] !== null) {
                Upload::excluir($foto['caminho']);
            }
            if ($foto['erro'] !== null) {
                $validacao['errors']['foto'] = $foto['erro'];
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/eventos/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $fotoAtual = (string) ($evento['foto'] ?? '');
        if ((string) $request->post('remover_foto', '') === '1') {
            Upload::excluir($fotoAtual);
            $fotoAtual = '';
        }
        if ($foto['caminho'] !== null) {
            Upload::excluir($fotoAtual);
            $fotoAtual = $foto['caminho'];
        }
        $dados['foto'] = $fotoAtual;
        if (!Auth::hasPermission('eventos', 'publicar')) {
            $dados['publicado'] = (string) (int) $evento['publicado'];
            $dados['destaque'] = (string) (int) $evento['destaque'];
        }

        try {
            EventoRepository::salvar((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/eventos/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'eventos', 'eventos', (int) $id, $evento, $dados);
        Session::flash('sucesso', 'Evento atualizado com sucesso.');
        return redirect('admin/modulos/eventos/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('eventos', 'editar')]);

    $router->post('/admin/modulos/eventos/{id}/publicar', static function (string $id): Response {
        $evento = EventoRepository::buscar((int) $id);
        if ($evento === null) {
            throw new HttpException(404, 'O evento solicitado não foi encontrado.');
        }

        $publicado = EventoRepository::alternarPublicacao((int) $id);
        Audit::registrar($publicado === 1 ? 'publicar' : 'despublicar', 'eventos', 'eventos', (int) $id, null, ['publicado' => $publicado]);
        Session::flash('sucesso', $publicado === 1 ? 'Evento publicado no site.' : 'Evento despublicado do site.');
        return redirect('admin/modulos/eventos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('eventos', 'publicar')]);

    $router->post('/admin/modulos/eventos/{id}/destaque', static function (string $id): Response {
        $evento = EventoRepository::buscar((int) $id);
        if ($evento === null) {
            throw new HttpException(404, 'O evento solicitado não foi encontrado.');
        }

        $destaque = EventoRepository::alternarDestaque((int) $id);
        Audit::registrar('destaque', 'eventos', 'eventos', (int) $id, null, ['destaque' => $destaque]);
        Session::flash('sucesso', $destaque === 1 ? 'Evento destacado na home.' : 'Destaque removido.');
        return redirect('admin/modulos/eventos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('eventos', 'publicar')]);

    $router->post('/admin/modulos/eventos/{id}/excluir', static function (string $id): Response {
        $evento = EventoRepository::buscar((int) $id);
        if ($evento === null) {
            throw new HttpException(404, 'O evento solicitado não foi encontrado.');
        }

        EventoRepository::excluir((int) $id);
        Upload::excluir((string) ($evento['foto'] ?? ''));
        Audit::registrar('excluir', 'eventos', 'eventos', (int) $id, $evento, null);
        Session::flash('sucesso', 'Evento excluído com sucesso.');
        return redirect('admin/modulos/eventos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('eventos', 'excluir')]);

    $noticiaImagem = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('imagem');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'noticias', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $noticiaPadrao = static function (): array {
        return [
            'titulo' => '',
            'resumo' => '',
            'conteudo' => '',
            'imagem' => '',
            'autor_id' => '0',
            'publicada' => '0',
        ];
    };

    $router->get('/admin/modulos/noticias', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'publicada' => (string) $request->query('publicada', ''),
            'autor_id' => (string) $request->query('autor_id', ''),
        ];

        return admin_view('admin/noticias/index', [
            'titulo' => 'Notícias',
            'subtitulo' => 'Notícias publicadas no site institucional.',
            'noticias' => NoticiaRepository::listar($filtros),
            'estatisticas' => NoticiaRepository::estatisticas(),
            'autores' => NoticiaRepository::autores(),
            'filtros' => $filtros,
        ], 'noticias', 'noticias', 'ver');
    }, [new PermissionMiddleware('noticias', 'ver')]);

    $router->get('/admin/modulos/noticias/novo', static function () use ($estadoFormulario, $noticiaPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/noticias/form', [
            'titulo' => 'Nova notícia',
            'subtitulo' => 'Cadastre uma notícia e defina sua publicação no site.',
            'noticia' => null,
            'autores' => NoticiaRepository::autores(),
            'valores' => $estado['valores'] === [] ? $noticiaPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'noticias', 'noticias', 'criar');
    }, [new PermissionMiddleware('noticias', 'criar')]);

    $router->get('/admin/modulos/noticias/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $noticia = NoticiaRepository::buscar((int) $id);
        if ($noticia === null) {
            throw new HttpException(404, 'A notícia solicitada não foi encontrada.');
        }

        $estado = $estadoFormulario();
        $valores = $estado['valores'] === [] ? NoticiaRepository::valoresFormulario($noticia) : $estado['valores'];
        return admin_view('admin/noticias/form', [
            'titulo' => 'Editar notícia',
            'subtitulo' => 'Atualize o conteúdo e a situação de publicação.',
            'noticia' => $noticia,
            'autores' => NoticiaRepository::autores(),
            'valores' => $valores,
            'erros' => $estado['erros'],
        ], 'noticias', 'noticias', 'editar');
    }, [new PermissionMiddleware('noticias', 'editar')]);

    $router->post('/admin/modulos/noticias', static function (Request $request) use ($noticiaImagem): Response {
        $imagem = $noticiaImagem($request, 'noticia');
        $validacao = Validator::validate($request->post(), NoticiaRepository::regras());
        if ($imagem['erro'] !== null || !$validacao['ok']) {
            if ($imagem['caminho'] !== null) {
                Upload::excluir($imagem['caminho']);
            }
            if ($imagem['erro'] !== null) {
                $validacao['errors']['imagem'] = $imagem['erro'];
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/noticias/novo');
        }

        $dados = $validacao['data'];
        $dados['imagem'] = $imagem['caminho'] ?? (string) ($dados['imagem'] ?? '');
        if (!Auth::hasPermission('noticias', 'publicar')) {
            $dados['publicada'] = '0';
        }

        try {
            $id = NoticiaRepository::salvar(null, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/noticias/novo');
        }

        Audit::registrar('criar', 'noticias', 'noticias', $id, null, $dados);
        Session::flash('sucesso', 'Notícia cadastrada com sucesso.');
        return redirect('admin/modulos/noticias/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('noticias', 'criar')]);

    $router->post('/admin/modulos/noticias/{id}', static function (Request $request, string $id) use ($noticiaImagem): Response {
        $noticia = NoticiaRepository::buscar((int) $id);
        if ($noticia === null) {
            throw new HttpException(404, 'A notícia solicitada não foi encontrada.');
        }

        $imagem = $noticiaImagem($request, 'noticia-' . (int) $id);
        $validacao = Validator::validate($request->post(), NoticiaRepository::regras());
        if ($imagem['erro'] !== null || !$validacao['ok']) {
            if ($imagem['caminho'] !== null) {
                Upload::excluir($imagem['caminho']);
            }
            if ($imagem['erro'] !== null) {
                $validacao['errors']['imagem'] = $imagem['erro'];
            }
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/noticias/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $imagemAtual = (string) ($noticia['imagem'] ?? '');
        if ((string) $request->post('remover_imagem', '') === '1') {
            Upload::excluir($imagemAtual);
            $imagemAtual = '';
        }
        if ($imagem['caminho'] !== null) {
            Upload::excluir($imagemAtual);
            $imagemAtual = $imagem['caminho'];
        }
        $dados['imagem'] = $imagemAtual;
        if (!Auth::hasPermission('noticias', 'publicar')) {
            $dados['publicada'] = (string) (int) $noticia['publicada'];
        }

        try {
            NoticiaRepository::salvar((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/noticias/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'noticias', 'noticias', (int) $id, $noticia, $dados);
        Session::flash('sucesso', 'Notícia atualizada com sucesso.');
        return redirect('admin/modulos/noticias/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('noticias', 'editar')]);

    $router->post('/admin/modulos/noticias/{id}/publicar', static function (string $id): Response {
        $noticia = NoticiaRepository::buscar((int) $id);
        if ($noticia === null) {
            throw new HttpException(404, 'A notícia solicitada não foi encontrada.');
        }

        $publicada = NoticiaRepository::alternarPublicacao((int) $id);
        Audit::registrar($publicada === 1 ? 'publicar' : 'despublicar', 'noticias', 'noticias', (int) $id, null, ['publicada' => $publicada]);
        Session::flash('sucesso', $publicada === 1 ? 'Notícia publicada no site.' : 'Notícia despublicada do site.');
        return redirect('admin/modulos/noticias');
    }, [new CsrfMiddleware(), new PermissionMiddleware('noticias', 'publicar')]);

    $router->post('/admin/modulos/noticias/{id}/excluir', static function (string $id): Response {
        $noticia = NoticiaRepository::buscar((int) $id);
        if ($noticia === null) {
            throw new HttpException(404, 'A notícia solicitada não foi encontrada.');
        }

        NoticiaRepository::excluir((int) $id);
        Upload::excluir((string) ($noticia['imagem'] ?? ''));
        Audit::registrar('excluir', 'noticias', 'noticias', (int) $id, $noticia, null);
        Session::flash('sucesso', 'Notícia excluída com sucesso.');
        return redirect('admin/modulos/noticias');
    }, [new CsrfMiddleware(), new PermissionMiddleware('noticias', 'excluir')]);

    $galeriaImagem = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('imagem');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'galeria', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $galeriaPadrao = static function (): array {
        return [
            'titulo' => '',
            'descricao' => '',
            'ordem' => (string) GaleriaRepository::proximaOrdem(),
            'ativo' => '1',
            'imagem' => '',
        ];
    };

    $router->get('/admin/modulos/galeria', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'ativo' => (string) $request->query('ativo', ''),
        ];

        return admin_view('admin/galeria/index', [
            'titulo' => 'Galeria',
            'subtitulo' => 'Fotografias da comunidade exibidas no site institucional.',
            'fotos' => GaleriaRepository::listar($filtros),
            'estatisticas' => GaleriaRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'galeria', 'galeria', 'ver');
    }, [new PermissionMiddleware('galeria', 'ver')]);

    $router->get('/admin/modulos/galeria/novo', static function () use ($estadoFormulario, $galeriaPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/galeria/form', [
            'titulo' => 'Adicionar fotografia',
            'subtitulo' => 'Envie uma imagem e defina como ela será exibida.',
            'foto' => null,
            'valores' => $estado['valores'] === [] ? $galeriaPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'galeria', 'galeria', 'criar');
    }, [new PermissionMiddleware('galeria', 'criar')]);

    $router->get('/admin/modulos/galeria/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $foto = GaleriaRepository::buscar((int) $id);
        if ($foto === null) {
            throw new HttpException(404, 'A fotografia solicitada não foi encontrada.');
        }

        $estado = $estadoFormulario();
        $valores = $estado['valores'] === [] ? GaleriaRepository::valoresFormulario($foto) : $estado['valores'];
        return admin_view('admin/galeria/form', [
            'titulo' => 'Editar fotografia',
            'subtitulo' => 'Atualize a legenda, a ordem e a imagem.',
            'foto' => $foto,
            'valores' => $valores,
            'erros' => $estado['erros'],
        ], 'galeria', 'galeria', 'editar');
    }, [new PermissionMiddleware('galeria', 'editar')]);

    $router->post('/admin/modulos/galeria', static function (Request $request) use ($galeriaImagem): Response {
        $imagem = $galeriaImagem($request, 'galeria');
        $validacao = Validator::validate($request->post(), GaleriaRepository::regras());
        $erros = $validacao['errors'];
        if ($imagem['erro'] !== null) {
            $erros['imagem'] = $imagem['erro'];
        }
        if ($imagem['caminho'] === null && $imagem['erro'] === null) {
            $erros['imagem'] = 'Selecione uma imagem para a galeria.';
        }
        if ($erros !== []) {
            if ($imagem['caminho'] !== null) {
                Upload::excluir($imagem['caminho']);
            }
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/galeria/novo');
        }

        $dados = $validacao['data'];
        $dados['imagem'] = (string) $imagem['caminho'];
        try {
            $id = GaleriaRepository::salvar(null, $dados);
        } catch (InvalidArgumentException $exception) {
            Upload::excluir((string) $dados['imagem']);
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/galeria/novo');
        }

        Audit::registrar('criar', 'galeria', 'galeria', $id, null, $dados);
        Session::flash('sucesso', 'Fotografia adicionada com sucesso.');
        return redirect('admin/modulos/galeria/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('galeria', 'criar')]);

    $router->post('/admin/modulos/galeria/{id}', static function (Request $request, string $id) use ($galeriaImagem): Response {
        $foto = GaleriaRepository::buscar((int) $id);
        if ($foto === null) {
            throw new HttpException(404, 'A fotografia solicitada não foi encontrada.');
        }

        $imagem = $galeriaImagem($request, 'galeria-' . (int) $id);
        $validacao = Validator::validate($request->post(), GaleriaRepository::regras());
        $erros = $validacao['errors'];
        if ($imagem['erro'] !== null) {
            $erros['imagem'] = $imagem['erro'];
        }
        if ($erros !== []) {
            if ($imagem['caminho'] !== null) {
                Upload::excluir($imagem['caminho']);
            }
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/galeria/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $imagemAtual = (string) ($foto['imagem'] ?? '');
        if ($imagem['caminho'] !== null) {
            Upload::excluir($imagemAtual);
            $imagemAtual = $imagem['caminho'];
        }
        $dados['imagem'] = $imagemAtual;
        try {
            GaleriaRepository::salvar((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/galeria/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'galeria', 'galeria', (int) $id, $foto, $dados);
        Session::flash('sucesso', 'Fotografia atualizada com sucesso.');
        return redirect('admin/modulos/galeria/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('galeria', 'editar')]);

    $router->post('/admin/modulos/galeria/{id}/ativo', static function (string $id): Response {
        $foto = GaleriaRepository::buscar((int) $id);
        if ($foto === null) {
            throw new HttpException(404, 'A fotografia solicitada não foi encontrada.');
        }

        $ativo = GaleriaRepository::alternarAtivo((int) $id);
        Audit::registrar($ativo === 1 ? 'ativar' : 'desativar', 'galeria', 'galeria', (int) $id, null, ['ativo' => $ativo]);
        Session::flash('sucesso', $ativo === 1 ? 'Fotografia ativada no site.' : 'Fotografia desativada do site.');
        return redirect('admin/modulos/galeria');
    }, [new CsrfMiddleware(), new PermissionMiddleware('galeria', 'editar')]);

    $router->post('/admin/modulos/galeria/{id}/excluir', static function (string $id): Response {
        $foto = GaleriaRepository::buscar((int) $id);
        if ($foto === null) {
            throw new HttpException(404, 'A fotografia solicitada não foi encontrada.');
        }

        GaleriaRepository::excluir((int) $id);
        Upload::excluir((string) ($foto['imagem'] ?? ''));
        Audit::registrar('excluir', 'galeria', 'galeria', (int) $id, $foto, null);
        Session::flash('sucesso', 'Fotografia excluída com sucesso.');
        return redirect('admin/modulos/galeria');
    }, [new CsrfMiddleware(), new PermissionMiddleware('galeria', 'excluir')]);

    $programacaoPadrao = static function (): array {
        return [
            'congregacao_id' => '',
            'dia_semana' => 'domingo',
            'horario' => '09:00',
            'tipo' => '',
            'descricao' => '',
            'ordem' => (string) ProgramacaoRepository::proximaOrdem(),
            'ativo' => '1',
        ];
    };

    $programacaoErroCampo = static function (InvalidArgumentException $exception): string {
        $mensagem = $exception->getMessage();
        if (str_contains($mensagem, 'Congregação')) {
            return 'congregacao_id';
        }
        if (str_contains($mensagem, 'dia da semana')) {
            return 'dia_semana';
        }
        return 'horario';
    };

    $router->get('/admin/modulos/programacao', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'dia_semana' => (string) $request->query('dia_semana', ''),
            'ativo' => (string) $request->query('ativo', ''),
            'congregacao_id' => (string) $request->query('congregacao_id', ''),
        ];

        return admin_view('admin/programacao/index', [
            'titulo' => 'Programação',
            'subtitulo' => 'Gerencie os cultos e horários exibidos no site.',
            'horarios' => ProgramacaoRepository::listar($filtros),
            'estatisticas' => ProgramacaoRepository::estatisticas(),
            'filtros' => $filtros,
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'programacao', 'programacao', 'ver');
    }, [new PermissionMiddleware('programacao', 'ver')]);

    $router->get('/admin/modulos/programacao/novo', static function () use ($estadoFormulario, $programacaoPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/programacao/form', [
            'titulo' => 'Novo horário de culto',
            'subtitulo' => 'Cadastre um encontro na programação da igreja.',
            'culto' => null,
            'valores' => $estado['valores'] === [] ? $programacaoPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
            'congregacoes' => StructureRepository::congregacoes(),
        ], 'programacao', 'programacao', 'editar');
    }, [new PermissionMiddleware('programacao', 'editar')]);

    $router->get('/admin/modulos/programacao/{id}/editar', static function (string $id) use ($estadoFormulario, $programacaoPadrao): Response {
        $culto = ProgramacaoRepository::buscar((int) $id);
        if ($culto === null) {
            throw new HttpException(404, 'O horário solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        $congregacaoAtual = (int) ($culto['congregacao_id'] ?? 0);
        return admin_view('admin/programacao/form', [
            'titulo' => 'Editar horário de culto',
            'subtitulo' => 'Atualize os dados deste encontro.',
            'culto' => $culto,
            'valores' => $estado['valores'] === [] ? ProgramacaoRepository::valoresFormulario($culto) : $estado['valores'],
            'erros' => $estado['erros'],
            'congregacoes' => StructureRepository::congregacoes($congregacaoAtual > 0 ? $congregacaoAtual : null),
        ], 'programacao', 'programacao', 'editar');
    }, [new PermissionMiddleware('programacao', 'editar')]);

    $router->post('/admin/modulos/programacao', static function (Request $request) use ($programacaoErroCampo): Response {
        $validacao = Validator::validate($request->post(), ProgramacaoRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/programacao/novo');
        }

        $dados = $validacao['data'];
        try {
            $id = ProgramacaoRepository::salvar(null, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('form_erros', [$programacaoErroCampo($exception) => $exception->getMessage()]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/programacao/novo');
        }

        Audit::registrar('criar', 'programacao', 'programacao', $id, null, $dados);
        Session::flash('sucesso', 'Horário de culto adicionado com sucesso.');
        return redirect('admin/modulos/programacao/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('programacao', 'editar')]);

    $router->post('/admin/modulos/programacao/{id}', static function (Request $request, string $id) use ($programacaoErroCampo): Response {
        $culto = ProgramacaoRepository::buscar((int) $id);
        if ($culto === null) {
            throw new HttpException(404, 'O horário solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), ProgramacaoRepository::regras());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/programacao/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        try {
            ProgramacaoRepository::salvar((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('form_erros', [$programacaoErroCampo($exception) => $exception->getMessage()]);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/programacao/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'programacao', 'programacao', (int) $id, $culto, $dados);
        Session::flash('sucesso', 'Horário de culto atualizado com sucesso.');
        return redirect('admin/modulos/programacao/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('programacao', 'editar')]);

    $router->post('/admin/modulos/programacao/{id}/ativo', static function (string $id): Response {
        $culto = ProgramacaoRepository::buscar((int) $id);
        if ($culto === null) {
            throw new HttpException(404, 'O horário solicitado não foi encontrado.');
        }

        $ativo = ProgramacaoRepository::alternarAtivo((int) $id);
        Audit::registrar($ativo === 1 ? 'ativar' : 'desativar', 'programacao', 'programacao', (int) $id, null, ['ativo' => $ativo]);
        Session::flash('sucesso', $ativo === 1 ? 'Horário ativado no site.' : 'Horário desativado do site.');
        return redirect('admin/modulos/programacao');
    }, [new CsrfMiddleware(), new PermissionMiddleware('programacao', 'editar')]);

    $router->post('/admin/modulos/programacao/{id}/excluir', static function (string $id): Response {
        $culto = ProgramacaoRepository::buscar((int) $id);
        if ($culto === null) {
            throw new HttpException(404, 'O horário solicitado não foi encontrado.');
        }

        ProgramacaoRepository::excluir((int) $id);
        Audit::registrar('excluir', 'programacao', 'programacao', (int) $id, $culto, null);
        Session::flash('sucesso', 'Horário de culto excluído com sucesso.');
        return redirect('admin/modulos/programacao');
    }, [new CsrfMiddleware(), new PermissionMiddleware('programacao', 'editar')]);

    $palavraFoto = static function (Request $request, string $prefixo): array {
        $arquivo = $request->file('foto');
        if ($arquivo === null || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['caminho' => null, 'erro' => null];
        }

        try {
            return ['caminho' => Upload::image($arquivo, 'palavra_pastoral', $prefixo), 'erro' => null];
        } catch (Throwable $exception) {
            return ['caminho' => null, 'erro' => $exception->getMessage()];
        }
    };

    $palavraPadrao = static function (): array {
        return [
            'titulo' => '',
            'texto' => '',
            'autor_id' => (string) Auth::id(),
            'foto' => '',
            'publicada' => '1',
        ];
    };

    $versiculoPadrao = static function (): array {
        return [
            'referencia' => '',
            'texto' => '',
            'ordem' => (string) PalavraPastoralRepository::proximaOrdemVersiculo(),
            'ativo' => '1',
        ];
    };

    $router->get('/admin/modulos/palavra_pastoral', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'publicada' => (string) $request->query('publicada', ''),
        ];

        return admin_view('admin/palavra_pastoral/index', [
            'titulo' => 'Palavra Pastoral',
            'subtitulo' => 'Gerencie mensagens e versículos em destaque no site.',
            'mensagens' => PalavraPastoralRepository::listar($filtros),
            'estatisticas' => PalavraPastoralRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'palavra_pastoral', 'palavra_pastoral', 'ver');
    }, [new PermissionMiddleware('palavra_pastoral', 'ver')]);

    $router->get('/admin/modulos/palavra_pastoral/novo', static function () use ($estadoFormulario, $palavraPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/palavra_pastoral/form', [
            'titulo' => 'Nova mensagem pastoral',
            'subtitulo' => 'Publique uma mensagem na página inicial do site.',
            'mensagem' => null,
            'valores' => $estado['valores'] === [] ? $palavraPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
            'autores' => PalavraPastoralRepository::autores(),
        ], 'palavra_pastoral', 'palavra_pastoral', 'editar');
    }, [new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->get('/admin/modulos/palavra_pastoral/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $mensagem = PalavraPastoralRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        $estado = $estadoFormulario();
        return admin_view('admin/palavra_pastoral/form', [
            'titulo' => 'Editar mensagem pastoral',
            'subtitulo' => 'Atualize o conteúdo e a publicação da mensagem.',
            'mensagem' => $mensagem,
            'valores' => $estado['valores'] === [] ? PalavraPastoralRepository::valoresFormulario($mensagem) : $estado['valores'],
            'erros' => $estado['erros'],
            'autores' => PalavraPastoralRepository::autores(),
        ], 'palavra_pastoral', 'palavra_pastoral', 'editar');
    }, [new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->get('/admin/modulos/palavra_pastoral/versiculos', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'ativo' => (string) $request->query('ativo', ''),
        ];

        return admin_view('admin/palavra_pastoral/versiculos/index', [
            'titulo' => 'Versículos em destaque',
            'subtitulo' => 'Gerencie o versículo exibido nas páginas do site.',
            'versiculos' => PalavraPastoralRepository::listarVersiculos($filtros),
            'estatisticas' => PalavraPastoralRepository::estatisticasVersiculos(),
            'filtros' => $filtros,
        ], 'palavra_pastoral', 'palavra_pastoral', 'ver');
    }, [new PermissionMiddleware('palavra_pastoral', 'ver')]);

    $router->get('/admin/modulos/palavra_pastoral/versiculos/novo', static function () use ($estadoFormulario, $versiculoPadrao): Response {
        $estado = $estadoFormulario();
        return admin_view('admin/palavra_pastoral/versiculos/form', [
            'titulo' => 'Novo versículo',
            'subtitulo' => 'Cadastre um versículo para destaque no site.',
            'versiculo' => null,
            'valores' => $estado['valores'] === [] ? $versiculoPadrao() : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'palavra_pastoral', 'palavra_pastoral', 'editar');
    }, [new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->get('/admin/modulos/palavra_pastoral/versiculos/{id}/editar', static function (string $id) use ($estadoFormulario): Response {
        $versiculo = PalavraPastoralRepository::buscarVersiculo((int) $id);
        if ($versiculo === null) {
            throw new HttpException(404, 'O versículo solicitado não foi encontrado.');
        }

        $estado = $estadoFormulario();
        return admin_view('admin/palavra_pastoral/versiculos/form', [
            'titulo' => 'Editar versículo',
            'subtitulo' => 'Atualize a referência, o texto e a ordem de exibição.',
            'versiculo' => $versiculo,
            'valores' => $estado['valores'] === [] ? PalavraPastoralRepository::valoresFormularioVersiculo($versiculo) : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'palavra_pastoral', 'palavra_pastoral', 'editar');
    }, [new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral', static function (Request $request) use ($palavraFoto): Response {
        $foto = $palavraFoto($request, 'palavra');
        $validacao = Validator::validate($request->post(), PalavraPastoralRepository::regras());
        $erros = $validacao['errors'];
        if ($foto['erro'] !== null) {
            $erros['foto'] = $foto['erro'];
        }
        if ($erros !== []) {
            if ($foto['caminho'] !== null) {
                Upload::excluir($foto['caminho']);
            }
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/novo');
        }

        $dados = $validacao['data'];
        $dados['foto'] = $foto['caminho'] === null ? (string) $request->post('foto', '') : $foto['caminho'];
        try {
            $id = PalavraPastoralRepository::salvar(null, $dados);
        } catch (InvalidArgumentException $exception) {
            if ($foto['caminho'] !== null) {
                Upload::excluir($foto['caminho']);
            }
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/novo');
        }

        Audit::registrar('criar', 'palavra_pastoral', 'palavra_pastoral', $id, null, $dados);
        Session::flash('sucesso', 'Mensagem pastoral cadastrada com sucesso.');
        return redirect('admin/modulos/palavra_pastoral/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/versiculos', static function (Request $request): Response {
        $validacao = Validator::validate($request->post(), PalavraPastoralRepository::regrasVersiculo());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/versiculos/novo');
        }

        $dados = $validacao['data'];
        try {
            $id = PalavraPastoralRepository::salvarVersiculo(null, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/versiculos/novo');
        }

        Audit::registrar('criar', 'palavra_pastoral', 'versiculos_destaque', $id, null, $dados);
        Session::flash('sucesso', 'Versículo cadastrado com sucesso.');
        return redirect('admin/modulos/palavra_pastoral/versiculos/' . $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/versiculos/{id}', static function (Request $request, string $id): Response {
        $versiculo = PalavraPastoralRepository::buscarVersiculo((int) $id);
        if ($versiculo === null) {
            throw new HttpException(404, 'O versículo solicitado não foi encontrado.');
        }

        $validacao = Validator::validate($request->post(), PalavraPastoralRepository::regrasVersiculo());
        if (!$validacao['ok']) {
            Session::flash('form_erros', $validacao['errors']);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/versiculos/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        try {
            PalavraPastoralRepository::salvarVersiculo((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/versiculos/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'palavra_pastoral', 'versiculos_destaque', (int) $id, $versiculo, $dados);
        Session::flash('sucesso', 'Versículo atualizado com sucesso.');
        return redirect('admin/modulos/palavra_pastoral/versiculos/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/versiculos/{id}/ativo', static function (string $id): Response {
        $versiculo = PalavraPastoralRepository::buscarVersiculo((int) $id);
        if ($versiculo === null) {
            throw new HttpException(404, 'O versículo solicitado não foi encontrado.');
        }

        $ativo = PalavraPastoralRepository::alternarVersiculoAtivo((int) $id);
        Audit::registrar($ativo === 1 ? 'ativar' : 'desativar', 'palavra_pastoral', 'versiculos_destaque', (int) $id, null, ['ativo' => $ativo]);
        Session::flash('sucesso', $ativo === 1 ? 'Versículo ativado no site.' : 'Versículo desativado do site.');
        return redirect('admin/modulos/palavra_pastoral/versiculos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/versiculos/{id}/excluir', static function (string $id): Response {
        $versiculo = PalavraPastoralRepository::buscarVersiculo((int) $id);
        if ($versiculo === null) {
            throw new HttpException(404, 'O versículo solicitado não foi encontrado.');
        }

        PalavraPastoralRepository::excluirVersiculo((int) $id);
        Audit::registrar('excluir', 'palavra_pastoral', 'versiculos_destaque', (int) $id, $versiculo, null);
        Session::flash('sucesso', 'Versículo excluído com sucesso.');
        return redirect('admin/modulos/palavra_pastoral/versiculos');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/{id}', static function (Request $request, string $id) use ($palavraFoto): Response {
        $mensagem = PalavraPastoralRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        $foto = $palavraFoto($request, 'palavra-' . (int) $id);
        $validacao = Validator::validate($request->post(), PalavraPastoralRepository::regras());
        $erros = $validacao['errors'];
        if ($foto['erro'] !== null) {
            $erros['foto'] = $foto['erro'];
        }
        if ($erros !== []) {
            if ($foto['caminho'] !== null) {
                Upload::excluir($foto['caminho']);
            }
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/' . (int) $id . '/editar');
        }

        $dados = $validacao['data'];
        $fotoAtual = (string) ($mensagem['foto'] ?? '');
        if ($foto['caminho'] !== null || $request->post('remover_foto', '') === '1') {
            Upload::excluir($fotoAtual);
            $fotoAtual = $foto['caminho'] ?? '';
        }
        $dados['foto'] = $fotoAtual;
        try {
            PalavraPastoralRepository::salvar((int) $id, $dados);
        } catch (InvalidArgumentException $exception) {
            Session::flash('erro', $exception->getMessage());
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/palavra_pastoral/' . (int) $id . '/editar');
        }

        Audit::registrar('editar', 'palavra_pastoral', 'palavra_pastoral', (int) $id, $mensagem, $dados);
        Session::flash('sucesso', 'Mensagem pastoral atualizada com sucesso.');
        return redirect('admin/modulos/palavra_pastoral/' . (int) $id . '/editar');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/{id}/publicar', static function (string $id): Response {
        $mensagem = PalavraPastoralRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        $publicada = PalavraPastoralRepository::alternarPublicacao((int) $id);
        Audit::registrar($publicada === 1 ? 'publicar' : 'despublicar', 'palavra_pastoral', 'palavra_pastoral', (int) $id, null, ['publicada' => $publicada]);
        Session::flash('sucesso', $publicada === 1 ? 'Mensagem publicada no site.' : 'Mensagem despublicada do site.');
        return redirect('admin/modulos/palavra_pastoral');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->post('/admin/modulos/palavra_pastoral/{id}/excluir', static function (string $id): Response {
        $mensagem = PalavraPastoralRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        PalavraPastoralRepository::excluir((int) $id);
        Upload::excluir((string) ($mensagem['foto'] ?? ''));
        Audit::registrar('excluir', 'palavra_pastoral', 'palavra_pastoral', (int) $id, $mensagem, null);
        Session::flash('sucesso', 'Mensagem pastoral excluída com sucesso.');
        return redirect('admin/modulos/palavra_pastoral');
    }, [new CsrfMiddleware(), new PermissionMiddleware('palavra_pastoral', 'editar')]);

    $router->get('/admin/modulos/contato', static function (Request $request): Response {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'situacao' => (string) $request->query('situacao', ''),
        ];

        return admin_view('admin/contato/index', [
            'titulo' => 'Mensagens',
            'subtitulo' => 'Acompanhe as mensagens recebidas pelo formulário de contato.',
            'mensagens' => ContatoRepository::listar($filtros),
            'estatisticas' => ContatoRepository::estatisticas(),
            'filtros' => $filtros,
        ], 'contato', 'contato', 'ver');
    }, [new PermissionMiddleware('contato', 'ver')]);

    $router->get('/admin/modulos/contato/{id}', static function (string $id): Response {
        $mensagem = ContatoRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        return admin_view('admin/contato/detalhe', [
            'titulo' => 'Detalhes da mensagem',
            'subtitulo' => 'Leia a mensagem e registre o andamento do atendimento.',
            'mensagem' => $mensagem,
        ], 'contato', 'contato', 'ver');
    }, [new PermissionMiddleware('contato', 'ver')]);

    $router->post('/admin/modulos/contato/{id}/lida', static function (string $id): Response {
        $mensagem = ContatoRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        if ((int) $mensagem['lida'] === 0) {
            ContatoRepository::marcarLida((int) $id);
            Audit::registrar('marcar_lida', 'contato', 'contato_mensagens', (int) $id, null, ['lida' => 1]);
        }

        Session::flash('sucesso', 'Mensagem marcada como lida.');
        return redirect('admin/modulos/contato/' . (int) $id);
    }, [new CsrfMiddleware(), new PermissionMiddleware('contato', 'responder')]);

    $router->post('/admin/modulos/contato/{id}/respondida', static function (string $id): Response {
        $mensagem = ContatoRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        ContatoRepository::marcarRespondida((int) $id);
        Audit::registrar('responder', 'contato', 'contato_mensagens', (int) $id, null, ['lida' => 1, 'respondida_em' => date('Y-m-d H:i:s')]);
        Session::flash('sucesso', 'Mensagem marcada como respondida.');
        return redirect('admin/modulos/contato/' . (int) $id);
    }, [new CsrfMiddleware(), new PermissionMiddleware('contato', 'responder')]);

    $router->post('/admin/modulos/contato/{id}/resposta/desmarcar', static function (string $id): Response {
        $mensagem = ContatoRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        if ($mensagem['respondida_em'] !== null) {
            ContatoRepository::desmarcarRespondida((int) $id);
            Audit::registrar('desmarcar_resposta', 'contato', 'contato_mensagens', (int) $id, null, ['respondida_em' => null]);
        }

        Session::flash('sucesso', 'Resposta desmarcada.');
        return redirect('admin/modulos/contato/' . (int) $id);
    }, [new CsrfMiddleware(), new PermissionMiddleware('contato', 'responder')]);

    $router->post('/admin/modulos/contato/{id}/excluir', static function (string $id): Response {
        $mensagem = ContatoRepository::buscar((int) $id);
        if ($mensagem === null) {
            throw new HttpException(404, 'A mensagem solicitada não foi encontrada.');
        }

        ContatoRepository::excluir((int) $id);
        Audit::registrar('excluir', 'contato', 'contato_mensagens', (int) $id, $mensagem, null);
        Session::flash('sucesso', 'Mensagem excluída com sucesso.');
        return redirect('admin/modulos/contato');
    }, [new CsrfMiddleware(), new PermissionMiddleware('contato', 'responder')]);

    $filtrosRelatorios = static function (Request $request): array {
        return RelatorioRepository::normalizarFiltros([
            'competencia' => (string) $request->query('competencia', ''),
            'congregacao_id' => (string) $request->query('congregacao_id', ''),
            'modulo' => (string) $request->query('modulo', ''),
        ]);
    };

    $relatorioCsv = static function (array $dados): string {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Seção', 'Indicador', 'Valor'], ';', '"', '\\');
        foreach ($dados['secoes'] as $secao => $itens) {
            foreach ($itens as $rotulo => $valor) {
                fputcsv($handle, [$secao, $rotulo, $valor], ';', '"', '\\');
            }
        }
        rewind($handle);
        $conteudo = stream_get_contents($handle);
        fclose($handle);

        return $conteudo === false ? '' : $conteudo;
    };

    $router->get('/admin/modulos/relatorios', static function (Request $request) use ($filtrosRelatorios): Response {
        $filtros = $filtrosRelatorios($request);
        $dados = RelatorioRepository::dados($filtros);

        return admin_view('admin/relatorios/index', [
            'titulo' => 'Relatórios',
            'subtitulo' => 'Consulte indicadores consolidados da operação.',
            'filtros' => $dados['filtros'],
            'secoes' => $dados['secoes'],
            'categorias' => $dados['categorias'],
            'modulosAuditoria' => $dados['modulos'],
            'congregacoes' => RelatorioRepository::congregacoes(),
            'competencias' => RelatorioRepository::competencias(),
            'modulosDisponiveis' => RelatorioRepository::modulos(),
        ], 'relatorios', 'relatorios', 'ver');
    }, [new PermissionMiddleware('relatorios', 'ver')]);

    $router->get('/admin/modulos/relatorios/exportar', static function (Request $request) use ($filtrosRelatorios, $relatorioCsv): Response {
        $filtros = $filtrosRelatorios($request);
        $dados = RelatorioRepository::dados($filtros);
        Audit::registrar('exportar', 'relatorios', 'relatorio', null, null, $filtros);

        return Response::html($relatorioCsv($dados))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="relatorio-operacional-' . date('Y-m-d') . '.csv"');
    }, [new PermissionMiddleware('relatorios', 'exportar')]);

    $router->get('/admin/modulos/relatorios/imprimir', static function (Request $request) use ($filtrosRelatorios): Response {
        $filtros = $filtrosRelatorios($request);
        $dados = RelatorioRepository::dados($filtros);
        Audit::registrar('imprimir', 'relatorios', 'relatorio', null, null, $filtros);

        return Response::html(View::render('admin/relatorios/relatorio_print', [
            'titulo' => 'Relatório operacional',
            'filtros' => $dados['filtros'],
            'secoes' => $dados['secoes'],
            'categorias' => $dados['categorias'],
            'modulosAuditoria' => $dados['modulos'],
            'congregacoes' => RelatorioRepository::congregacoes(),
        ], 'layouts/print'));
    }, [new PermissionMiddleware('relatorios', 'imprimir')]);

    $filtrosAuditoria = static function (Request $request): array {
        return AuditoriaRepository::filtros($request->query);
    };

    $auditoriaCsv = static function (array $registros): string {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['ID', 'Data e hora', 'Usuário', 'Ação', 'Módulo', 'Entidade', 'Entidade ID', 'IP', 'Dados anteriores', 'Dados novos'], ';', '"', '\\');
        foreach ($registros as $registro) {
            fputcsv($handle, [
                (int) $registro['id'],
                (string) $registro['created_at'],
                (string) ($registro['usuario_nome'] ?? 'Sistema'),
                (string) $registro['acao'],
                (string) $registro['modulo'],
                (string) ($registro['entidade'] ?? ''),
                (string) ($registro['entidade_id'] ?? ''),
                (string) ($registro['ip'] ?? ''),
                AuditoriaRepository::jsonSeguro((string) ($registro['dados_anteriores'] ?? '')),
                AuditoriaRepository::jsonSeguro((string) ($registro['dados_novos'] ?? '')),
            ], ';', '"', '\\');
        }
        rewind($handle);
        $conteudo = stream_get_contents($handle);
        fclose($handle);

        return $conteudo === false ? '' : $conteudo;
    };

    $router->get('/admin/modulos/auditoria', static function (Request $request) use ($filtrosAuditoria): Response {
        $filtros = $filtrosAuditoria($request);

        return admin_view('admin/auditoria/index', [
            'titulo' => 'Auditoria',
            'subtitulo' => 'Trilha de ações realizadas no sistema.',
            'filtros' => $filtros,
            'registros' => AuditoriaRepository::listar($filtros),
            'estatisticas' => AuditoriaRepository::estatisticas($filtros),
            'acoes' => AuditoriaRepository::acoes(),
            'modulosAuditoria' => AuditoriaRepository::modulos(),
            'entidades' => AuditoriaRepository::entidades(),
            'usuarios' => AuditoriaRepository::usuarios(),
        ], 'auditoria', 'auditoria', 'ver');
    }, [new PermissionMiddleware('auditoria', 'ver')]);

    $router->get('/admin/modulos/auditoria/detalhe/{id}', static function (Request $request, string $id) use ($filtrosAuditoria): Response {
        $registro = AuditoriaRepository::buscar((int) $id);
        if ($registro === null) {
            Session::flash('erro', 'Registro de auditoria não encontrado.');
            return redirect('admin/modulos/auditoria');
        }

        return admin_view('admin/auditoria/detalhe', [
            'titulo' => 'Detalhe da auditoria',
            'subtitulo' => 'Registro #' . (int) $registro['id'],
            'registro' => $registro,
            'filtros' => $filtrosAuditoria($request),
        ], 'auditoria', 'auditoria', 'ver');
    }, [new PermissionMiddleware('auditoria', 'ver')]);

    $router->get('/admin/modulos/auditoria/exportar', static function (Request $request) use ($filtrosAuditoria, $auditoriaCsv): Response {
        $filtros = $filtrosAuditoria($request);
        $registros = AuditoriaRepository::listar($filtros);
        Audit::registrar('exportar', 'auditoria', 'trilha', null, null, $filtros);

        return Response::html($auditoriaCsv($registros))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="auditoria-' . date('Y-m-d') . '.csv"');
    }, [new PermissionMiddleware('auditoria', 'ver')]);

    $router->get('/admin/modulos/auditoria/imprimir', static function (Request $request) use ($filtrosAuditoria): Response {
        $filtros = $filtrosAuditoria($request);
        $registros = AuditoriaRepository::listar($filtros);
        $estatisticas = AuditoriaRepository::estatisticas($filtros);
        Audit::registrar('imprimir', 'auditoria', 'trilha', null, null, $filtros);

        return Response::html(View::render('admin/auditoria/auditoria_print', [
            'titulo' => 'Trilha de auditoria',
            'filtros' => $filtros,
            'registros' => $registros,
            'estatisticas' => $estatisticas,
            'usuarios' => AuditoriaRepository::usuarios(),
        ], 'layouts/print'));
    }, [new PermissionMiddleware('auditoria', 'ver')]);

    $filtrosLgpd = static function (Request $request): array {
        return LgpdRepository::filtros($request->query);
    };

    $lgpdVoltar = static function (Request $request): string {
        $query = array_filter([
            'titular_tipo' => (string) $request->query('titular_tipo', ''),
            'finalidade' => (string) $request->query('finalidade', ''),
            'consentido' => (string) $request->query('consentido', ''),
            'de' => (string) $request->query('de', ''),
            'ate' => (string) $request->query('ate', ''),
            'q' => (string) $request->query('q', ''),
            'limite' => (string) $request->query('limite', ''),
        ], static fn (string $item): bool => $item !== '' && $item !== '0');

        return 'admin/modulos/lgpd' . ($query === [] ? '' : '?' . http_build_query($query));
    };

    $lgpdPadrao = static function (?array $registro): array {
        if ($registro !== null) {
            return [
                'titular_tipo' => (string) $registro['titular_tipo'],
                'titular_id' => (string) $registro['titular_id'],
                'finalidade' => (string) $registro['finalidade'],
                'consentido' => (int) $registro['consentido'] === 1 ? '1' : '0',
                'data_consentimento' => (string) $registro['data_consentimento'],
                'ip' => (string) ($registro['ip'] ?? ''),
            ];
        }

        return [
            'titular_tipo' => 'membro',
            'titular_id' => '',
            'finalidade' => '',
            'consentido' => '1',
            'data_consentimento' => date('Y-m-d H:i:s'),
            'ip' => '',
        ];
    };

    $lgpdValidar = static function (Request $request): array {
        $post = $request->post();
        $titulares = [];
        foreach (['membro', 'obreiro', 'contato'] as $tipo) {
            $titulares[$tipo] = array_map('intval', array_column(LgpdRepository::titulares($tipo), 'id'));
        }

        $tipo = (string) ($post['titular_tipo'] ?? '');
        $titularId = (int) ($post['titular_id'] ?? 0);
        $finalidade = trim((string) ($post['finalidade'] ?? ''));
        $ip = trim((string) ($post['ip'] ?? ''));
        $erros = [];

        if (!in_array($tipo, ['membro', 'obreiro', 'contato'], true)) {
            $erros['titular_tipo'] = 'Selecione um tipo de titular válido.';
        }
        if ($titularId <= 0 || !in_array($titularId, $titulares[$tipo] ?? [], true)) {
            $erros['titular_id'] = 'Selecione um titular válido para o tipo informado.';
        }
        if (mb_strlen($finalidade) < 3 || mb_strlen($finalidade) > 180) {
            $erros['finalidade'] = 'Informe uma finalidade entre 3 e 180 caracteres.';
        }
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $erros['ip'] = 'Informe um IP válido.';
        }

        $dataBruta = trim((string) ($post['data_consentimento'] ?? ''));
        $dataConsentimento = date('Y-m-d H:i:s');
        if ($dataBruta !== '') {
            $data = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $dataBruta);
            if ($data === false || $data->format('Y-m-d\TH:i') !== $dataBruta) {
                $erros['data_consentimento'] = 'Informe uma data e hora válidas.';
            } else {
                $dataConsentimento = $data->format('Y-m-d H:i:s');
            }
        }

        $dados = [
            'titular_tipo' => $tipo,
            'titular_id' => $titularId,
            'finalidade' => $finalidade,
            'consentido' => (string) ($post['consentido'] ?? '1') === '0' ? 0 : 1,
            'data_consentimento' => $dataConsentimento,
            'ip' => $ip === '' ? $request->ip : $ip,
        ];

        return [$dados, $erros];
    };

    $lgpdCsv = static function (array $registros): string {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['ID', 'Data e hora', 'Titular', 'Tipo', 'Finalidade', 'Situação', 'IP'], ';', '"', '\\');
        foreach ($registros as $registro) {
            fputcsv($handle, [
                (int) $registro['id'],
                (string) $registro['data_consentimento'],
                (string) ($registro['titular_nome'] ?? 'Titular #' . (int) $registro['titular_id']),
                (string) $registro['titular_tipo'],
                (string) $registro['finalidade'],
                (int) $registro['consentido'] === 1 ? 'Consentido' : 'Não consentido',
                (string) ($registro['ip'] ?? ''),
            ], ';', '"', '\\');
        }
        rewind($handle);
        $conteudo = stream_get_contents($handle);
        fclose($handle);

        return $conteudo === false ? '' : $conteudo;
    };

    $router->get('/admin/modulos/lgpd', static function (Request $request) use ($filtrosLgpd): Response {
        $filtros = $filtrosLgpd($request);
        $configuracoes = ConfiguracaoRepository::valores(ConfiguracaoRepository::listar());

        return admin_view('admin/lgpd/index', [
            'titulo' => 'LGPD',
            'subtitulo' => 'Consentimentos e retenção de dados.',
            'filtros' => $filtros,
            'registrosLgpd' => LgpdRepository::listar($filtros),
            'lgpdEstatisticas' => LgpdRepository::estatisticas($filtros),
            'finalidadesLgpd' => LgpdRepository::finalidades(),
            'auditoriaResumo' => LgpdRepository::auditoriaResumo(),
            'retencao' => $configuracoes,
        ], 'lgpd', 'lgpd', 'ver');
    }, [new PermissionMiddleware('lgpd', 'ver')]);

    $router->get('/admin/modulos/lgpd/novo', static function (Request $request) use ($estadoFormulario, $lgpdPadrao, $lgpdVoltar): Response {
        $estado = $estadoFormulario();

        return admin_view('admin/lgpd/form', [
            'titulo' => 'Novo consentimento',
            'subtitulo' => 'Registre o aceite ou a recusa de um titular.',
            'registro' => null,
            'valores' => $estado['valores'] === [] ? $lgpdPadrao(null) : $estado['valores'],
            'erros' => $estado['erros'],
            'titulares' => [
                'membro' => LgpdRepository::titulares('membro'),
                'obreiro' => LgpdRepository::titulares('obreiro'),
                'contato' => LgpdRepository::titulares('contato'),
            ],
            'acao' => url('admin/modulos/lgpd'),
            'voltar' => url($lgpdVoltar($request)),
        ], 'lgpd', 'lgpd', 'editar');
    }, [new PermissionMiddleware('lgpd', 'editar')]);

    $router->get('/admin/modulos/lgpd/exportar', static function (Request $request) use ($filtrosLgpd, $lgpdCsv): Response {
        $filtros = $filtrosLgpd($request);
        $registros = LgpdRepository::listar($filtros);
        Audit::registrar('exportar', 'lgpd', 'consentimento', null, null, $filtros);

        return Response::html($lgpdCsv($registros))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="lgpd-consentimentos-' . date('Y-m-d') . '.csv"');
    }, [new PermissionMiddleware('lgpd', 'ver')]);

    $router->get('/admin/modulos/lgpd/imprimir', static function (Request $request) use ($filtrosLgpd): Response {
        $filtros = $filtrosLgpd($request);
        $configuracoes = ConfiguracaoRepository::valores(ConfiguracaoRepository::listar());
        Audit::registrar('imprimir', 'lgpd', 'consentimento', null, null, $filtros);

        return Response::html(View::render('admin/lgpd/lgpd_print', [
            'titulo' => 'Consentimentos LGPD',
            'filtros' => $filtros,
            'registrosLgpd' => LgpdRepository::listar($filtros),
            'lgpdEstatisticas' => LgpdRepository::estatisticas($filtros),
            'retencao' => $configuracoes,
        ], 'layouts/print'));
    }, [new PermissionMiddleware('lgpd', 'ver')]);

    $router->get('/admin/modulos/lgpd/{id}/editar', static function (Request $request, string $id) use ($estadoFormulario, $lgpdPadrao, $lgpdVoltar): Response {
        $registro = LgpdRepository::buscar((int) $id);
        if ($registro === null) {
            Session::flash('erro', 'Consentimento não encontrado.');
            return redirect($lgpdVoltar($request));
        }
        $estado = $estadoFormulario();

        return admin_view('admin/lgpd/form', [
            'titulo' => 'Editar consentimento',
            'subtitulo' => 'Registro #' . (int) $registro['id'],
            'registro' => $registro,
            'valores' => $estado['valores'] === [] ? $lgpdPadrao($registro) : $estado['valores'],
            'erros' => $estado['erros'],
            'titulares' => [
                'membro' => LgpdRepository::titulares('membro'),
                'obreiro' => LgpdRepository::titulares('obreiro'),
                'contato' => LgpdRepository::titulares('contato'),
            ],
            'acao' => url('admin/modulos/lgpd/' . (int) $registro['id']),
            'voltar' => url($lgpdVoltar($request)),
        ], 'lgpd', 'lgpd', 'editar');
    }, [new PermissionMiddleware('lgpd', 'editar')]);

    $router->post('/admin/modulos/lgpd/retencao', static function (Request $request): Response {
        $valor = trim((string) $request->post('prazo_retencao_auditoria_anos', ''));
        if (!ctype_digit($valor) || (int) $valor < 1 || (int) $valor > 100) {
            Session::flash('erro', 'Informe um prazo de retenção entre 1 e 100 anos.');
            return redirect('admin/modulos/lgpd');
        }

        $configuracoes = ConfiguracaoRepository::valores(ConfiguracaoRepository::listar());
        $anterior = (string) ($configuracoes['prazo_retencao_auditoria_anos'] ?? '5');
        ConfiguracaoRepository::salvar(['prazo_retencao_auditoria_anos' => $valor]);
        Audit::registrar('editar', 'lgpd', 'configuracoes', null, ['prazo_retencao_auditoria_anos' => $anterior], ['prazo_retencao_auditoria_anos' => $valor]);
        Session::flash('sucesso', 'Política de retenção salva com sucesso.');
        return redirect('admin/modulos/lgpd');
    }, [new CsrfMiddleware(), new PermissionMiddleware('lgpd', 'editar')]);

    $router->post('/admin/modulos/lgpd/{id}/excluir', static function (Request $request, string $id) use ($lgpdVoltar): Response {
        $registro = LgpdRepository::buscar((int) $id);
        if ($registro === null) {
            Session::flash('erro', 'Consentimento não encontrado.');
            return redirect($lgpdVoltar($request));
        }
        LgpdRepository::excluir((int) $id);
        Audit::registrar('excluir', 'lgpd', 'consentimento', (int) $id, $registro, null);
        Session::flash('sucesso', 'Consentimento excluído com sucesso.');
        return redirect($lgpdVoltar($request));
    }, [new CsrfMiddleware(), new PermissionMiddleware('lgpd', 'editar')]);

    $router->post('/admin/modulos/lgpd', static function (Request $request) use ($lgpdValidar, $lgpdVoltar): Response {
        [$dados, $erros] = $lgpdValidar($request);
        if ($erros !== []) {
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/lgpd/novo');
        }

        $id = LgpdRepository::criar($dados);
        Audit::registrar('criar', 'lgpd', 'consentimento', $id, null, $dados);
        Session::flash('sucesso', 'Consentimento registrado com sucesso.');
        return redirect($lgpdVoltar($request));
    }, [new CsrfMiddleware(), new PermissionMiddleware('lgpd', 'editar')]);

    $router->post('/admin/modulos/lgpd/{id}', static function (Request $request, string $id) use ($lgpdValidar, $lgpdVoltar): Response {
        $registro = LgpdRepository::buscar((int) $id);
        if ($registro === null) {
            Session::flash('erro', 'Consentimento não encontrado.');
            return redirect($lgpdVoltar($request));
        }

        [$dados, $erros] = $lgpdValidar($request);
        if ($erros !== []) {
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $request->post());
            return redirect('admin/modulos/lgpd/' . (int) $id . '/editar');
        }

        LgpdRepository::atualizar((int) $id, $dados);
        Audit::registrar('editar', 'lgpd', 'consentimento', (int) $id, $registro, $dados);
        Session::flash('sucesso', 'Consentimento atualizado com sucesso.');
        return redirect($lgpdVoltar($request));
    }, [new CsrfMiddleware(), new PermissionMiddleware('lgpd', 'editar')]);

    $backupFiltros = static function (Request $request): array {
        return BackupRepository::filtros($request->query);
    };

    $backupVoltar = static function (Request $request): string {
        $query = array_filter([
            'de' => (string) $request->query('de', ''),
            'ate' => (string) $request->query('ate', ''),
            'status' => (string) $request->query('status', ''),
            'q' => (string) $request->query('q', ''),
            'limite' => (string) $request->query('limite', ''),
        ], static fn (string $item): bool => $item !== '' && $item !== '0');

        return 'admin/modulos/backup' . ($query === [] ? '' : '?' . http_build_query($query));
    };

    $backupCsv = static function (array $registros): Response {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Arquivo', 'Gerado em', 'Gerado por', 'Tabelas', 'Tamanho (bytes)', 'Duracao (ms)', 'Situacao'], ';', '"', '\\');
        foreach ($registros as $registro) {
            fputcsv($handle, [
                (string) $registro['nome_arquivo'],
                (string) $registro['gerado_em'],
                (string) ($registro['usuario_nome'] ?? ''),
                (int) $registro['tabelas'],
                (int) $registro['tamanho_bytes'],
                (int) $registro['duracao_ms'],
                (string) $registro['status'],
            ], ';', '"', '\\');
        }
        rewind($handle);
        $conteudo = (string) stream_get_contents($handle);
        fclose($handle);

        return Response::html($conteudo)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="backup_historico.csv"')
            ->withHeader('Cache-Control', 'no-store');
    };

    $router->get('/admin/modulos/backup', static function (Request $request) use ($backupFiltros): Response {
        $filtros = $backupFiltros($request);

        return admin_view('admin/backup/index', [
            'titulo' => 'Backup',
            'subtitulo' => 'Geração, acompanhamento e download de cópias de segurança do banco de dados.',
            'backupFiltros' => $filtros,
            'registrosBackup' => BackupRepository::listar($filtros),
            'backupEstatisticas' => BackupRepository::estatisticas($filtros),
        ], 'backup', 'backup', 'gerar');
    }, [new PermissionMiddleware('backup', 'gerar')]);

    $router->get('/admin/modulos/backup/exportar', static function (Request $request) use ($backupFiltros, $backupCsv): Response {
        $filtros = $backupFiltros($request);
        $registros = BackupRepository::listar($filtros);
        Audit::registrar('exportar', 'backup', 'historico', null, null, $filtros);

        return $backupCsv($registros);
    }, [new PermissionMiddleware('backup', 'gerar')]);

    $router->get('/admin/modulos/backup/imprimir', static function (Request $request) use ($backupFiltros): Response {
        $filtros = $backupFiltros($request);
        $registros = BackupRepository::listar($filtros);
        Audit::registrar('imprimir', 'backup', 'historico', null, null, $filtros);

        return Response::html(View::render('admin/backup/backup_print', [
            'backupFiltros' => $filtros,
            'registrosBackup' => $registros,
            'backupEstatisticas' => BackupRepository::estatisticas($filtros),
        ], 'layouts/print'));
    }, [new PermissionMiddleware('backup', 'gerar')]);

    $router->get('/admin/modulos/backup/download/{id}', static function (Request $request, string $id): Response {
        try {
            [$registro, $conteudo] = BackupRepository::conteudo((int) $id);
        } catch (Throwable $exception) {
            Session::flash('erro', 'Não foi possível baixar o backup solicitado.');
            return redirect('admin/modulos/backup');
        }

        Audit::registrar('exportar', 'backup', 'arquivo', (int) $registro['id'], null, [
            'nome_arquivo' => (string) $registro['nome_arquivo'],
            'tamanho_bytes' => (int) $registro['tamanho_bytes'],
        ]);

        return Response::html($conteudo)
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . (string) $registro['nome_arquivo'] . '"')
            ->withHeader('Cache-Control', 'no-store');
    }, [new PermissionMiddleware('backup', 'gerar')]);

    $router->post('/admin/modulos/backup/gerar', static function (Request $request) use ($backupVoltar): Response {
        try {
            $registro = BackupRepository::criar((int) Auth::id());
            Audit::registrar('gerar', 'backup', 'arquivo', (int) $registro['id'], null, [
                'nome_arquivo' => (string) $registro['nome_arquivo'],
                'tamanho_bytes' => (int) ($registro['tamanho_bytes'] ?? 0),
                'tabelas' => (int) ($registro['tabelas'] ?? 0),
            ]);
            Session::flash('sucesso', 'Backup gerado com sucesso.');
        } catch (Throwable $exception) {
            Audit::registrar('gerar', 'backup', 'arquivo', null, null, [
                'sucesso' => false,
                'erro' => mb_substr($exception->getMessage(), 0, 200),
            ]);
            Session::flash('erro', 'Não foi possível gerar o backup.');
        }

        return redirect($backupVoltar($request));
    }, [new CsrfMiddleware(), new PermissionMiddleware('backup', 'gerar')]);

    $router->post('/admin/modulos/backup/{id}/excluir', static function (Request $request, string $id) use ($backupVoltar): Response {
        try {
            $registro = BackupRepository::excluir((int) $id);
            if ($registro === null) {
                Session::flash('erro', 'Backup não encontrado.');
                return redirect($backupVoltar($request));
            }
            Audit::registrar('excluir', 'backup', 'arquivo', (int) $registro['id'], $registro, null);
            Session::flash('sucesso', 'Backup excluído com sucesso.');
        } catch (Throwable $exception) {
            Session::flash('erro', 'Não foi possível excluir o backup.');
        }

        return redirect($backupVoltar($request));
    }, [new CsrfMiddleware(), new PermissionMiddleware('backup', 'gerar')]);

    $configuracaoArquivo = static function (?array $grupo, string $chave): ?array {
        if ($grupo === null || !isset($grupo['name'][$chave]) || !is_array($grupo['name'])) {
            return null;
        }

        return [
            'name' => (string) $grupo['name'][$chave],
            'type' => (string) ($grupo['type'][$chave] ?? ''),
            'tmp_name' => (string) ($grupo['tmp_name'][$chave] ?? ''),
            'error' => (int) ($grupo['error'][$chave] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($grupo['size'][$chave] ?? 0),
        ];
    };

    $router->get('/admin/modulos/configuracoes', static function () use ($estadoFormulario): Response {
        $configuracoes = ConfiguracaoRepository::listar();
        $estado = $estadoFormulario();

        return admin_view('admin/configuracoes/index', [
            'titulo' => 'Configurações',
            'subtitulo' => 'Personalize os dados usados pelo site, pelos documentos e pelo atendimento.',
            'grupos' => ConfiguracaoRepository::agrupar($configuracoes),
            'valores' => $estado['valores'] === [] ? ConfiguracaoRepository::valores($configuracoes) : $estado['valores'],
            'erros' => $estado['erros'],
        ], 'configuracoes', 'configuracoes', 'ver');
    }, [new PermissionMiddleware('configuracoes', 'ver')]);

    $router->post('/admin/modulos/configuracoes', static function (Request $request) use ($configuracaoArquivo): Response {
        $configuracoes = ConfiguracaoRepository::listar();
        $anteriores = ConfiguracaoRepository::valores($configuracoes);
        $config = $request->post('config', []);
        $remover = $request->post('remover', []);
        $config = is_array($config) ? $config : [];
        $remover = is_array($remover) ? $remover : [];

        $erros = [];
        $novos = [];
        foreach ($configuracoes as $item) {
            $chave = (string) $item['chave'];
            $tipo = (string) $item['tipo'];
            if ($chave === '' || $tipo === 'arquivo') {
                continue;
            }

            $atual = $anteriores[$chave] ?? '';
            $valor = trim((string) ($config[$chave] ?? $atual));
            if (mb_strlen($valor) > 1000) {
                $erros[$chave] = 'Informe no máximo 1000 caracteres.';
                continue;
            }
            if ($valor !== '' && $tipo === 'email' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
                $erros[$chave] = 'Informe um e-mail válido.';
            } elseif ($valor !== '' && $tipo === 'url' && filter_var($valor, FILTER_VALIDATE_URL) === false) {
                $erros[$chave] = 'Informe uma URL válida.';
            } elseif ($valor !== '' && $tipo === 'cor' && preg_match('/^#[0-9a-f]{6}$/i', $valor) !== 1) {
                $erros[$chave] = 'Informe uma cor no formato #RRGGBB.';
            } elseif ($valor !== '' && $tipo === 'numero' && filter_var($valor, FILTER_VALIDATE_INT) === false) {
                $erros[$chave] = 'Informe um número inteiro.';
            } elseif ($valor !== '' && $tipo === 'numero' && (int) $valor <= 0) {
                $erros[$chave] = 'Informe um valor maior que zero.';
            }

            if ($valor !== $atual) {
                $novos[$chave] = $valor;
            }
        }

        $valoresFlash = [];
        foreach ($configuracoes as $item) {
            $chave = (string) $item['chave'];
            if ((string) $item['tipo'] === 'arquivo') {
                continue;
            }
            $valoresFlash[$chave] = (string) ($config[$chave] ?? ($anteriores[$chave] ?? ''));
        }

        if ($erros !== []) {
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $valoresFlash);
            return redirect('admin/modulos/configuracoes');
        }

        $arquivos = $request->file('arquivo');
        $enviados = [];
        $antigosParaExcluir = [];
        foreach ($configuracoes as $item) {
            $chave = (string) $item['chave'];
            if ((string) $item['tipo'] !== 'arquivo' || $chave === '') {
                continue;
            }

            $arquivo = $configuracaoArquivo($arquivos, $chave);
            $atual = $anteriores[$chave] ?? '';
            $codigo = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($arquivo !== null && $codigo !== UPLOAD_ERR_NO_FILE) {
                try {
                    $caminho = Upload::image($arquivo, 'configuracoes', $chave);
                    $enviados[] = $caminho;
                    if ($caminho !== $atual) {
                        $novos[$chave] = $caminho;
                        if ($atual !== '') {
                            $antigosParaExcluir[] = $atual;
                        }
                    }
                } catch (Throwable $exception) {
                    $erros[$chave] = $exception->getMessage();
                }
            } elseif (($remover[$chave] ?? '') === '1' && $atual !== '') {
                $novos[$chave] = '';
                $antigosParaExcluir[] = $atual;
            }
        }

        if ($erros !== []) {
            foreach ($enviados as $caminho) {
                Upload::excluir($caminho);
            }
            Session::flash('form_erros', $erros);
            Session::flash('form_valores', $valoresFlash);
            return redirect('admin/modulos/configuracoes');
        }

        if ($novos !== []) {
            ConfiguracaoRepository::salvar($novos);
            foreach ($antigosParaExcluir as $caminho) {
                Upload::excluir($caminho);
            }
            Audit::registrar('editar', 'configuracoes', 'configuracoes', null, array_intersect_key($anteriores, $novos), $novos);
        }

        Session::flash('sucesso', $novos === [] ? 'Nenhuma configuração foi alterada.' : 'Configurações salvas com sucesso.');
        return redirect('admin/modulos/configuracoes');
    }, [new CsrfMiddleware(), new PermissionMiddleware('configuracoes', 'editar')]);

    $router->get('/admin/modulos/{modulo}', static function (string $modulo): Response {
        $informacao = AdminRepository::modulo($modulo);
        if ($informacao === null) {
            throw new HttpException(404, 'O módulo solicitado não existe.');
        }

        return admin_view('admin/modulo', [
            'titulo' => (string) $informacao['nome'],
            'subtitulo' => (string) $informacao['descricao'],
            'modulo' => $informacao,
        ], $modulo, (string) $informacao['modulo'], (string) $informacao['acao']);
    });

    $router->post('/logout', static function (): Response {
        Auth::logout();
        return redirect('login?sair=1');
    }, [new CsrfMiddleware()]);
};
