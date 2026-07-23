<?php

/**
 * Script para testar a conexão com o usuário getxml
 */

echo "=== Testando Conexão com Usuário getxml ===\n\n";

$senha = 'gX7#kLp$2Qz!vN9@@@';

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
    echo "\nDICA: Use o script SQL database/manipulacao_banco.sql para criar o usuário\n";
    echo "ou conecte ao phpMyAdmin/MySQL Workbench com seu usuário administrativo.\n";
}
