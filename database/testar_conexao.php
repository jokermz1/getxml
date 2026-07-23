<?php

/**
 * Script para testar a conexão com o usuário getxml
 */

echo "=== Testando Conexão com Usuário getxml ===\n\n";

$senha = 'gX7#kLp$2Qz!vN9@@@a';

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=getxml;charset=utf8mb4',
        'getxml',
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "✓ Conexão bem-sucedida com usuário getxml\n";
    echo "✓ Senha: $senha\n";

} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "\n";
    echo "\nTentando recriar usuário...\n";
    
    try {
        $pdoRoot = new PDO(
            'mysql:host=localhost;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        
        $pdoRoot->exec("DROP USER IF EXISTS getxml@localhost");
        $pdoRoot->exec("CREATE USER getxml@localhost IDENTIFIED BY '$senha'");
        $pdoRoot->exec("GRANT ALL PRIVILEGES ON getxml.* TO getxml@localhost");
        $pdoRoot->exec("FLUSH PRIVILEGES");
        
        echo "✓ Usuário recriado\n";
        
        // Testa novamente
        $pdo = new PDO(
            'mysql:host=localhost;dbname=getxml;charset=utf8mb4',
            'getxml',
            $senha,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        
        echo "✓ Conexão testada com sucesso!\n";
        
    } catch (Exception $e2) {
        echo "✗ Erro ao recriar usuário: " . $e2->getMessage() . "\n";
    }
}
