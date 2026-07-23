-- Execute um destes comandos no phpMyAdmin

-- Opção 1: Remover e recriar com senha simples
DROP USER IF EXISTS 'getxml'@'localhost;
CREATE USER 'getxml'@'localhost IDENTIFIED BY 'gX7#kLp$2Qz!vN9@@@';
GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost;
FLUSH PRIVILEGES;

-- Opção 2: Alterar senha do usuário existente
-- ALTER USER 'getxml'@'localhost IDENTIFIED BY 'gX7#kLp$2Qz!vN9@@@';
-- FLUSH PRIVILEGES;

-- Opção 3: Criar usuário com nome diferente
-- CREATE USER 'getxml_sistema'@'localhost IDENTIFIED BY 'GetXML2024';
-- GRANT ALL PRIVILEGES ON getxml.* TO 'getxml_sistema'@'localhost;
-- FLUSH PRIVILEGES;