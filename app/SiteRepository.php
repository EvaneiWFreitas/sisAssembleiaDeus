<?php
declare(strict_types=1);

namespace App;

use Throwable;

final class SiteRepository
{
    private static ?array $configuracoes = null;

    /** @return array<string, mixed> */
    public static function contexto(string $rotaAtiva = ''): array
    {
        $nome = self::config('ministerio_nome', '');
        if ($nome === '' || stripos($nome, 'Nome do Minist') === 0 || str_contains($nome, '??')) {
            $nome = 'Assembleia de Deus';
        }

        return [
            'config' => self::configuracoes(),
            'rotaAtiva' => $rotaAtiva,
            'nomeIgreja' => $nome,
            'logotipo' => self::logotipo(),
            'assinaturaPastor' => self::assinaturaPastor(),
            'pastorPresidente' => self::config('pastor_presidente', ''),
            'endereco' => self::endereco(),
            'telefone' => self::config('ministerio_telefone', ''),
            'whatsapp' => self::config('ministerio_whatsapp', ''),
            'whatsappLink' => self::whatsappLink(),
            'email' => self::config('ministerio_email', ''),
            'redes' => [
                'facebook' => self::config('redes_facebook', ''),
                'instagram' => self::config('redes_instagram', ''),
                'youtube' => self::config('redes_youtube', ''),
                'telegram' => self::config('redes_telegram', ''),
            ],
            'cores' => [
                'primaria' => self::cor('cor_primaria', '#0b2545'),
                'secundaria' => self::cor('cor_secundaria', '#c9a227'),
                'fundo' => self::cor('cor_fundo', '#f4f6f9'),
            ],
            'versiculo' => self::versiculo(),
        ];
    }

    /** @return array<string, string> */
    public static function configuracoes(): array
    {
        if (self::$configuracoes !== null) {
            return self::$configuracoes;
        }

        $valores = [];
        try {
            $linhas = Database::run('SELECT `chave`, `valor` FROM `configuracoes`')->fetchAll();
            foreach ($linhas as $linha) {
                $chave = (string) ($linha['chave'] ?? '');
                if ($chave !== '') {
                    $valores[$chave] = (string) ($linha['valor'] ?? '');
                }
            }
        } catch (Throwable) {
            $valores = [];
        }

        self::$configuracoes = $valores;
        return $valores;
    }

    public static function config(string $chave, string $padrao = ''): string
    {
        $valor = self::configuracoes()[$chave] ?? '';
        return $valor === '' ? $padrao : $valor;
    }

    public static function endereco(): string
    {
        $partes = [];
        foreach (['ministerio_endereco', 'ministerio_numero', 'ministerio_complemento', 'ministerio_bairro', 'ministerio_cidade', 'ministerio_estado', 'ministerio_cep'] as $chave) {
            $valor = self::config($chave, '');
            if ($valor !== '') {
                $partes[] = $valor;
            }
        }

        return $partes === [] ? 'Endereço institucional a ser configurado.' : implode(', ', $partes);
    }

    public static function whatsappLink(): string
    {
        $digitos = preg_replace('/\D/', '', self::config('ministerio_whatsapp', '')) ?? '';
        if (strlen($digitos) < 10) {
            return '';
        }

        $numero = str_starts_with($digitos, '55') && strlen($digitos) >= 12
            ? $digitos
            : '55' . ltrim($digitos, '0');

        return 'https://wa.me/' . $numero;
    }

    public static function logotipo(): string
    {
        return self::imagem(self::config('ministerio_logotipo', ''), 'assets/img/logo-assembleia.svg');
    }

    public static function assinaturaPastor(): string
    {
        $caminho = self::config('assinatura_pastor', '');
        return $caminho === '' ? '' : self::imagem($caminho, '');
    }

    public static function imagem(?string $caminho, string $padrao = 'assets/img/galeria-placeholder.svg'): string
    {
        $valor = trim((string) $caminho);
        if ($valor === '') {
            return upload_url($padrao);
        }
        if (preg_match('#^https?://#i', $valor) === 1) {
            return $valor;
        }

        return upload_url($valor);
    }

