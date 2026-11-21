-- Matriz de permissões inicial por perfil
SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

-- 1. Superadministrador: acesso integral
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 1, `id` FROM `permissoes`;

-- 2. Administrador Geral: tudo, exceto backup, edição de permissões e LGPD
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 2, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) NOT IN (('backup','gerar'), ('permissoes','editar'), ('lgpd','editar'));

-- 3. Pastor Presidente
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 3, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) IN (
    ('dashboard','ver'), ('auditoria','ver'), ('configuracoes','ver'),
    ('usuarios','ver'), ('membros','ver'), ('membros','imprimir'), ('membros','historico'),
    ('obreiros','ver'), ('obreiros','transferir'), ('obreiros','imprimir'), ('obreiros','historico'),
    ('arquivo_morto','ver'), ('estrutura','ver'), ('financeiro','ver'), ('financeiro','relatorio'),
    ('sacramentos','ver'), ('sacramentos','criar'), ('sacramentos','editar'),
    ('documentos','ver'), ('documentos','emitir'),
    ('carteirinhas','ver'), ('carteirinhas','emitir'), ('carteirinhas','validar'),
    ('eventos','ver'), ('eventos','criar'), ('eventos','editar'), ('eventos','publicar'),
    ('noticias','ver'), ('noticias','publicar'), ('galeria','ver'),
    ('programacao','ver'), ('programacao','editar'),
    ('palavra_pastoral','ver'), ('palavra_pastoral','editar'),
    ('relatorios','ver'), ('relatorios','exportar'), ('relatorios','imprimir'),
    ('lgpd','ver')
);

-- 4. Pastor de Congregação (filtragem por congregação aplicada na aplicação)
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 4, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) IN (
    ('dashboard','ver'), ('membros','ver'), ('membros','criar'), ('membros','editar'),
    ('membros','imprimir'), ('membros','historico'),
    ('obreiros','ver'), ('obreiros','imprimir'), ('obreiros','historico'),
    ('arquivo_morto','ver'), ('estrutura','ver'), ('financeiro','ver'),
    ('sacramentos','ver'), ('documentos','ver'), ('documentos','emitir'),
    ('carteirinhas','ver'), ('carteirinhas','emitir'),
    ('eventos','ver'), ('eventos','criar'), ('eventos','editar'),
    ('noticias','ver'), ('galeria','ver'), ('programacao','ver'),
    ('relatorios','ver'), ('contato','ver')
);

-- 5. Secretário
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 5, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) IN (
    ('dashboard','ver'),
    ('membros','ver'), ('membros','criar'), ('membros','editar'), ('membros','imprimir'), ('membros','historico'),
    ('obreiros','ver'), ('obreiros','criar'), ('obreiros','editar'), ('obreiros','imprimir'), ('obreiros','historico'),
    ('arquivo_morto','ver'), ('arquivo_morto','transferir'), ('arquivo_morto','imprimir'),
    ('estrutura','ver'), ('financeiro','ver'),
    ('sacramentos','ver'), ('sacramentos','criar'), ('sacramentos','editar'),
    ('documentos','ver'), ('documentos','emitir'),
    ('carteirinhas','ver'), ('carteirinhas','emitir'),
    ('eventos','ver'), ('eventos','criar'), ('eventos','editar'), ('eventos','publicar'),
    ('noticias','ver'), ('noticias','criar'), ('noticias','editar'), ('noticias','publicar'),
    ('galeria','ver'), ('galeria','criar'), ('galeria','editar'),
    ('programacao','ver'), ('programacao','editar'),
    ('relatorios','ver'), ('relatorios','exportar'), ('relatorios','imprimir'),
    ('contato','ver'), ('contato','responder'), ('configuracoes','ver')
);

-- 6. Tesoureiro
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 6, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) IN (
    ('dashboard','ver'), ('membros','ver'), ('estrutura','ver'),
    ('financeiro','ver'), ('financeiro','lancar'), ('financeiro','editar'),
    ('financeiro','fechar'), ('financeiro','relatorio'),
    ('relatorios','ver'), ('relatorios','exportar'), ('relatorios','imprimir'),
    ('contato','ver')
);

-- 7. Obreiro autorizado
INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 7, `id` FROM `permissoes`
WHERE (`modulo`, `acao`) IN (
    ('dashboard','ver'), ('membros','ver'), ('obreiros','ver'),
    ('estrutura','ver'), ('eventos','ver'), ('noticias','ver'), ('galeria','ver'),
    ('programacao','ver'), ('carteirinhas','ver'), ('relatorios','ver'), ('contato','ver')
);

INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 1, `id` FROM `permissoes` WHERE `modulo` = 'carrossel';

INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 2, `id` FROM `permissoes` WHERE `modulo` = 'carrossel';

INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 3, `id` FROM `permissoes` WHERE `modulo` = 'carrossel' AND `acao` IN ('ver', 'editar');

INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 4, `id` FROM `permissoes` WHERE `modulo` = 'carrossel' AND `acao` = 'ver';

INSERT IGNORE INTO `perfil_permissao` (`perfil_id`, `permissao_id`)
SELECT 5, `id` FROM `permissoes` WHERE `modulo` = 'carrossel';
