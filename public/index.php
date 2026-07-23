<?php

// Iniciar sessão
session_start();

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

// Roteamento simples
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
        $authController->requireLogin();
        $sefazController->buscar();
        break;
    case 'salvar':
        $authController->requireLogin();
        $sefazController->salvar();
        break;
    case 'listar':
        $authController->requireLogin();
        $sefazController->listar();
        break;
    case 'excluir':
        $authController->requireLogin();
        $sefazController->excluir();
        break;
    case 'config':
        $authController->requireLogin();
        $sefazController->config();
        break;
    case 'home':
    default:
        // Se não estiver logado, redireciona para login
        $auth = new \App\Core\Auth($config);
        if ($auth->check()) {
            header('Location: index.php?action=dashboard');
            exit;
        } else {
            header('Location: index.php?action=login');
            exit;
        }
        break;
}
