<?php

require_once __DIR__ . '/../vendor/autoload.php';

$sefazCaBundleDefault = __DIR__ . '/../certs/icpbrasil_raiz_v10.crt';

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
            'url' => $_ENV['APP_URL'] ?? 'http://localhost/getxml/public',
            'public_path' => $_ENV['APP_PUBLIC_PATH'] ?? '/public',
        ],
        'sefaz' => [
            'uf' => $_ENV['SEFAZ_UF'] ?? 'SP',
            'ambiente' => $_ENV['SEFAZ_AMBIENTE'] ?? '1',
            'certificado' => $_ENV['SEFAZ_CERTIFICADO'] ?? null, // Opcional - usuário pode fazer upload
            'senha_certificado' => $_ENV['SEFAZ_SENHA_CERTIFICADO'] ?? null, // Opcional - usuário pode fazer upload
            'ca_bundle' => $_ENV['SEFAZ_CA_BUNDLE'] ?? (file_exists($sefazCaBundleDefault) ? $sefazCaBundleDefault : null),
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
            'username' => $_ENV['DB_USERNAME'] ?? 'getxml',
            'password' => $_ENV['DB_PASSWORD'] ?? 'gX7#kLp$2Qz!vN9@@@',
        ],
    ];
} else {
    // Configuração de fallback
    return [
        'app' => [
            'name' => 'GetXML SEFAZ',
            'env' => 'development',
            'debug' => true,
            'url' => 'http://localhost/getxml/public',
            'public_path' => '/public',
        ],
        'sefaz' => [
            'uf' => 'SP',
            'ambiente' => '2',
            'certificado' => '',
            'senha_certificado' => '',
            'ca_bundle' => file_exists($sefazCaBundleDefault) ? $sefazCaBundleDefault : null,
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
            'username' => 'getxml',
            'password' => 'gX7#kLp$2Qz!vN9@@@',
        ],
    ];
}
