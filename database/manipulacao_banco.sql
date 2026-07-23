-- =====================================================
-- SCRIPT DE MANIPULAÇÃO DO BANCO DE DADOS - GETXML SEFAZ
-- =====================================================
-- Este script permite gerenciar o banco de dados do sistema
-- Funciona em produção e homologação, seguindo normas SEFAZ
-- =====================================================

-- SELEÇÃO DE OPERAÇÕES
-- Descomente a operação desejada e execute no seu cliente MySQL
-- (phpMyAdmin, MySQL Workbench, DBeaver, etc.)

-- =====================================================
-- 1. CRIAÇÃO COMPLETA DO BANCO DE DADOS
-- =====================================================

-- 1.1 Criar banco de dados e usuário
DROP DATABASE IF EXISTS getxml;
CREATE DATABASE getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário do banco de dados (ALTERE A SENHA EM PRODUÇÃO!)
DROP USER IF EXISTS 'getxml'@'localhost';
CREATE USER 'getxml'@'localhost' IDENTIFIED BY 'gX7#kLp$2Qz!vN9@@@';

-- Conceder privilégios
GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost';
FLUSH PRIVILEGES;

-- NOTA: Estas credenciais devem corresponder às configurações no arquivo .env:
-- DB_USERNAME=getxml
-- DB_PASSWORD=gX7#kLp$2Qz!vN9@@@

-- 1.2 Selecionar o banco de dados
USE getxml;

