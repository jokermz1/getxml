-- SQL para criar usuário e banco de dados manualmente
-- Execute este SQL no seu cliente MySQL (phpMyAdmin, Workbench, etc.)

-- Cria o banco de dados
CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Cria o usuário
CREATE USER 'getxml'@'localhost' IDENTIFIED BY 'gX7#kLp$2Qz!vN9@@@';

-- Concede privilégios (CORRIGIDO: para 'getxml', não 'usuario')
GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost';

-- Aplica as mudanças
FLUSH PRIVILEGES;
