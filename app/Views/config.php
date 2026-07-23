<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Configurações do Sistema</h2>
    
    <?php if (!empty($erros)): ?>
        <?php foreach ($erros as $erro): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>
    
    <div class="alert alert-info">
        <strong>Nota:</strong> O certificado digital pode ser configurado de duas formas:
        <ul style="margin: 10px 0 0 20px;">
            <li><strong>Upload via interface</strong> (Recomendado): Acesse "Certificados" no menu</li>
            <li><strong>Arquivo .env</strong>: Configure as variáveis SEFAZ_CERTIFICADO e SEFAZ_SENHA_CERTIFICADO</li>
        </ul>
        O sistema prioriza o certificado uploadado via interface.
    </div>
    
    <div class="card" style="background-color: #f8f9fa; margin-top: 20px;">
        <h3>Configurações Atuais</h3>
        <table style="margin-top: 15px;">
            <tr>
                <th>Parâmetro</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>UF</td>
                <td><?= htmlspecialchars($config['sefaz']['uf']) ?></td>
            </tr>
            <tr>
                <td>Ambiente</td>
                <td><?= $config['sefaz']['ambiente'] == '1' ? 'Produção' : 'Homologação' ?></td>
            </tr>
            <tr>
                <td>CNPJ</td>
                <td><?= htmlspecialchars($config['cnpj']['cnpj'] ?: 'Não configurado') ?></td>
            </tr>
            <tr>
                <td>IE</td>
                <td><?= htmlspecialchars($config['cnpj']['ie'] ?: 'Não configurado') ?></td>
            </tr>
            <tr>
                <td>Certificado</td>
                <td><?= htmlspecialchars($config['sefaz']['certificado'] ?: 'Não configurado') ?></td>
            </tr>
            <tr>
                <td>Storage Path</td>
                <td><?= htmlspecialchars($config['storage']['path']) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card" style="background-color: #f8f9fa; margin-top: 20px;">
        <h3>Como Configurar</h3>
        <ol style="margin: 15px 0; padding-left: 20px; line-height: 1.8;">
            <li>Edite o arquivo <code>.env</code> na raiz do projeto</li>
            <li>Configure o CNPJ que deseja consultar em <code>CNPJ_CNPJ</code></li>
            <li>Configure a Inscrição Estadual em <code>CNPJ_IE</code></li>
            <li><strong>Certificado Digital (opcional)</strong>: Faça upload via interface "Certificados" ou configure em <code>SEFAZ_CERTIFICADO</code></li>
            <li>Configure a UF em <code>SEFAZ_UF</code></li>
            <li>Configure o ambiente (1=produção, 2=homologação) em <code>SEFAZ_AMBIENTE</code></li>
        </ol>
        <div class="alert alert-success" style="margin-top: 15px;">
            <strong>Dica:</strong> O upload de certificado via interface é mais simples e seguro. Cada usuário pode ter seu próprio certificado!
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="index.php" class="btn btn-primary">Voltar ao Início</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
