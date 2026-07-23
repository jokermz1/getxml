<?php

/**
 * Script simples para resolver o problema do usuário MySQL
 * Remove o usuário existente e cria um novo com senha simples
 *
 * NOTA: Este script requer privilégios administrativos no MySQL.
 * Em produção, use o script SQL database/manipulacao_banco.sql
 * ou conecte com um usuário que tenha privilégios para criar usuários.
 */

echo "=== Resolvendo Problema do Usuário MySQL ===\n\n";

try {
    // Conecta com usuário administrativo (ALTERE se necessário)
    // Em produção, use credenciais apropriadas com privilégios de CREATE USER
    // NOTA: Este script usa root apenas temporariamente para criar o usuário getxml
    // Depois que o usuário getxml for criado, o sistema usará apenas getxml
    $adminUser = 'root'; // Altere para seu usuário admin se necessário
    $adminPass = '';      // Altere para a senha do seu usuário admin

    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        $adminUser,
        $adminPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "✓ Conectado como $adminUser\n";

    // Remove usuário existente (sem aspas no nome)
    $pdo->exec("DROP USER IF EXISTS getxml@localhost");
    echo "✓ Usuário antigo removido\n";

    // Cria novo usuário com senha simples (sem aspas)
    $senha = 'GetXML2024Sistema';
    $stmt = $pdo->prepare("CREATE USER getxml@localhost IDENTIFIED BY ?");
    $stmt->execute([$senha]);
    echo "✓ Novo usuário getxml criado (senha: GetXML2024Sistema)\n";

    // Concede privilégios
    $pdo->exec("GRANT ALL PRIVILEGES ON getxml.* TO getxml@localhost");
    $pdo->exec("FLUSH PRIVILEGES");
    echo "✓ Privilégios concedidos\n";

    // Atualiza o .env
    $envFile = __DIR__ . '/../.env';
    $envContent = file_get_contents($envFile);
    
    // Remove linhas antigas de DB e adiciona novas
    $linhas = explode("\n", $envContent);
    $novasLinhas = [];
    
    foreach ($linhas as $linha) {
        if (strpos($linha, 'DB_') === 0) {
            continue; // Remove linhas antigas de DB
        }
        $novasLinhas[] = $linha;
    }
    
    // Adiciona novas configurações
    $novasLinhas[] = "DB_HOST=localhost";
    $novasLinhas[] = "DB_DATABASE=getxml";
    $novasLinhas[] = "DB_USERNAME=getxml";
    $novasLinhas[] = "DB_PASSWORD=gX7#kLp$2Qz!vN9@@@";
    
    file_put_contents($envFile, implode("\n", $novasLinhas));
    echo "✓ Arquivo .env atualizado\n";

    echo "\n=== Resolvido com Sucesso! ===\n";
    echo "\nCredenciais do sistema:\n";
    echo "Usuario: getxml\n";
    echo "Senha: gX7#kLp$2Qz!vN9@@@\n";
    echo "Banco: getxml\n";
    echo "\nAcesse: http://localhost/getxml/public/\n";
    echo "Login: admin@getxml.com / admin123\n";

} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    echo "\nDICA: Se não tiver acesso como root, use o script SQL database/manipulacao_banco.sql\n";
    echo "ou conecte ao phpMyAdmin/MySQL Workbench com seu usuário administrativo.\n";
    exit(1);
}