    /** @return list<array<string, mixed>> */
    public static function eventos(int $limite = 3): array
    {
        $limite = max(1, min(12, $limite));
        $sql = 'SELECT `titulo`, `slug`, `descricao`, `tipo`, `data_inicio`, `data_fim`, `horario`, `local`, `foto`
                FROM `eventos`
                WHERE `publicado` = 1 AND `deleted_at` IS NULL AND `data_inicio` >= NOW()
                ORDER BY `destaque` DESC, `data_inicio` ASC
                LIMIT ' . $limite;

        try {
            $linhas = Database::run($sql)->fetchAll();
            if ($linhas !== []) {
                return $linhas;
            }
        } catch (Throwable) {
        }

        return self::eventosDemo($limite);
    }

    /** @return list<array<string, mixed>> */
    public static function noticias(int $limite = 3): array
    {
        $limite = max(1, min(12, $limite));
        $sql = 'SELECT `titulo`, `slug`, `resumo`, `conteudo`, `imagem`, `publicada_em`, `created_at`
                FROM `noticias`
                WHERE `publicada` = 1 AND `deleted_at` IS NULL
                ORDER BY COALESCE(`publicada_em`, `created_at`) DESC
                LIMIT ' . $limite;

        try {
            $linhas = Database::run($sql)->fetchAll();
            if ($linhas !== []) {
                return $linhas;
            }
        } catch (Throwable) {
        }

        return array_slice(self::noticiasDemo(), 0, $limite);
    }

