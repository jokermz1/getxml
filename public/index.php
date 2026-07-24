<?php

// Iniciar sessão
session_start();

$sefazCaBundleDefault = __DIR__ . '/../certs/icpbrasil_raiz_v10.crt';

// Carregar configurações - com tratamento de erro
try {
    $config = require_once __DIR__ . '/../config/config.php';
} catch (Exception $e) {
    // Fallback para configuração manual se .env falhar
    $config = [
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
    ];
}

// Carregar autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar controllers
use App\Controllers\SefazController;
use App\Controllers\AuthController;

// Inicializar controllers
$sefazController = new SefazController($config);
$authController = new AuthController($config);

// Determinar ação
$action = $_GET['action'] ?? 'home';

// Roteamento simples - temporariamente sem autenticação
switch ($action) {
    case 'login':
        $authController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'dashboard':
        $authController->dashboard();
        break;
    case 'perfil':
        $authController->perfil();
        break;
    case 'upload_certificado':
        $authController->uploadCertificado();
        break;
    case 'remover_certificado':
        $authController->removerCertificado();
        break;
    case 'toggle_certificado':
        $authController->toggleCertificado();
        break;
    case 'alterar_senha':
        $authController->alterarSenha();
        break;
    case 'admin':
        $authController->admin();
        break;
    case 'admin_criar_usuario':
        $authController->adminCriarUsuario();
        break;
    case 'admin_toggle_usuario':
        $authController->adminToggleUsuario();
        break;
    case 'buscar':
        $sefazController->buscar();
        break;
    case 'salvar':
        $sefazController->salvar();
        break;
    case 'listar':
        $sefazController->listar();
        break;
    case 'excluir':
        $sefazController->excluir();
        break;
    case 'config':
        $sefazController->config();
        break;
    case 'home':
    default:
        $sefazController->home();
        break;
}
