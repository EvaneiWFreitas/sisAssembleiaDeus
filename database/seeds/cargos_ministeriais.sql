-- Cargos ministeriais iniciais
SET NAMES utf8mb4;
USE `ezyro_43462517_gestao_igreja`;

INSERT INTO `cargos_ministeriais` (`id`, `nome`, `descricao`, `ativo`) VALUES
    (1, 'Pastor', 'Pastor responsável pela congregação ou pelo ministério.', 1),
    (2, 'Evangelista', 'Obreiro dedicado à evangelização.', 1),
    (3, 'Presbítero', 'Obreiro com funções de liderança e aconselhamento.', 1),
    (4, 'Diácono', 'Obreiro responsável por serviços práticos da igreja.', 1),
    (5, 'Diaconisa', 'Obreira responsável por serviços práticos da igreja.', 1),
    (6, 'Missionário', 'Obreiro enviado para campo missionário.', 1),
    (7, 'Missionária', 'Obreira enviada para campo missionário.', 1),
    (8, 'Cooperador', 'Obreiro cooperador nas atividades ministeriais.', 1),
    (9, 'Cooperadora', 'Obreira cooperadora nas atividades ministeriais.', 1),
    (10, 'Auxiliar', 'Obreiro auxiliar nas atividades da congregação.', 1)
ON DUPLICATE KEY UPDATE
    `descricao` = VALUES(`descricao`),
    `ativo` = VALUES(`ativo`);
