<?php

/**
 * Script de Instalação do Banco de Dados
 * Este script cria as tabelas necessárias para o sistema
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Tenta carregar configuração
try {
    $config = require_once __DIR__ . '/../config/config.php';
} catch (Exception $e) {
    // Se falhar, usa configuração padrão com root
    $config = [
        'database' => [
            'host' => 'localhost',
            'database' => 'getxml',
            'username' => 'root',
            'password' => '',
        ]
    ];
}

echo "=== Instalação do Banco de Dados GetXML SEFAZ ===\n\n";

try {
    // Primeiro tenta usar as credenciais do .env
    $dbConfig = $config['database'];
    
    try {
        $dsn = sprintf(
            'mysql:host=%s;charset=utf8mb4',
            $dbConfig['host']
        );

        $pdo = new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        echo "✓ Conectado ao MySQL com usuário: " . $dbConfig['username'] . "\n";
    } catch (PDOException $e) {
        // Se falhar, tenta com root
        echo "⚠ Usuário '" . $dbConfig['username'] . "' não existe. Tentando com root...\n";
        
        try {
            $pdo = new PDO(
                'mysql:host=localhost;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
            echo "✓ Conectado ao MySQL com usuário: root\n";
            
            // Cria o banco de dados
            $pdo->exec("CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Banco de dados 'getxml' criado\n";
            
            // Vamos pular a criação do usuário e focar nas tabelas
            // O usuário será criado manualmente via phpMyAdmin
            echo "⚠ Criando tabelas com usuário root (usuário MySQL será criado manualmente)\n";
            
            // Seleciona o banco
            $pdo->exec("USE getxml");
            
        } catch (PDOException $e2) {
            throw new Exception("Não foi possível conectar nem com root: " . $e2->getMessage());
        }
    }

    // Lê o schema SQL
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    if ($schema === false) {
        throw new Exception('Não foi possível ler o arquivo schema.sql');
    }

    echo "✓ Arquivo schema.sql lido\n";

    // Executa o schema
    $pdo->exec($schema);

    echo "✓ Schema executado com sucesso\n";

    // Verifica se as tabelas foram criadas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Tabelas criadas: " . implode(', ', $tables) . "\n";

    // Verifica usuários criados
    $usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch();
    echo "✓ Usuários criados: " . $usuarios['total'] . "\n";

    echo "\n=== Instalação Concluída com Sucesso! ===\n";
    echo "\nUsuários Padrão:\n";
    echo "- Admin: admin@getxml.com / senha: admin123\n";
    echo "- Contador 1: contador1@teste.com / senha: contador123\n";
    echo "- Contador 2: contador2@teste.com / senha: contador123\n";
    echo "\n⚠️  IMPORTANTE: Altere as senhas padrão em produção!\n";

} catch (Exception $e) {
    echo "✗ Erro na instalação: " . $e->getMessage() . "\n";
    echo "\nVerifique:\n";
    echo "1. Se o MySQL está rodando\n";
    echo "2. Se a senha do root está vazia (padrão XAMPP)\n";
    echo "3. Se o MySQL está aceitando conexões\n";
    exit(1);
}
