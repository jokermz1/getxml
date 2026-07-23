-- Schema do Banco de Dados GetXML SEFAZ
-- Este script cria todas as tabelas necessárias para o sistema

-- Cria o banco de dados se não existir
CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE getxml;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
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

-- Tabela de certificados digitais
CREATE TABLE IF NOT EXISTS certificados (
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

-- Tabela de notas fiscais capturadas
CREATE TABLE IF NOT EXISTS notas_fiscais (
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

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
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

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere configurações padrão
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('sefaz_ambiente', '2', 'Ambiente da SEFAZ (1=Produção, 2=Homologação)'),
('sefaz_uf_padrao', 'SP', 'UF padrão da SEFAZ'),
('max_tamanho_certificado', '10485760', 'Tamanho máximo do certificado em bytes (10MB)'),
('tipos_certificado_permitidos', 'pfx,p12', 'Extensões de certificado permitidas'),
('limite_notas_usuario', '10000', 'Limite de notas por usuário');

-- Cria usuário admin padrão (senha: admin123)
-- Em produção, altere esta senha imediatamente
INSERT INTO usuarios (nome, email, senha, papel) VALUES
('Administrador', 'admin@getxml.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Cria alguns usuários de teste (senhas: contador123)
INSERT INTO usuarios (nome, email, senha, cnpj, ie, papel) VALUES
('Contador Teste 1', 'contador1@teste.com', '$2y$10$wR8jXlJqZ3VvYzJlZmJlZOqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK', '12.345.678/0001-90', '123456789', 'contador'),
('Contador Teste 2', 'contador2@teste.com', '$2y$10$wR8jXlJqZ3VvYzJlZmJlZOqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK', '98.765.432/0001-10', '987654321', 'contador');
