INSERT INTO `usuarios` (`perfil_id`, `nome`, `email`, `senha_hash`, `ativo`, `created_at`)
SELECT 1, 'Administrador Geral', 'admin@sisad.local', '$2y$10$AhQ/4WdCt.FR4e7xr0BKMObR2VFnw0zUm1IWS6iBxbehCLwK/hZgu', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `email` = 'admin@sisad.local');
