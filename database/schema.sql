-- ============================================================
-- sisAssembleiaDeus — Esquema completo do banco de dados
-- Banco: ezyro_43462517_gestao_igreja
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Compatível com MariaDB 10.x / MySQL 5.7+ / MySQL 8.x
-- ============================================================

SET NAMES utf8mb4;
SET default_storage_engine = InnoDB;
SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE IF NOT EXISTS `ezyro_43462517_gestao_igreja`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ezyro_43462517_gestao_igreja`;

-- ------------------------------------------------------------
-- 1. Segurança, usuários e auditoria
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `perfis` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(60) NOT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_perfis_slug` (`slug`),
    KEY `idx_perfis_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `modulo` VARCHAR(60) NOT NULL,
    `acao` VARCHAR(40) NOT NULL,
    `descricao` VARCHAR(180) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissoes_modulo_acao` (`modulo`, `acao`),
    KEY `idx_permissoes_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `perfil_permissao` (
    `perfil_id` INT UNSIGNED NOT NULL,
    `permissao_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`perfil_id`, `permissao_id`),
    KEY `idx_perfil_permissao_permissao` (`permissao_id`),
    CONSTRAINT `fk_perfil_permissao_perfil` FOREIGN KEY (`perfil_id`)
        REFERENCES `perfis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_perfil_permissao_permissao` FOREIGN KEY (`permissao_id`)
        REFERENCES `permissoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `perfil_id` INT UNSIGNED NOT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `senha_hash` VARCHAR(255) NOT NULL,
    `foto` VARCHAR(255) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `ultimo_acesso` DATETIME NULL DEFAULT NULL,
    `tentativas_falhas` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `bloqueado_ate` DATETIME NULL DEFAULT NULL,
    `reset_token_hash` VARCHAR(255) NULL DEFAULT NULL,
    `reset_expira_em` DATETIME NULL DEFAULT NULL,
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuarios_email` (`email`),
    KEY `idx_usuarios_perfil` (`perfil_id`),
    KEY `idx_usuarios_ativo` (`ativo`),
    CONSTRAINT `fk_usuarios_perfil` FOREIGN KEY (`perfil_id`)
        REFERENCES `perfis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessoes` (
    `id` CHAR(64) NOT NULL,
    `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `criada_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expira_em` DATETIME NOT NULL,
    `ultima_atividade` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ativa` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_sessoes_usuario` (`usuario_id`),
    KEY `idx_sessoes_expira` (`expira_em`),
    KEY `idx_sessoes_ativa` (`ativa`),
    CONSTRAINT `fk_sessoes_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auditoria` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
    `acao` VARCHAR(80) NOT NULL,
    `modulo` VARCHAR(60) NOT NULL,
    `entidade` VARCHAR(60) NULL DEFAULT NULL,
    `entidade_id` INT UNSIGNED NULL DEFAULT NULL,
    `dados_anteriores` TEXT NULL,
    `dados_novos` TEXT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_auditoria_usuario` (`usuario_id`),
    KEY `idx_auditoria_modulo` (`modulo`),
    KEY `idx_auditoria_acao` (`acao`),
    KEY `idx_auditoria_entidade` (`entidade`, `entidade_id`),
    KEY `idx_auditoria_created` (`created_at`),
    CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `configuracoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chave` VARCHAR(80) NOT NULL,
    `valor` TEXT NULL,
    `grupo` VARCHAR(50) NOT NULL DEFAULT 'geral',
    `tipo` VARCHAR(20) NOT NULL DEFAULT 'texto',
    `descricao` VARCHAR(180) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_configuracoes_chave` (`chave`),
    KEY `idx_configuracoes_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Estrutura ministerial
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cargos_ministeriais` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(80) NOT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cargos_nome` (`nome`),
    KEY `idx_cargos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `igrejas_matriz` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(150) NOT NULL,
    `cnpj` CHAR(18) NULL DEFAULT NULL,
    `logotipo` VARCHAR(255) NULL DEFAULT NULL,
    `endereco` VARCHAR(200) NULL DEFAULT NULL,
    `numero` VARCHAR(20) NULL DEFAULT NULL,
    `complemento` VARCHAR(100) NULL DEFAULT NULL,
    `bairro` VARCHAR(100) NULL DEFAULT NULL,
    `cidade` VARCHAR(100) NULL DEFAULT NULL,
    `estado` CHAR(2) NULL DEFAULT NULL,
    `cep` CHAR(9) NULL DEFAULT NULL,
    `telefone` VARCHAR(30) NULL DEFAULT NULL,
    `email` VARCHAR(190) NULL DEFAULT NULL,
    `pastor_responsavel_id` INT UNSIGNED NULL DEFAULT NULL,
    `data_fundacao` DATE NULL DEFAULT NULL,
    `situacao` ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_igrejas_cnpj` (`cnpj`),
    KEY `idx_igrejas_nome` (`nome`),
    KEY `idx_igrejas_situacao` (`situacao`),
    KEY `idx_igrejas_pastor` (`pastor_responsavel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `congregacoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(30) NOT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `igreja_matriz_id` INT UNSIGNED NOT NULL,
    `endereco` VARCHAR(200) NULL DEFAULT NULL,
    `numero` VARCHAR(20) NULL DEFAULT NULL,
    `complemento` VARCHAR(100) NULL DEFAULT NULL,
    `bairro` VARCHAR(100) NULL DEFAULT NULL,
    `cidade` VARCHAR(100) NULL DEFAULT NULL,
    `estado` CHAR(2) NULL DEFAULT NULL,
    `cep` CHAR(9) NULL DEFAULT NULL,
    `telefone` VARCHAR(30) NULL DEFAULT NULL,
    `email` VARCHAR(190) NULL DEFAULT NULL,
    `pastor_responsavel_id` INT UNSIGNED NULL DEFAULT NULL,
    `data_abertura` DATE NULL DEFAULT NULL,
    `situacao` ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_congregacoes_codigo` (`codigo`),
    KEY `idx_congregacoes_igreja` (`igreja_matriz_id`),
    KEY `idx_congregacoes_nome` (`nome`),
    KEY `idx_congregacoes_situacao` (`situacao`),
    KEY `idx_congregacoes_pastor` (`pastor_responsavel_id`),
    CONSTRAINT `fk_congregacoes_igreja` FOREIGN KEY (`igreja_matriz_id`)
        REFERENCES `igrejas_matriz` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pontos_pregacao` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `congregacao_id` INT UNSIGNED NOT NULL,
    `identificacao` VARCHAR(150) NOT NULL,
    `endereco` VARCHAR(200) NULL DEFAULT NULL,
    `numero` VARCHAR(20) NULL DEFAULT NULL,
    `bairro` VARCHAR(100) NULL DEFAULT NULL,
    `cidade` VARCHAR(100) NULL DEFAULT NULL,
    `estado` CHAR(2) NULL DEFAULT NULL,
    `cep` CHAR(9) NULL DEFAULT NULL,
    `responsavel` VARCHAR(150) NULL DEFAULT NULL,
    `dias_horarios` VARCHAR(255) NULL DEFAULT NULL,
    `data_abertura` DATE NULL DEFAULT NULL,
    `situacao` ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pontos_congregacao` (`congregacao_id`),
    KEY `idx_pontos_situacao` (`situacao`),
    KEY `idx_pontos_identificacao` (`identificacao`),
    CONSTRAINT `fk_pontos_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Membros e obreiros
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `membros` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(30) NOT NULL,
    `nome` VARCHAR(180) NOT NULL,
    `foto` VARCHAR(255) NULL DEFAULT NULL,
    `cpf` CHAR(14) NULL DEFAULT NULL,
    `rg` VARCHAR(30) NULL DEFAULT NULL,
    `data_nascimento` DATE NULL DEFAULT NULL,
    `sexo` ENUM('masculino','feminino','outro','nao_informado') NOT NULL DEFAULT 'nao_informado',
    `estado_civil` ENUM('solteiro','casado','divorciado','viuvo','outro','nao_informado') NOT NULL DEFAULT 'nao_informado',
    `telefone` VARCHAR(30) NULL DEFAULT NULL,
    `whatsapp` VARCHAR(30) NULL DEFAULT NULL,
    `email` VARCHAR(190) NULL DEFAULT NULL,
    `endereco` VARCHAR(200) NULL DEFAULT NULL,
    `numero` VARCHAR(20) NULL DEFAULT NULL,
    `complemento` VARCHAR(100) NULL DEFAULT NULL,
    `bairro` VARCHAR(100) NULL DEFAULT NULL,
    `cidade` VARCHAR(100) NULL DEFAULT NULL,
    `estado` CHAR(2) NULL DEFAULT NULL,
    `cep` CHAR(9) NULL DEFAULT NULL,
    `data_ingresso` DATE NULL DEFAULT NULL,
    `igreja_origem_id` INT UNSIGNED NULL DEFAULT NULL,
    `congregacao_id` INT UNSIGNED NULL DEFAULT NULL,
    `data_batismo` DATE NULL DEFAULT NULL,
    `situacao` ENUM('ativo','inativo','transferido','falecido') NOT NULL DEFAULT 'ativo',
    `observacoes` TEXT NULL,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_membros_codigo` (`codigo`),
    UNIQUE KEY `uq_membros_cpf` (`cpf`),
    KEY `idx_membros_nome` (`nome`),
    KEY `idx_membros_email` (`email`),
    KEY `idx_membros_situacao` (`situacao`),
    KEY `idx_membros_congregacao` (`congregacao_id`),
    KEY `idx_membros_igreja_origem` (`igreja_origem_id`),
    KEY `idx_membros_nascimento` (`data_nascimento`),
    KEY `idx_membros_ingresso` (`data_ingresso`),
    KEY `idx_membros_deleted` (`deleted_at`),
    CONSTRAINT `fk_membros_igreja_origem` FOREIGN KEY (`igreja_origem_id`)
        REFERENCES `igrejas_matriz` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_membros_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `obreiros` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(30) NOT NULL,
    `membro_id` INT UNSIGNED NULL DEFAULT NULL,
    `nome` VARCHAR(180) NOT NULL,
    `foto` VARCHAR(255) NULL DEFAULT NULL,
    `cpf` CHAR(14) NULL DEFAULT NULL,
    `rg` VARCHAR(30) NULL DEFAULT NULL,
    `data_nascimento` DATE NULL DEFAULT NULL,
    `telefone` VARCHAR(30) NULL DEFAULT NULL,
    `whatsapp` VARCHAR(30) NULL DEFAULT NULL,
    `email` VARCHAR(190) NULL DEFAULT NULL,
    `endereco` VARCHAR(200) NULL DEFAULT NULL,
    `numero` VARCHAR(20) NULL DEFAULT NULL,
    `complemento` VARCHAR(100) NULL DEFAULT NULL,
    `bairro` VARCHAR(100) NULL DEFAULT NULL,
    `cidade` VARCHAR(100) NULL DEFAULT NULL,
    `estado` CHAR(2) NULL DEFAULT NULL,
    `cep` CHAR(9) NULL DEFAULT NULL,
    `igreja_origem_id` INT UNSIGNED NULL DEFAULT NULL,
    `congregacao_id` INT UNSIGNED NOT NULL,
    `cargo_id` INT UNSIGNED NOT NULL,
    `data_consagracao` DATE NULL DEFAULT NULL,
    `registro_ministerial` VARCHAR(50) NULL DEFAULT NULL,
    `situacao` ENUM('ativo','licenciado','desligado') NOT NULL DEFAULT 'ativo',
    `observacoes` TEXT NULL,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_obreiros_codigo` (`codigo`),
    UNIQUE KEY `uq_obreiros_cpf` (`cpf`),
    UNIQUE KEY `uq_obreiros_registro` (`registro_ministerial`),
    UNIQUE KEY `uq_obreiros_membro` (`membro_id`),
    KEY `idx_obreiros_nome` (`nome`),
    KEY `idx_obreiros_congregacao` (`congregacao_id`),
    KEY `idx_obreiros_cargo` (`cargo_id`),
    KEY `idx_obreiros_situacao` (`situacao`),
    KEY `idx_obreiros_consagracao` (`data_consagracao`),
    KEY `idx_obreiros_igreja_origem` (`igreja_origem_id`),
    KEY `idx_obreiros_deleted` (`deleted_at`),
    CONSTRAINT `fk_obreiros_membro` FOREIGN KEY (`membro_id`)
        REFERENCES `membros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_obreiros_igreja_origem` FOREIGN KEY (`igreja_origem_id`)
        REFERENCES `igrejas_matriz` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_obreiros_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_obreiros_cargo` FOREIGN KEY (`cargo_id`)
        REFERENCES `cargos_ministeriais` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `obreiros_historico` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `obreiro_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('cargo','transferencia','situacao','consagracao','observacao') NOT NULL,
    `cargo_anterior_id` INT UNSIGNED NULL DEFAULT NULL,
    `cargo_novo_id` INT UNSIGNED NULL DEFAULT NULL,
    `congregacao_anterior_id` INT UNSIGNED NULL DEFAULT NULL,
    `congregacao_nova_id` INT UNSIGNED NULL DEFAULT NULL,
    `situacao_anterior` VARCHAR(30) NULL DEFAULT NULL,
    `situacao_nova` VARCHAR(30) NULL DEFAULT NULL,
    `data_historico` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `descricao` TEXT NULL,
    `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_hist_obreiro` (`obreiro_id`),
    KEY `idx_hist_tipo` (`tipo`),
    KEY `idx_hist_data` (`data_historico`),
    CONSTRAINT `fk_hist_obreiro` FOREIGN KEY (`obreiro_id`)
        REFERENCES `obreiros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_cargo_anterior` FOREIGN KEY (`cargo_anterior_id`)
        REFERENCES `cargos_ministeriais` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_cargo_novo` FOREIGN KEY (`cargo_novo_id`)
        REFERENCES `cargos_ministeriais` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_cong_anterior` FOREIGN KEY (`congregacao_anterior_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_cong_nova` FOREIGN KEY (`congregacao_nova_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `arquivo_morto` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `obreiro_id` INT UNSIGNED NOT NULL,
    `motivo` TEXT NOT NULL,
    `data_desligamento` DATE NOT NULL,
    `responsavel_id` INT UNSIGNED NULL DEFAULT NULL,
    `observacoes` TEXT NULL,
    `restaurado_em` DATETIME NULL DEFAULT NULL,
    `restaurado_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_arquivo_obreiro` (`obreiro_id`),
    KEY `idx_arquivo_data` (`data_desligamento`),
    KEY `idx_arquivo_responsavel` (`responsavel_id`),
    CONSTRAINT `fk_arquivo_obreiro` FOREIGN KEY (`obreiro_id`)
        REFERENCES `obreiros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_arquivo_responsavel` FOREIGN KEY (`responsavel_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_arquivo_restaurador` FOREIGN KEY (`restaurado_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Batismos e consagrações
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `batismos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `membro_id` INT UNSIGNED NOT NULL,
    `data_batismo` DATE NOT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `pastor_celebrante_id` INT UNSIGNED NULL DEFAULT NULL,
    `numero_certificado` VARCHAR(60) NULL DEFAULT NULL,
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_batismos_certificado` (`numero_certificado`),
    KEY `idx_batismos_membro` (`membro_id`),
    KEY `idx_batismos_data` (`data_batismo`),
    KEY `idx_batismos_pastor` (`pastor_celebrante_id`),
    CONSTRAINT `fk_batismos_membro` FOREIGN KEY (`membro_id`)
        REFERENCES `membros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_batismos_pastor` FOREIGN KEY (`pastor_celebrante_id`)
        REFERENCES `obreiros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `consagracoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `obreiro_id` INT UNSIGNED NOT NULL,
    `cargo_id` INT UNSIGNED NOT NULL,
    `data_consagracao` DATE NOT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `pastor_presidente_id` INT UNSIGNED NULL DEFAULT NULL,
    `numero_diploma` VARCHAR(60) NULL DEFAULT NULL,
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_consagracoes_diploma` (`numero_diploma`),
    KEY `idx_consagracoes_obreiro` (`obreiro_id`),
    KEY `idx_consagracoes_cargo` (`cargo_id`),
    KEY `idx_consagracoes_data` (`data_consagracao`),
    KEY `idx_consagracoes_presidente` (`pastor_presidente_id`),
    CONSTRAINT `fk_consagracoes_obreiro` FOREIGN KEY (`obreiro_id`)
        REFERENCES `obreiros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_consagracoes_cargo` FOREIGN KEY (`cargo_id`)
        REFERENCES `cargos_ministeriais` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_consagracoes_presidente` FOREIGN KEY (`pastor_presidente_id`)
        REFERENCES `obreiros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Financeiro
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(100) NOT NULL,
    `tipo` ENUM('entrada','saida') NOT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categorias_nome_tipo` (`nome`, `tipo`),
    KEY `idx_categorias_tipo` (`tipo`),
    KEY `idx_categorias_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movimentacoes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo` ENUM('entrada','saida') NOT NULL,
    `categoria_id` INT UNSIGNED NOT NULL,
    `congregacao_id` INT UNSIGNED NULL DEFAULT NULL,
    `descricao` VARCHAR(255) NOT NULL,
    `valor` DECIMAL(12,2) NOT NULL,
    `data_movimentacao` DATE NOT NULL,
    `forma_pagamento` ENUM('dinheiro','pix','boleto','cartao','transferencia','cheque','outro') NOT NULL DEFAULT 'dinheiro',
    `referencia_mes` CHAR(7) NULL DEFAULT NULL,
    `competencia` CHAR(7) NOT NULL,
    `responsavel_id` INT UNSIGNED NULL DEFAULT NULL,
    `observacoes` TEXT NULL,
    `fechado` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mov_categoria` (`categoria_id`),
    KEY `idx_mov_congregacao` (`congregacao_id`),
    KEY `idx_mov_responsavel` (`responsavel_id`),
    KEY `idx_mov_data` (`data_movimentacao`),
    KEY `idx_mov_competencia` (`competencia`),
    KEY `idx_mov_tipo` (`tipo`),
    KEY `idx_mov_fechado` (`fechado`),
    CONSTRAINT `fk_mov_categoria` FOREIGN KEY (`categoria_id`)
        REFERENCES `categorias_financeiras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_mov_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_mov_responsavel` FOREIGN KEY (`responsavel_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dizimos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movimentacao_id` INT UNSIGNED NOT NULL,
    `membro_id` INT UNSIGNED NULL DEFAULT NULL,
    `congregacao_id` INT UNSIGNED NULL DEFAULT NULL,
    `referencia_mes` CHAR(7) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dizimos_movimentacao` (`movimentacao_id`),
    KEY `idx_dizimos_membro` (`membro_id`),
    KEY `idx_dizimos_congregacao` (`congregacao_id`),
    KEY `idx_dizimos_referencia` (`referencia_mes`),
    CONSTRAINT `fk_dizimos_movimentacao` FOREIGN KEY (`movimentacao_id`)
        REFERENCES `movimentacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dizimos_membro` FOREIGN KEY (`membro_id`)
        REFERENCES `membros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_dizimos_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ofertas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movimentacao_id` INT UNSIGNED NOT NULL,
    `ofertante_id` INT UNSIGNED NULL DEFAULT NULL,
    `tipo_oferta` VARCHAR(100) NULL DEFAULT NULL,
    `congregacao_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ofertas_movimentacao` (`movimentacao_id`),
    KEY `idx_ofertas_ofertante` (`ofertante_id`),
    KEY `idx_ofertas_tipo` (`tipo_oferta`),
    KEY `idx_ofertas_congregacao` (`congregacao_id`),
    CONSTRAINT `fk_ofertas_movimentacao` FOREIGN KEY (`movimentacao_id`)
        REFERENCES `movimentacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ofertas_ofertante` FOREIGN KEY (`ofertante_id`)
        REFERENCES `membros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ofertas_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fechamentos_mensais` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `competencia` CHAR(7) NOT NULL,
    `congregacao_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_entradas` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `total_saidas` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `saldo` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `fechado_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `fechado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `observacoes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fechamento_competencia_cong` (`competencia`, `congregacao_id`),
    KEY `idx_fechamento_fechado_por` (`fechado_por_id`),
    CONSTRAINT `fk_fechamento_usuario` FOREIGN KEY (`fechado_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Conteúdo público
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `eventos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `descricao` TEXT NULL,
    `tipo` ENUM('culto','congresso','festividade','campanha','conferencia','reuniao','escola_biblica','especial') NOT NULL DEFAULT 'culto',
    `data_inicio` DATETIME NOT NULL,
    `data_fim` DATETIME NULL DEFAULT NULL,
    `horario` VARCHAR(50) NULL DEFAULT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `responsavel_id` INT UNSIGNED NULL DEFAULT NULL,
    `foto` VARCHAR(255) NULL DEFAULT NULL,
    `publicado` TINYINT(1) NOT NULL DEFAULT 0,
    `publicado_em` DATETIME NULL DEFAULT NULL,
    `destaque` TINYINT(1) NOT NULL DEFAULT 0,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_eventos_slug` (`slug`),
    KEY `idx_eventos_data` (`data_inicio`),
    KEY `idx_eventos_publicado` (`publicado`),
    KEY `idx_eventos_tipo` (`tipo`),
    KEY `idx_eventos_responsavel` (`responsavel_id`),
    KEY `idx_eventos_deleted` (`deleted_at`),
    CONSTRAINT `fk_eventos_responsavel` FOREIGN KEY (`responsavel_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `noticias` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `resumo` VARCHAR(500) NULL DEFAULT NULL,
    `conteudo` TEXT NULL,
    `imagem` VARCHAR(255) NULL DEFAULT NULL,
    `publicada` TINYINT(1) NOT NULL DEFAULT 0,
    `publicada_em` DATETIME NULL DEFAULT NULL,
    `autor_id` INT UNSIGNED NULL DEFAULT NULL,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_noticias_slug` (`slug`),
    KEY `idx_noticias_publicada` (`publicada`),
    KEY `idx_noticias_publicada_em` (`publicada_em`),
    KEY `idx_noticias_autor` (`autor_id`),
    KEY `idx_noticias_deleted` (`deleted_at`),
    CONSTRAINT `fk_noticias_autor` FOREIGN KEY (`autor_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `galeria` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(150) NULL DEFAULT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `imagem` VARCHAR(255) NOT NULL,
    `ordem` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_galeria_ativo` (`ativo`),
    KEY `idx_galeria_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programacao_cultos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `congregacao_id` INT UNSIGNED NULL DEFAULT NULL,
    `dia_semana` ENUM('domingo','segunda','terca','quarta','quinta','sexta','sabado') NOT NULL,
    `horario` TIME NOT NULL,
    `tipo` VARCHAR(80) NULL DEFAULT NULL,
    `descricao` VARCHAR(255) NULL DEFAULT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `ordem` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_programacao_congregacao` (`congregacao_id`),
    KEY `idx_programacao_dia` (`dia_semana`),
    KEY `idx_programacao_ativo` (`ativo`),
    CONSTRAINT `fk_programacao_congregacao` FOREIGN KEY (`congregacao_id`)
        REFERENCES `congregacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `palavra_pastoral` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(180) NOT NULL,
    `texto` TEXT NULL,
    `autor_id` INT UNSIGNED NULL DEFAULT NULL,
    `foto` VARCHAR(255) NULL DEFAULT NULL,
    `publicada` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_palavra_publicada` (`publicada`),
    CONSTRAINT `fk_palavra_autor` FOREIGN KEY (`autor_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `versiculos_destaque` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `referencia` VARCHAR(120) NOT NULL,
    `texto` TEXT NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `ordem` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_versiculo_ativo` (`ativo`),
    KEY `idx_versiculo_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contato_mensagens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `telefone` VARCHAR(30) NULL DEFAULT NULL,
    `assunto` VARCHAR(180) NULL DEFAULT NULL,
    `mensagem` TEXT NOT NULL,
    `lida` TINYINT(1) NOT NULL DEFAULT 0,
    `respondida_em` DATETIME NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contato_lida` (`lida`),
    KEY `idx_contato_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. Documentos, diplomas e carteirinhas
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `convites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `numero` VARCHAR(60) NOT NULL,
    `convidado` VARCHAR(180) NOT NULL,
    `igreja_convidada` VARCHAR(180) NULL DEFAULT NULL,
    `endereco` TEXT NULL,
    `nome_evento` VARCHAR(180) NOT NULL,
    `data_evento` DATETIME NOT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `pastor_responsavel` VARCHAR(180) NULL DEFAULT NULL,
    `assinaturas` TEXT NULL,
    `modelo` VARCHAR(60) NOT NULL DEFAULT 'classico',
    `emitido_em` DATETIME NULL DEFAULT NULL,
    `emitido_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_convites_numero` (`numero`),
    KEY `idx_convites_data` (`data_evento`),
    KEY `idx_convites_emitido_por` (`emitido_por_id`),
    CONSTRAINT `fk_convites_usuario` FOREIGN KEY (`emitido_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documentos_emitidos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo` ENUM('carta_convite','diploma_batismo','diploma_consagracao','carteirinha','ficha_membro','ficha_obreiro') NOT NULL,
    `referencia_id` INT UNSIGNED NULL DEFAULT NULL,
    `numero` VARCHAR(60) NOT NULL,
    `modelo` VARCHAR(60) NULL DEFAULT NULL,
    `dados_json` TEXT NULL,
    `arquivo_pdf` VARCHAR(255) NULL DEFAULT NULL,
    `emitido_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `emitido_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `validado_em` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_documentos_numero` (`numero`),
    KEY `idx_documentos_tipo_ref` (`tipo`, `referencia_id`),
    KEY `idx_documentos_emitido_em` (`emitido_em`),
    KEY `idx_documentos_emitido_por` (`emitido_por_id`),
    CONSTRAINT `fk_documentos_usuario` FOREIGN KEY (`emitido_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diplomas_batismo` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batismo_id` INT UNSIGNED NOT NULL,
    `batizado_nome` VARCHAR(180) NOT NULL,
    `data_batismo` DATE NOT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `pastor_celebrante` VARCHAR(180) NULL DEFAULT NULL,
    `numero_certificado` VARCHAR(60) NOT NULL,
    `assinaturas` TEXT NULL,
    `modelo` VARCHAR(60) NOT NULL DEFAULT 'classico',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_diploma_batismo_batismo` (`batismo_id`),
    UNIQUE KEY `uq_diploma_batismo_numero` (`numero_certificado`),
    KEY `idx_diploma_batismo_data` (`data_batismo`),
    CONSTRAINT `fk_diploma_batismo_batismo` FOREIGN KEY (`batismo_id`)
        REFERENCES `batismos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diplomas_consagracao` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consagracao_id` INT UNSIGNED NOT NULL,
    `obreiro_nome` VARCHAR(180) NOT NULL,
    `cargo_ministerial` VARCHAR(80) NOT NULL,
    `data_consagracao` DATE NOT NULL,
    `local` VARCHAR(200) NULL DEFAULT NULL,
    `pastor_presidente` VARCHAR(180) NULL DEFAULT NULL,
    `numero_diploma` VARCHAR(60) NOT NULL,
    `assinaturas` TEXT NULL,
    `modelo` VARCHAR(60) NOT NULL DEFAULT 'classico',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_diploma_consagracao_consagracao` (`consagracao_id`),
    UNIQUE KEY `uq_diploma_consagracao_numero` (`numero_diploma`),
    KEY `idx_diploma_consagracao_data` (`data_consagracao`),
    CONSTRAINT `fk_diploma_consagracao_consagracao` FOREIGN KEY (`consagracao_id`)
        REFERENCES `consagracoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `carteirinhas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipo` ENUM('membro','obreiro') NOT NULL,
    `referencia_id` INT UNSIGNED NOT NULL,
    `modelo` ENUM('classico','moderno','horizontal','vertical','institucional','qrcode') NOT NULL DEFAULT 'classico',
    `codigo_validacao` VARCHAR(64) NOT NULL,
    `data_emissao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `validade` DATE NULL DEFAULT NULL,
    `emitida_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `arquivo_pdf` VARCHAR(255) NULL DEFAULT NULL,
    `segunda_via` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_carteirinhas_codigo` (`codigo_validacao`),
    KEY `idx_carteirinhas_tipo_ref` (`tipo`, `referencia_id`),
    KEY `idx_carteirinhas_emissao` (`data_emissao`),
    KEY `idx_carteirinhas_emitida_por` (`emitida_por_id`),
    CONSTRAINT `fk_carteirinhas_usuario` FOREIGN KEY (`emitida_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. LGPD
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `lgpd_consentimentos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `titular_tipo` ENUM('membro','obreiro','contato') NOT NULL,
    `titular_id` INT UNSIGNED NOT NULL,
    `finalidade` VARCHAR(180) NOT NULL,
    `consentido` TINYINT(1) NOT NULL DEFAULT 0,
    `data_consentimento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lgpd_titular` (`titular_tipo`, `titular_id`),
    KEY `idx_lgpd_finalidade` (`finalidade`),
    KEY `idx_lgpd_data` (`data_consentimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. Backup
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome_arquivo` VARCHAR(180) NOT NULL,
    `tamanho_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `tabelas` INT UNSIGNED NOT NULL DEFAULT 0,
    `duracao_ms` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('gerando','concluido','falhado') NOT NULL DEFAULT 'gerando',
    `observacoes` VARCHAR(255) NULL DEFAULT NULL,
    `gerado_por_id` INT UNSIGNED NULL DEFAULT NULL,
    `gerado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_backups_nome_arquivo` (`nome_arquivo`),
    KEY `idx_backups_gerado_em` (`gerado_em`),
    KEY `idx_backups_status` (`status`),
    KEY `idx_backups_usuario` (`gerado_por_id`),
    CONSTRAINT `fk_backups_usuario` FOREIGN KEY (`gerado_por_id`)
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. Chaves estrangeiras circulares (adicionadas após todas as tabelas)
--     Igreja/Congregação -> Obreiro responsável e Obreiro -> Congregação
-- ------------------------------------------------------------

SET @fk_igreja_pastor = (
    SELECT COUNT(*) FROM `information_schema`.`table_constraints`
    WHERE `constraint_schema` = 'ezyro_43462517_gestao_igreja'
      AND `table_name` = 'igrejas_matriz'
      AND `constraint_name` = 'fk_igrejas_pastor_responsavel'
);
SET @sql_fk_igreja = IF(@fk_igreja_pastor = 0,
    'ALTER TABLE `igrejas_matriz` ADD CONSTRAINT `fk_igrejas_pastor_responsavel` FOREIGN KEY (`pastor_responsavel_id`) REFERENCES `obreiros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE `stmt_fk_igreja` FROM @sql_fk_igreja;
EXECUTE `stmt_fk_igreja`;
DEALLOCATE PREPARE `stmt_fk_igreja`;

SET @fk_congregacao_pastor = (
    SELECT COUNT(*) FROM `information_schema`.`table_constraints`
    WHERE `constraint_schema` = 'ezyro_43462517_gestao_igreja'
      AND `table_name` = 'congregacoes'
      AND `constraint_name` = 'fk_congregacoes_pastor_responsavel'
);
SET @sql_fk_congregacao = IF(@fk_congregacao_pastor = 0,
    'ALTER TABLE `congregacoes` ADD CONSTRAINT `fk_congregacoes_pastor_responsavel` FOREIGN KEY (`pastor_responsavel_id`) REFERENCES `obreiros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE `stmt_fk_congregacao` FROM @sql_fk_congregacao;
EXECUTE `stmt_fk_congregacao`;
DEALLOCATE PREPARE `stmt_fk_congregacao`;

-- ============================================================
-- Fim do esquema.
-- Após importar este arquivo, importe os seeds na ordem:
--   database/seeds/perfis.sql
--   database/seeds/permissoes.sql
--   database/seeds/perfil_permissao.sql
--   database/seeds/cargos_ministeriais.sql
--   database/seeds/categorias_financeiras.sql
--   database/seeds/configuracoes.sql
-- ============================================================
