-- Perfis de acesso obrigatórios do sisAssembleiaDeus
SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

INSERT INTO `perfis` (`id`, `nome`, `slug`, `descricao`, `ativo`) VALUES
    (1, 'Superadministrador', 'superadministrador', 'Acesso total a todos os módulos, inclusive backup, permissões e LGPD.', 1),
    (2, 'Administrador Geral', 'administrador_geral', 'Administração operacional de todos os módulos, exceto backup, edição de permissões e LGPD.', 1),
    (3, 'Pastor Presidente', 'pastor_presidente', 'Visão geral do ministério, sacramentos, documentos, eventos e relatórios.', 1),
    (4, 'Pastor de Congregação', 'pastor_congregacao', 'Gestão pastoral da congregação vinculada, com visão restrita por congregação.', 1),
    (5, 'Secretário', 'secretario', 'Cadastros de membros e obreiros, documentos, eventos, notícias e relatórios.', 1),
    (6, 'Tesoureiro', 'tesoureiro', 'Lançamentos financeiros, fechamento mensal e relatórios econômicos.', 1),
    (7, 'Obreiro autorizado', 'obreiro_autorizado', 'Consulta de cadastros, eventos, programação e carteirinhas.', 1)
ON DUPLICATE KEY UPDATE
    `nome` = VALUES(`nome`),
    `descricao` = VALUES(`descricao`),
    `ativo` = VALUES(`ativo`);
