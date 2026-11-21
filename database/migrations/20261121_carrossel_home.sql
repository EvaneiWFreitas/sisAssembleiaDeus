-- Cria o módulo de carrossel da página inicial (Bem-vindo à família)
SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

CREATE TABLE IF NOT EXISTS `carrossel` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(150) NULL DEFAULT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `imagem` VARCHAR(255) NOT NULL,
    `ordem` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_carrossel_ativo` (`ativo`),
    KEY `idx_carrossel_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracoes` (`chave`, `valor`, `grupo`, `tipo`, `descricao`) VALUES
    ('home_eyebrow', CONVERT(UNHEX('42656d2d76696e646f20c3a02066616dc3ad6c6961') USING utf8mb4), 'publico', 'texto', 'Texto em destaque acima do titulo na pagina inicial.'),
    ('home_descricao', CONVERT(UNHEX('556d206c756761722064652066c3a92c206573706572616ec3a761206520636f6d756e68c3a36f2e2041636f6d70616e6865206e6f73736f732063756c746f732c206576656e746f732065206e6f74c3ad636961732c2065206661c3a7612070617274652064612066616dc3ad6c696120646120696772656a612e') USING utf8mb4), 'publico', 'texto', 'Descricao exibida no carrossel da pagina inicial.')
ON DUPLICATE KEY UPDATE
    `valor` = IF(`valor` = '', VALUES(`valor`), `valor`),
    `descricao` = VALUES(`descricao`),
    `grupo` = VALUES(`grupo`),
    `tipo` = VALUES(`tipo`);

UPDATE `configuracoes`
SET `valor` = CONVERT(UNHEX('42656d2d76696e646f20c3a02066616dc3ad6c6961') USING utf8mb4)
WHERE `chave` = 'home_eyebrow' AND (`valor` = '' OR `valor` IS NULL);

UPDATE `configuracoes`
SET `valor` = CONVERT(UNHEX('556d206c756761722064652066c3a92c206573706572616ec3a761206520636f6d756e68c3a36f2e2041636f6d70616e6865206e6f73736f732063756c746f732c206576656e746f732065206e6f74c3ad636961732c2065206661c3a7612070617274652064612066616dc3ad6c696120646120696772656a612e') USING utf8mb4)
WHERE `chave` = 'home_descricao' AND (`valor` = '' OR `valor` IS NULL);

INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
    ('carrossel', 'ver', 'Consultar imagens de fundo do carrossel da pagina inicial'),
    ('carrossel', 'criar', 'Adicionar imagens de fundo ao carrossel'),
    ('carrossel', 'editar', 'Editar imagens de fundo do carrossel'),
    ('carrossel', 'excluir', 'Excluir imagens de fundo do carrossel')
ON DUPLICATE KEY UPDATE
    `descricao` = VALUES(`descricao`);

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
