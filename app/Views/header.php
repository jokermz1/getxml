<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= $titulo ?? 'GetXML SEFAZ' ?></title>
    <link rel="icon" type="image/x-icon" href="/getxml/public/favicon.ico">
    <link rel="stylesheet" href="/getxml/public/assets/css/styles.css">
    <script src="/getxml/public/assets/js/app.js" defer></script>
</head>
<body>
    <header>
        <div class="container">
            <h1>📄 GetXML SEFAZ - Sistema de Captura de Notas Fiscais</h1>
        </div>
    </header>
    
    <nav>
        <div class="container">
            <ul>
                <?php if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado']): ?>
                    <li><a href="index.php?action=dashboard">Dashboard</a></li>
                    <li><a href="index.php?action=buscar">Buscar Notas</a></li>
                    <li><a href="index.php?action=listar">Listar Notas</a></li>
                    <li><a href="index.php?action=logout">Sair</a></li>
                <?php else: ?>
                    <li><a href="index.php?action=login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <main class="container">