    /** @return array<string, mixed>|null */
    public static function noticiaPorSlug(string $slug): ?array
    {
        try {
            $linha = Database::run(
                'SELECT `titulo`, `slug`, `resumo`, `conteudo`, `imagem`, `publicada_em`, `created_at`
                 FROM `noticias`
                 WHERE `slug` = :slug AND `publicada` = 1 AND `deleted_at` IS NULL
                 LIMIT 1',
                [':slug' => $slug]
            )->fetch();
            if (is_array($linha) && $linha !== []) {
                return $linha;
            }
        } catch (Throwable) {
        }

        foreach (self::noticiasDemo() as $noticia) {
            if ($noticia['slug'] === $slug) {
                return $noticia;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function galeria(int $limite = 8): array
    {
        $limite = max(1, min(24, $limite));
        try {
            $linhas = Database::run(
                'SELECT `titulo`, `descricao`, `imagem` FROM `galeria` WHERE `ativo` = 1 ORDER BY `ordem` ASC, `id` ASC LIMIT ' . $limite
            )->fetchAll();
            if ($linhas !== []) {
                return $linhas;
            }
        } catch (Throwable) {
        }

        return array_slice(self::galeriaDemo(), 0, $limite);
    }

    /** @return list<array<string, mixed>> */
    public static function cultos(): array
    {
        $sql = 'SELECT p.`dia_semana`, p.`horario`, p.`tipo`, p.`descricao`, c.`nome` AS `congregacao`
                FROM `programacao_cultos` p
                LEFT JOIN `congregacoes` c ON c.`id` = p.`congregacao_id`
                WHERE p.`ativo` = 1
                ORDER BY FIELD(p.`dia_semana`, "domingo", "segunda", "terca", "quarta", "quinta", "sexta", "sabado"), p.`ordem` ASC, p.`horario` ASC
                LIMIT 12';

        try {
            $linhas = Database::run($sql)->fetchAll();
            if ($linhas !== []) {
                return $linhas;
            }
        } catch (Throwable) {
        }

        return [
            ['dia_semana' => 'domingo', 'horario' => '09:00:00', 'tipo' => 'Escola Bíblica Dominical', 'descricao' => 'Ensino bíblico para todas as idades.', 'congregacao' => 'Igreja Matriz'],
            ['dia_semana' => 'domingo', 'horario' => '18:00:00', 'tipo' => 'Culto da Família', 'descricao' => 'Celebração com a família da igreja.', 'congregacao' => 'Igreja Matriz'],
            ['dia_semana' => 'quarta', 'horario' => '19:30:00', 'tipo' => 'Estudo Bíblico', 'descricao' => 'Exposição da Palavra e oração.', 'congregacao' => 'Igreja Matriz'],
            ['dia_semana' => 'sexta', 'horario' => '19:30:00', 'tipo' => 'Culto de Oração', 'descricao' => 'Momento de intercessão e louvor.', 'congregacao' => 'Igreja Matriz'],
        ];
    }

    /** @return array<string, string> */
    public static function versiculo(): array
    {
        try {
            $linha = Database::run(
                'SELECT `referencia`, `texto` FROM `versiculos_destaque` WHERE `ativo` = 1 ORDER BY `ordem` ASC, `id` ASC LIMIT 1'
            )->fetch();
            if (is_array($linha) && $linha !== []) {
                return [
                    'referencia' => (string) ($linha['referencia'] ?? ''),
                    'texto' => (string) ($linha['texto'] ?? ''),
                ];
            }
        } catch (Throwable) {
        }

        $referencia = self::config('versiculo_referencia', '');
        $texto = self::config('versiculo_texto', '');
        if ($referencia !== '' && $texto !== '') {
            return ['referencia' => $referencia, 'texto' => $texto];
        }

        return [
            'referencia' => 'João 3:16',
            'texto' => 'Porque Deus amou o mundo de tal maneira que deu o seu Filho unigênito, para que todo aquele que nele crê não pereça, mas tenha a vida eterna.',
        ];
    }

    /** @return array<string, string> */
    public static function palavra(): array
    {
        try {
            $linha = Database::run(
                'SELECT p.`titulo`, p.`texto`, p.`foto`, u.`nome` AS `autor`
                 FROM `palavra_pastoral` p
                 LEFT JOIN `usuarios` u ON u.`id` = p.`autor_id`
                 WHERE p.`publicada` = 1
                 ORDER BY p.`created_at` DESC
                 LIMIT 1'
            )->fetch();
            if (is_array($linha) && $linha !== []) {
                return [
                    'titulo' => (string) ($linha['titulo'] ?? ''),
                    'texto' => (string) ($linha['texto'] ?? ''),
                    'foto' => (string) ($linha['foto'] ?? ''),
                    'autor' => (string) ($linha['autor'] ?? self::config('pastor_presidente', '')),
                ];
            }
        } catch (Throwable) {
        }

        return [
            'titulo' => self::config('palavra_pastoral_titulo', 'Uma palavra de esperança'),
            'texto' => self::config('palavra_pastoral_texto', 'A Palavra de Deus é lâmpada para os nossos pés e luz para o nosso caminho. Que esta verdade guie cada decisão da nossa família espiritual.'),
            'foto' => '',
            'autor' => self::config('pastor_presidente', 'Pastor Presidente'),
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvarMensagem(array $dados, string $ip): void
    {
        Database::run(
            'INSERT INTO `contato_mensagens` (`nome`, `email`, `telefone`, `assunto`, `mensagem`, `ip`, `created_at`)
             VALUES (:nome, :email, :telefone, :assunto, :mensagem, :ip, NOW())',
            [
                ':nome' => (string) ($dados['nome'] ?? ''),
                ':email' => (string) ($dados['email'] ?? ''),
                ':telefone' => (string) ($dados['telefone'] ?? ''),
                ':assunto' => (string) ($dados['assunto'] ?? ''),
                ':mensagem' => (string) ($dados['mensagem'] ?? ''),
                ':ip' => $ip !== '' ? $ip : null,
            ]
        );
    }

    private static function cor(string $chave, string $padrao): string
    {
        $valor = self::config($chave, $padrao);
        return preg_match('/^#[0-9a-f]{6}$/i', $valor) === 1 ? $valor : $padrao;
    }

    /** @return list<array<string, mixed>> */
    private static function eventosDemo(int $limite): array
    {
        $eventos = [
            ['titulo' => 'Culto de Celebração', 'slug' => 'culto-de-celebracao', 'descricao' => 'Um momento de louvor, Palavra e comunhão com toda a família da igreja.', 'tipo' => 'culto', 'data_inicio' => date('Y-m-d H:i:s', (int) strtotime('next sunday 18:00:00')), 'data_fim' => null, 'horario' => '18:00', 'local' => 'Igreja Matriz', 'foto' => ''],
            ['titulo' => 'Encontro de Oração', 'slug' => 'encontro-de-oracao', 'descricao' => 'Intercessão, cânticos e tempo de buscar a Deus em comunidade.', 'tipo' => 'reuniao', 'data_inicio' => date('Y-m-d H:i:s', (int) strtotime('next wednesday 19:30:00')), 'data_fim' => null, 'horario' => '19:30', 'local' => 'Salão principal', 'foto' => ''],
            ['titulo' => 'Escola Bíblica Dominical', 'slug' => 'escola-biblica-dominical', 'descricao' => 'Ensino bíblico para crianças, jovens e adultos.', 'tipo' => 'escola_biblica', 'data_inicio' => date('Y-m-d H:i:s', (int) strtotime('next sunday 09:00:00')), 'data_fim' => null, 'horario' => '09:00', 'local' => 'Salas de ensino', 'foto' => ''],
            ['titulo' => 'Campanha de Missões', 'slug' => 'campanha-de-missoes', 'descricao' => 'Mobilização da igreja para a obra missionária local e global.', 'tipo' => 'campanha', 'data_inicio' => date('Y-m-d H:i:s', (int) strtotime('next saturday 09:00:00')), 'data_fim' => null, 'horario' => '09:00', 'local' => ' Igreja Matriz', 'foto' => ''],
        ];

        return array_slice($eventos, 0, $limite);
    }

    /** @return list<array<string, mixed>> */
    private static function noticiasDemo(): array
    {
        return [
            [
                'titulo' => 'Bem-vindo ao novo portal da igreja',
                'slug' => 'bem-vindo-ao-novo-portal-da-igreja',
                'resumo' => 'Um espaço para acompanhar cultos, eventos, notícias e informações da nossa comunidade.',
                'conteudo' => 'Este é o novo canal de comunicação da nossa igreja. Aqui você encontra a programação dos cultos, as próximas atividades, notícias da comunidade e uma forma fácil de entrar em contato com a equipe.',
                'imagem' => 'assets/img/hero-igreja.svg',
                'publicada_em' => date('Y-m-d H:i:s', (int) strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', (int) strtotime('-2 days')),
            ],
            [
                'titulo' => 'Inscrições abertas para a Escola Bíblica',
                'slug' => 'inscricoes-abertas-para-a-escola-biblica',
                'resumo' => 'Todas as idades são bem-vindas para aprender a Palavra de Deus juntos.',
                'conteudo' => 'As inscrições para a Escola Bíblica Dominical estão abertas. Temos turmas para crianças, adolescentes, jovens e adultos, com ensino bíblico e acompanhamento de professores voluntários.',
                'imagem' => 'assets/img/galeria-placeholder.svg',
                'publicada_em' => date('Y-m-d H:i:s', (int) strtotime('-6 days')),
                'created_at' => date('Y-m-d H:i:s', (int) strtotime('-6 days')),
            ],
            [
                'titulo' => 'Culto de ação de graças',
                'slug' => 'culto-de-acao-de-gracas',
                'resumo' => 'Convidamos toda a família para um tempo especial de louvor e gratidão.',
                'conteudo' => 'O culto de ação de graças será realizado no salão principal, com participação dos ministérios e momentos de testemunho. Traga sua família e celebre conosco.',
                'imagem' => 'assets/img/galeria-placeholder.svg',
                'publicada_em' => date('Y-m-d H:i:s', (int) strtotime('-10 days')),
                'created_at' => date('Y-m-d H:i:s', (int) strtotime('-10 days')),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function galeriaDemo(): array
    {
        $titulos = ['Culto de domingo', 'Escola bíblica', 'Louvor e adoração', 'Oração em família', 'Batismo', 'Atividade infantil', 'Encontro de jovens', 'Comunhão'];
        $galeria = [];

        foreach ($titulos as $indice => $titulo) {
            $galeria[] = [
                'titulo' => $titulo,
                'descricao' => 'Momentos da nossa comunidade.',
                'imagem' => 'assets/img/galeria-placeholder.svg',
                'ordem' => $indice,
            ];
        }

        return $galeria;
    }
}
