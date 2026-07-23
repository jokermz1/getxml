<?php

/**
 * Script de Instalação do Banco de Dados
 * Este script cria as tabelas necessárias para o sistema
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Core\Database;

echo "=== Instalação do Banco de Dados GetXML SEFAZ ===\n\n";

try {
    // Conecta ao MySQL (sem selecionar banco)
    $config = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;charset=utf8mb4',
        $config['host']
    );

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "✓ Conectado ao MySQL\n";

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
    echo "2. Se as credenciais no .env estão corretas\n";
    echo "3. Se o usuário tem permissão para criar banco de dados\n";
    exit(1);
}
