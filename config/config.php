<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Tenta carregar o .env, mas usa fallback se falhar
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    $usarEnv = true;
} catch (Exception $e) {
    // Se .env falhar, usa valores padrão
    $usarEnv = false;
}

if ($usarEnv) {
    return [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'GetXML_SEFAZ',
            'env' => $_ENV['APP_ENV'] ?? 'production',
            'debug' => $_ENV['APP_DEBUG'] ?? false,
        ],
        'sefaz' => [
            'uf' => $_ENV['SEFAZ_UF'] ?? 'SP',
            'ambiente' => $_ENV['SEFAZ_AMBIENTE'] ?? '1',
            'certificado' => $_ENV['SEFAZ_CERTIFICADO'] ?? '',
            'senha_certificado' => $_ENV['SEFAZ_SENHA_CERTIFICADO'] ?? '',
        ],
        'cnpj' => [
            'cnpj' => $_ENV['CNPJ_CNPJ'] ?? '',
            'ie' => $_ENV['CNPJ_IE'] ?? '',
        ],
        'periodo' => [
            'data_inicio' => $_ENV['DATA_INICIO'] ?? null,
            'data_fim' => $_ENV['DATA_FIM'] ?? null,
        ],
        'storage' => [
            'path' => $_ENV['STORAGE_PATH'] ?? 'storage/xmls',
        ],
        'database' => [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_DATABASE'] ?? 'getxml',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ],
    ];
} else {
    // Configuração de fallback
    return [
        'app' => [
            'name' => 'GetXML SEFAZ',
            'env' => 'development',
            'debug' => true,
        ],
        'sefaz' => [
            'uf' => 'SP',
            'ambiente' => '2',
            'certificado' => '',
            'senha_certificado' => '',
        ],
        'cnpj' => [
            'cnpj' => '',
            'ie' => '',
        ],
        'periodo' => [
            'data_inicio' => null,
            'data_fim' => null,
        ],
        'storage' => [
            'path' => 'storage/xmls',
        ],
        'database' => [
            'host' => 'localhost',
            'database' => 'getxml',
            'username' => 'root',
            'password' => '',
        ],
    ];
}