-- 1.3 Criar tabelas
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20),
    ie VARCHAR(20),
    papel ENUM('admin', 'contador') DEFAULT 'contador',
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_cnpj (cnpj),
    INDEX idx_papel (papel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    senha_certificado VARCHAR(255) NOT NULL,
    sefaz_uf VARCHAR(2) DEFAULT 'SP',
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notas_fiscais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    chave VARCHAR(44) NOT NULL UNIQUE,
    numero VARCHAR(20),
    serie VARCHAR(5),
    data_emissao DATETIME,
    valor DECIMAL(10, 2),
    cnpj_emitente VARCHAR(20),
    nome_emitente VARCHAR(255),
    caminho_xml VARCHAR(500),
    data_captura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_chave (chave),
    INDEX idx_data_emissao (data_emissao),
    INDEX idx_cnpj_emitente (cnpj_emitente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('sefaz_ambiente', '2', 'Ambiente da SEFAZ (1=Produção, 2=Homologação)'),
('sefaz_uf_padrao', 'SP', 'UF padrão da SEFAZ'),
('max_tamanho_certificado', '10485760', 'Tamanho máximo do certificado em bytes (10MB)'),
('tipos_certificado_permitidos', 'pfx,p12', 'Extensões de certificado permitidas'),
('limite_notas_usuario', '10000', 'Limite de notas por usuário');

-- 1.5 Criar usuário admin padrão (senha: admin123)
-- IMPORTANTE: Altere esta senha em produção!
INSERT INTO usuarios (nome, email, senha, papel) VALUES
('Administrador', 'admin@getxml.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 1.6 Criar usuários de teste (senhas: contador123)
INSERT INTO usuarios (nome, email, senha, cnpj, ie, papel) VALUES
('Contador Teste 1', 'contador1@teste.com', '$2y$10$wR8jXlJqZ3VvYzJlZmJlZOqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK', '12.345.678/0001-90', '123456789', 'contador'),
('Contador Teste 2', 'contador2@teste.com', '$2y$10$wR8jXlJqZ3VvYzJlZmJlZOqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK', '98.765.432/0001-10', '987654321', 'contador');


-- =====================================================
-- 2. OPERAÇÕES DE MANUTENÇÃO
-- =====================================================

-- 2.1 Backup do banco de dados (via linha de comando)
-- mysqldump -u getxml -p getxml > backup_getxml_YYYY-MM-DD.sql

-- 2.2 Restaurar backup (via linha de comando)
-- mysql -u getxml -p getxml < backup_getxml_YYYY-MM-DD.sql

-- 2.3 Limpar todos os dados (manter estrutura)
-- USE getxml;
-- TRUNCATE TABLE notas_fiscais;
-- TRUNCATE TABLE certificados;
-- TRUNCATE TABLE logs_sistema;
-- TRUNCATE TABLE configuracoes;
-- DELETE FROM usuarios WHERE email != 'admin@getxml.com';

-- 2.4 Reset completo (recriar do zero)
-- DROP DATABASE IF EXISTS getxml;
-- Execute a seção 1 novamente


-- =====================================================
-- 3. OPERAÇÕES DE USUÁRIOS
-- =====================================================

-- 3.1 Listar todos os usuários
-- SELECT id, nome, email, cnpj, ie, papel, ativo FROM usuarios;

-- 3.2 Criar novo usuário (contador)
-- INSERT INTO usuarios (nome, email, senha, cnpj, ie, papel) VALUES
-- ('Nome do Contador', 'email@exemplo.com', '$2y$10$hashDaSenhaAqui', '00.000.000/0000-00', '123456789', 'contador');

-- 3.3 Criar novo usuário (admin)
-- INSERT INTO usuarios (nome, email, senha, papel) VALUES
-- ('Nome do Admin', 'admin@exemplo.com', '$2y$10$hashDaSenhaAqui', 'admin');

-- 3.4 Ativar/desativar usuário
-- UPDATE usuarios SET ativo = 0 WHERE id = 1;  -- Desativar
-- UPDATE usuarios SET ativo = 1 WHERE id = 1;  -- Ativar

-- 3.5 Alterar senha do usuário (gere o hash via PHP password_hash)
-- UPDATE usuarios SET senha = '$2y$10$novoHashAqui' WHERE id = 1;

-- 3.6 Alterar papel do usuário
-- UPDATE usuarios SET papel = 'admin' WHERE id = 1;
-- UPDATE usuarios SET papel = 'contador' WHERE id = 1;

-- 3.7 Excluir usuário (cascata para certificados e notas)
-- DELETE FROM usuarios WHERE id = 1;


-- =====================================================
-- 4. OPERAÇÕES DE CERTIFICADOS
-- =====================================================

-- 4.1 Listar certificados por usuário
-- SELECT c.*, u.nome as usuario_nome 
-- FROM certificados c 
-- JOIN usuarios u ON c.usuario_id = u.id;

-- 4.2 Desativar certificado
-- UPDATE certificados SET ativo = 0 WHERE id = 1;

-- 4.3 Ativar certificado
-- UPDATE certificados SET ativo = 1 WHERE id = 1;

-- 4.4 Excluir certificado
-- DELETE FROM certificados WHERE id = 1;


-- =====================================================
-- 5. OPERAÇÕES DE NOTAS FISCAIS
-- =====================================================

-- 5.1 Listar notas fiscais por usuário
-- SELECT nf.*, u.nome as usuario_nome 
-- FROM notas_fiscais nf 
-- JOIN usuarios u ON nf.usuario_id = u.id
-- ORDER BY nf.data_emissao DESC;

-- 5.2 Buscar nota por chave
-- SELECT * FROM notas_fiscais WHERE chave = 'chaveAqui';

-- 5.3 Listar notas por período
-- SELECT * FROM notas_fiscais 
-- WHERE data_emissao BETWEEN '2024-01-01' AND '2024-12-31'
-- ORDER BY data_emissao DESC;

-- 5.4 Contar notas por usuário
-- SELECT usuario_id, COUNT(*) as total_notas 
-- FROM notas_fiscais 
-- GROUP BY usuario_id;

-- 5.5 Excluir nota fiscal
-- DELETE FROM notas_fiscais WHERE id = 1;


-- =====================================================
-- 6. OPERAÇÕES DE LOGS
-- =====================================================

-- 6.1 Visualizar logs recentes
-- SELECT l.*, u.nome as usuario_nome 
-- FROM logs_sistema l 
-- LEFT JOIN usuarios u ON l.usuario_id = u.id
-- ORDER BY l.criado_em DESC 
-- LIMIT 100;

-- 6.2 Limpar logs antigos (manter últimos 30 dias)
-- DELETE FROM logs_sistema 
-- WHERE criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 6.3 Limpar todos os logs
-- TRUNCATE TABLE logs_sistema;


-- =====================================================
-- 7. OPERAÇÕES DE CONFIGURAÇÕES
-- =====================================================

-- 7.1 Listar todas as configurações
-- SELECT * FROM configuracoes;

-- 7.2 Alterar configuração
-- UPDATE configuracoes SET valor = '1' WHERE chave = 'sefaz_ambiente';

-- 7.3 Alterar ambiente SEFAZ (1=Produção, 2=Homologação)
-- UPDATE configuracoes SET valor = '1' WHERE chave = 'sefaz_ambiente';

-- 7.4 Alterar UF padrão
-- UPDATE configuracoes SET valor = 'MG' WHERE chave = 'sefaz_uf_padrao';


-- =====================================================
-- 8. CONSULTAS ÚTEIS
-- =====================================================

-- 8.1 Resumo do sistema
-- SELECT 
--     (SELECT COUNT(*) FROM usuarios) as total_usuarios,
--     (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) as usuarios_ativos,
--     (SELECT COUNT(*) FROM certificados WHERE ativo = 1) as certificados_ativos,
--     (SELECT COUNT(*) FROM notas_fiscais) as total_notas,
--     (SELECT COUNT(*) FROM logs_sistema) as total_logs;

-- 8.2 Notas por mês
-- SELECT 
--     DATE_FORMAT(data_emissao, '%Y-%m') as mes,
--     COUNT(*) as total_notas,
--     SUM(valor) as valor_total
-- FROM notas_fiscais
-- GROUP BY DATE_FORMAT(data_emissao, '%Y-%m')
-- ORDER BY mes DESC;

-- 8.3 Usuários sem certificado
-- SELECT u.id, u.nome, u.email 
-- FROM usuarios u 
-- LEFT JOIN certificados c ON u.id = c.usuario_id
-- WHERE c.id IS NULL AND u.ativo = 1;

-- 8.4 Espaço utilizado por usuário
-- SELECT 
--     u.id,
--     u.nome,
--     u.email,
--     COUNT(nf.id) as total_notas,
--     SUM(nf.valor) as valor_total
-- FROM usuarios u
-- LEFT JOIN notas_fiscais nf ON u.id = nf.usuario_id
-- GROUP BY u.id, u.nome, u.email
-- ORDER BY total_notas DESC;


-- =====================================================
-- 9. AMBIENTE DE PRODUÇÃO VS HOMOLOGAÇÃO
-- =====================================================

-- Para produção, altere a configuração:
-- UPDATE configuracoes SET valor = '1' WHERE chave = 'sefaz_ambiente';

-- Para homologação, altere a configuração:
-- UPDATE configuracoes SET valor = '2' WHERE chave = 'sefaz_ambiente';

-- Verificar ambiente atual:
-- SELECT valor as ambiente, 
--        CASE valor 
--            WHEN '1' THEN 'Produção' 
--            WHEN '2' THEN 'Homologação' 
--        END as descricao
-- FROM configuracoes 
-- WHERE chave = 'sefaz_ambiente';


-- =====================================================
-- 10. SEGURANÇA EM PRODUÇÃO
-- =====================================================

-- 10.1 Alterar senha do admin (gere o hash via PHP)
-- UPDATE usuarios SET senha = '$2y$10$novoHashSeguroAqui' WHERE email = 'admin@getxml.com';

-- 10.2 Remover usuários de teste
-- DELETE FROM usuarios WHERE email LIKE '%@teste.com';

-- 10.3 Alterar senha do banco de dados
-- ALTER USER 'getxml'@'localhost' IDENTIFIED BY 'NovaSenhaSegura123!';
-- FLUSH PRIVILEGES;
-- NOTA: Se alterar a senha aqui, também atualize no arquivo .env

-- 10.4 Verificar usuários ativos
-- SELECT id, nome, email, papel FROM usuarios WHERE ativo = 1;


-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
