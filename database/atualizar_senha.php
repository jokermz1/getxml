<?php

/**
 * Script para atualizar a senha do usuário MySQL
 */

echo "=== Atualizando Senha do Usuário MySQL ===\n\n";

try {
    // Conecta como root
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "✓ Conectado como root\n";

    // Remove usuário existente
    $pdo->exec("DROP USER IF EXISTS getxml@localhost");
    echo "✓ Usuário antigo removido\n";

    // Cria novo usuário com senha simples (sem caracteres especiais problemáticos)
    $senha = 'GetXML2024SenhaSegura';
    $stmt = $pdo->prepare("CREATE USER getxml@localhost IDENTIFIED BY ?");
    $stmt->execute([$senha]);
    echo "✓ Usuário 'getxml' criado com senha simples: $senha\n";

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
    $novasLinhas[] = "DB_PASSWORD=GetXML2024SenhaSegura";
    
    file_put_contents($envFile, implode("\n", $novasLinhas));
    echo "✓ Arquivo .env atualizado\n";

    echo "\n=== Concluído com Sucesso! ===\n";
    echo "\nCredenciais do sistema:\n";
    echo "Usuario: getxml\n";
    echo "Senha: GetXML2024SenhaSegura\n";
    echo "Banco: getxml\n";
    echo "\nAcesse: http://localhost/getxml/public/\n";
    echo "Login: admin@getxml.com / admin123\n";

} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
