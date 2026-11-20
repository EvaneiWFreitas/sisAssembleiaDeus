<?php
declare(strict_types=1);

namespace App;

use Throwable;

final class AdminRepository
{
    /** @return array<string, mixed> */
    public static function contexto(string $ativa = ''): array
    {
        $usuario = Auth::user() ?? [];
        $perfilId = (int) ($usuario['perfil_id'] ?? 0);

        return [
            'usuario' => $usuario,
            'perfilNome' => self::perfilNome($perfilId),
            'permissoes' => Permission::all(),
            'modulos' => self::modulos(),
            'ativa' => $ativa,
            'estatisticas' => self::estatisticas(),
            'atividade' => self::atividade(),
        ];
    }

    /** @return array<string, array<string, string>> */
    public static function modulos(): array
    {
        return [
            'dashboard' => ['modulo' => 'dashboard', 'acao' => 'ver', 'grupo' => 'Painel', 'nome' => 'Visão geral', 'icone' => 'fa-gauge-high', 'descricao' => 'Indicadores e atividades recentes do sistema.'],
            'membros' => ['modulo' => 'membros', 'acao' => 'ver', 'grupo' => 'Cadastros', 'nome' => 'Membros', 'icone' => 'fa-users', 'descricao' => 'Cadastro, fichas e situação dos membros.'],
            'obreiros' => ['modulo' => 'obreiros', 'acao' => 'ver', 'grupo' => 'Cadastros', 'nome' => 'Obreiros', 'icone' => 'fa-user-tie', 'descricao' => 'Cadastro ministerial e histórico dos obreiros.'],
            'estrutura' => ['modulo' => 'estrutura', 'acao' => 'ver', 'grupo' => 'Cadastros', 'nome' => 'Estrutura', 'icone' => 'fa-place-of-worship', 'descricao' => 'Matriz, congregações e pontos de pregação.'],
            'arquivo_morto' => ['modulo' => 'arquivo_morto', 'acao' => 'ver', 'grupo' => 'Cadastros', 'nome' => 'Arquivo Morto', 'icone' => 'fa-box-archive', 'descricao' => 'Histórico de obreiros desligados e licenciados.'],
            'financeiro' => ['modulo' => 'financeiro', 'acao' => 'ver', 'grupo' => 'Financeiro', 'nome' => 'Financeiro', 'icone' => 'fa-coins', 'descricao' => 'Dízimos, ofertas, movimentações e fechamentos.'],
            'sacramentos' => ['modulo' => 'sacramentos', 'acao' => 'ver', 'grupo' => 'Ministerial', 'nome' => 'Sacramentos', 'icone' => 'fa-water', 'descricao' => 'Batismos e consagrações registrados.'],
            'documentos' => ['modulo' => 'documentos', 'acao' => 'ver', 'grupo' => 'Ministerial', 'nome' => 'Documentos', 'icone' => 'fa-file-lines', 'descricao' => 'Cartas-convite, diplomas e documentos emitidos.'],
            'carteirinhas' => ['modulo' => 'carteirinhas', 'acao' => 'ver', 'grupo' => 'Ministerial', 'nome' => 'Carteirinhas', 'icone' => 'fa-id-card', 'descricao' => 'Emissão e validação de carteirinhas.'],
            'eventos' => ['modulo' => 'eventos', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Eventos', 'icone' => 'fa-calendar-days', 'descricao' => 'Agenda e publicação de eventos.'],
            'noticias' => ['modulo' => 'noticias', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Notícias', 'icone' => 'fa-newspaper', 'descricao' => 'Notícias publicadas no site institucional.'],
            'galeria' => ['modulo' => 'galeria', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Galeria', 'icone' => 'fa-images', 'descricao' => 'Fotografias da comunidade.'],
            'programacao' => ['modulo' => 'programacao', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Programação', 'icone' => 'fa-clock', 'descricao' => 'Horários e cultos da semana.'],
            'palavra_pastoral' => ['modulo' => 'palavra_pastoral', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Palavra Pastoral', 'icone' => 'fa-book-bible', 'descricao' => 'Mensagens e versículos em destaque.'],
            'contato' => ['modulo' => 'contato', 'acao' => 'ver', 'grupo' => 'Conteúdo', 'nome' => 'Mensagens', 'icone' => 'fa-envelope', 'descricao' => 'Mensagens recebidas pelo formulário de contato.'],
            'relatorios' => ['modulo' => 'relatorios', 'acao' => 'ver', 'grupo' => 'Gestão', 'nome' => 'Relatórios', 'icone' => 'fa-chart-column', 'descricao' => 'Relatórios operacionais e exportações.'],
            'usuarios' => ['modulo' => 'usuarios', 'acao' => 'ver', 'grupo' => 'Administração', 'nome' => 'Usuários', 'icone' => 'fa-user-gear', 'descricao' => 'Contas de acesso ao sistema.'],
            'permissoes' => ['modulo' => 'permissoes', 'acao' => 'ver', 'grupo' => 'Administração', 'nome' => 'Perfis e Permissões', 'icone' => 'fa-key', 'descricao' => 'Perfis de acesso e permissões por módulo.'],
            'configuracoes' => ['modulo' => 'configuracoes', 'acao' => 'ver', 'grupo' => 'Administração', 'nome' => 'Configurações', 'icone' => 'fa-gear', 'descricao' => 'Identidade, contato e parâmetros gerais.'],
            'auditoria' => ['modulo' => 'auditoria', 'acao' => 'ver', 'grupo' => 'Administração', 'nome' => 'Auditoria', 'icone' => 'fa-list-check', 'descricao' => 'Trilha de ações realizadas no sistema.'],
            'lgpd' => ['modulo' => 'lgpd', 'acao' => 'ver', 'grupo' => 'Administração', 'nome' => 'LGPD', 'icone' => 'fa-shield-halved', 'descricao' => 'Consentimentos e retenção de dados.'],
            'backup' => ['modulo' => 'backup', 'acao' => 'gerar', 'grupo' => 'Administração', 'nome' => 'Backup', 'icone' => 'fa-database', 'descricao' => 'Geração de cópias de segurança.'],
        ];
    }

    /** @return array<string, string>|null */
    public static function modulo(string $chave): ?array
    {
        return self::modulos()[$chave] ?? null;
    }

    /** @param array<string, string> $modulo @param list<string> $permissoes */
    public static function permitido(array $modulo, array $permissoes): bool
    {
        return in_array($modulo['modulo'] . '.' . $modulo['acao'], $permissoes, true);
    }

    public static function perfilNome(int $perfilId): string
    {
        try {
            $linha = Database::run('SELECT `nome` FROM `perfis` WHERE `id` = :id LIMIT 1', [':id' => $perfilId])->fetch();
            if (is_array($linha) && isset($linha['nome'])) {
                return (string) $linha['nome'];
            }
        } catch (Throwable) {
        }

        return 'Perfil não identificado';
    }

    public static function iniciais(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome)) ?: [];
        $iniciais = '';
        foreach (array_slice($partes, 0, 2) as $parte) {
            $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
        }

        return $iniciais === '' ? 'AD' : $iniciais;
    }

    /** @return array<string, int> */
    public static function estatisticas(): array
    {
        $padrao = [
            'usuarios_ativos' => 0,
            'membros_ativos' => 0,
            'obreiros_ativos' => 0,
            'eventos_futuros' => 0,
            'mensagens_nao_lidas' => 0,
            'auditoria_7d' => 0,
        ];

        try {
            $linha = Database::run(
                'SELECT
                    (SELECT COUNT(*) FROM `usuarios` WHERE `ativo` = 1) AS `usuarios_ativos`,
                    (SELECT COUNT(*) FROM `membros` WHERE `situacao` = "ativo" AND `deleted_at` IS NULL) AS `membros_ativos`,
                    (SELECT COUNT(*) FROM `obreiros` WHERE `situacao` = "ativo" AND `deleted_at` IS NULL) AS `obreiros_ativos`,
                    (SELECT COUNT(*) FROM `eventos` WHERE `publicado` = 1 AND `deleted_at` IS NULL AND `data_inicio` >= NOW()) AS `eventos_futuros`,
                    (SELECT COUNT(*) FROM `contato_mensagens` WHERE `lida` = 0) AS `mensagens_nao_lidas`,
                    (SELECT COUNT(*) FROM `auditoria` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS `auditoria_7d`'
            )->fetch();

            if (is_array($linha)) {
                foreach ($padrao as $chave => $valor) {
                    $padrao[$chave] = (int) ($linha[$chave] ?? 0);
                }
            }
        } catch (Throwable) {
        }

        return $padrao;
    }

    /** @return list<array<string, mixed>> */
    public static function atividade(): array
    {
        try {
            return Database::run(
                'SELECT a.`acao`, a.`modulo`, a.`entidade`, a.`created_at`, u.`nome` AS `usuario`
                 FROM `auditoria` a
                 LEFT JOIN `usuarios` u ON u.`id` = a.`usuario_id`
                 ORDER BY a.`created_at` DESC
                 LIMIT 6'
            )->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }
}
