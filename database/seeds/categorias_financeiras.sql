-- Categorias financeiras básicas
SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

INSERT INTO `categorias_financeiras` (`nome`, `tipo`, `descricao`, `ativo`) VALUES
    ('Dízimos', 'entrada', 'Dízimos recebidos dos membros e contribuintes.', 1),
    ('Ofertas', 'entrada', 'Ofertas recebidas em cultos e campanhas.', 1),
    ('Doações', 'entrada', 'Doações voluntárias e contribuições eventuais.', 1),
    ('Rendimentos', 'entrada', 'Rendimentos de aplicações e outras origens.', 1),
    ('Outros ingressos', 'entrada', 'Entradas não classificadas nas demais categorias.', 1),
    ('Despesas administrativas', 'saida', 'Despesas de administração e escritório.', 1),
    ('Manutenção e conservação', 'saida', 'Despesas com manutenção do patrimônio.', 1),
    ('Missões e evangelização', 'saida', 'Despesas com ações missionárias e evangelísticas.', 1),
    ('Assistência social', 'saida', 'Despesas com ações de assistência e beneficência.', 1),
    ('Eventos e campanhas', 'saida', 'Despesas com eventos, congressos e campanhas.', 1),
    ('Impressos e material', 'saida', 'Despesas com material de impressão e escritório.', 1),
    ('Outros dispêndios', 'saida', 'Saídas não classificadas nas demais categorias.', 1)
ON DUPLICATE KEY UPDATE
    `descricao` = VALUES(`descricao`),
    `ativo` = VALUES(`ativo`);
