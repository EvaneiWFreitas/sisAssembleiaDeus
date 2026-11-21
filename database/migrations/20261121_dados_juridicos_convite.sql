SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

INSERT INTO `configuracoes` (`chave`, `valor`, `grupo`, `tipo`, `descricao`) VALUES
    ('ministerio_razao_social', '', 'identidade', 'texto', 'Razão social da igreja para papel timbrado.'),
    ('ministerio_inscricao_estadual', '', 'identidade', 'texto', 'Inscrição estadual da igreja para papel timbrado.'),
    ('ministerio_inscricao_municipal', '', 'identidade', 'texto', 'Inscrição municipal da igreja para papel timbrado.'),
    ('ministerio_alvara_funcionamento', '', 'identidade', 'texto', 'Alvará de funcionamento da igreja para papel timbrado.'),
    ('ministerio_registro_estatuto', '', 'identidade', 'texto', 'Registro do estatuto da igreja para papel timbrado.')
ON DUPLICATE KEY UPDATE
    `descricao` = VALUES(`descricao`),
    `grupo` = VALUES(`grupo`),
    `tipo` = VALUES(`tipo`);

UPDATE `configuracoes` SET `descricao` = 'CNPJ da igreja para papel timbrado.' WHERE `chave` = 'ministerio_cnpj';
