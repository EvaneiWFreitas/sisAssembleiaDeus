SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

UPDATE `configuracoes` SET `descricao` = 'Endereço da Igreja Matriz.' WHERE `chave` = 'ministerio_endereco';
UPDATE `configuracoes` SET `descricao` = 'CNPJ do ministério, quando aplicável.' WHERE `chave` = 'ministerio_cnpj';
UPDATE `configuracoes` SET `descricao` = 'Cor primária da identidade visual.' WHERE `chave` = 'cor_primaria';
UPDATE `configuracoes` SET `descricao` = 'Cor secundária da identidade visual.' WHERE `chave` = 'cor_secundaria';
UPDATE `configuracoes` SET `descricao` = 'Cor de fundo das páginas.' WHERE `chave` = 'cor_fundo';
UPDATE `configuracoes` SET `descricao` = 'Número do endereço.' WHERE `chave` = 'ministerio_numero';
UPDATE `configuracoes` SET `descricao` = 'Complemento do endereço.' WHERE `chave` = 'ministerio_complemento';
UPDATE `configuracoes` SET `descricao` = 'Link da página no Facebook.' WHERE `chave` = 'redes_facebook';
UPDATE `configuracoes` SET `descricao` = 'Referência do versículo em destaque.' WHERE `chave` = 'versiculo_referencia';
UPDATE `configuracoes` SET `descricao` = 'Texto do versículo em destaque.' WHERE `chave` = 'versiculo_texto';
UPDATE `configuracoes` SET `descricao` = 'Título da palavra pastoral.' WHERE `chave` = 'palavra_pastoral_titulo';
UPDATE `configuracoes` SET `descricao` = 'Mensagem exibida no rodapé institucional.' WHERE `chave` = 'mensagem_rodape';
UPDATE `configuracoes` SET `descricao` = 'URL pública de acesso ao sistema.' WHERE `chave` = 'url_sistema';
UPDATE `configuracoes` SET `descricao` = 'Prazo de retenção dos registros de auditoria, em anos.' WHERE `chave` = 'prazo_retencao_auditoria_anos';
UPDATE `configuracoes` SET `descricao` = 'Tamanho máximo permitido por arquivo enviado, em MB.' WHERE `chave` = 'tamanho_max_upload_mb';
