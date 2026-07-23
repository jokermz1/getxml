<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Bem-vindo ao GetXML SEFAZ</h2>
    <p style="margin: 15px 0;">Sistema para captura e gerenciamento de XMLs de notas fiscais da SEFAZ.</p>
    
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
        <a href="index.php?action=buscar" class="btn btn-primary">Buscar Novas Notas</a>
        <a href="index.php?action=listar" class="btn btn-success">Listar Notas Capturadas</a>
        <a href="index.php?action=config" class="btn btn-primary">Configurações</a>
    </div>
</div>

<div class="card">
    <h3>Como Funciona</h3>
    <ol style="margin: 15px 0; padding-left: 20px; line-height: 1.8;">
        <li>Configure suas credenciais no arquivo <code>.env</code></li>
        <li>Configure o caminho do certificado digital A1</li>
        <li>Informe o CNPJ que deseja consultar</li>
        <li>Utilize a opção "Buscar Notas" para capturar XMLs</li>
        <li>Os XMLs serão salvos no diretório <code>storage/xmls</code></li>
    </ol>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
