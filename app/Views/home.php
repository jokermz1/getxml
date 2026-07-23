<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Bem-vindo ao GetXML SEFAZ</h2>
    <p style="margin: 15px 0;">Sistema para captura e gerenciamento de XMLs de notas fiscais da SEFAZ.</p>
    
    <?php if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado']): ?>
        <div class="stats">
            <div class="stat-card">
                <h3><?= $estatisticas['total_notas'] ?? 0 ?></h3>
                <p>Total de Notas</p>
            </div>
            <div class="stat-card">
                <h3>R$ <?= number_format($estatisticas['valor_total'] ?? 0, 2, ',', '.') ?></h3>
                <p>Valor Total</p>
            </div>
            <div class="stat-card">
                <h3>R$ <?= number_format($estatisticas['valor_medio'] ?? 0, 2, ',', '.') ?></h3>
                <p>Valor Médio</p>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="index.php?action=dashboard" class="btn btn-primary">Dashboard</a>
            <a href="index.php?action=buscar" class="btn btn-success">Buscar Novas Notas</a>
            <a href="index.php?action=listar" class="btn btn-primary">Listar Notas Capturadas</a>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Faça login para acessar o sistema e gerenciar suas notas fiscais.
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="index.php?action=login" class="btn btn-primary">Fazer Login</a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Como Funciona</h3>
    <ol style="margin: 15px 0; padding-left: 20px; line-height: 1.8;">
        <li>Faça login no sistema</li>
        <li>Configure seu certificado digital no perfil</li>
        <li>Informe o CNPJ que deseja consultar</li>
        <li>Utilize a opção "Buscar Notas" para capturar XMLs</li>
        <li>Os XMLs serão salvos no seu diretório pessoal</li>
    </ol>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
