<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario['nome']) ?>!</h2>
    <p style="margin: 15px 0;">Painel do Sistema GetXML SEFAZ</p>
    
    <div class="stats">
        <div class="stat-card">
            <h3><?= count($certificados) ?></h3>
            <p>Certificados</p>
        </div>
        <div class="stat-card">
            <h3><?= htmlspecialchars($usuario['papel']) ?></h3>
            <p>Papel</p>
        </div>
        <div class="stat-card">
            <h3><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></h3>
            <p>Status</p>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
        <a href="index.php?action=perfil" class="btn btn-primary">Meu Perfil</a>
        <a href="index.php?action=upload_certificado" class="btn btn-success">Gerenciar Certificados</a>
        <a href="index.php?action=alterar_senha" class="btn btn-warning">Alterar Senha</a>
        <?php if ($usuario['papel'] === 'admin'): ?>
            <a href="index.php?action=admin" class="btn btn-danger">Painel Admin</a>
        <?php endif; ?>
        <a href="index.php?action=buscar" class="btn btn-primary">Buscar Notas</a>
        <a href="index.php?action=listar" class="btn btn-success">Listar Notas</a>
        <a href="index.php?action=logout" class="btn btn-danger">Sair</a>
    </div>
</div>

<div class="card">
    <h3>Seus Dados</h3>
    <table style="margin-top: 15px;">
        <tr>
            <th>Nome:</th>
            <td><?= htmlspecialchars($usuario['nome']) ?></td>
        </tr>
        <tr>
            <th>Email:</th>
            <td><?= htmlspecialchars($usuario['email']) ?></td>
        </tr>
        <tr>
            <th>CNPJ:</th>
            <td><?= htmlspecialchars($usuario['cnpj'] ?: 'Não informado') ?></td>
        </tr>
        <tr>
            <th>IE:</th>
            <td><?= htmlspecialchars($usuario['ie'] ?: 'Não informado') ?></td>
        </tr>
        <tr>
            <th>Papel:</th>
            <td><?= htmlspecialchars($usuario['papel']) ?></td>
        </tr>
    </table>
</div>

<?php if (!empty($certificados)): ?>
<div class="card">
    <h3>Certificados Cadastrados</h3>
    <table style="margin-top: 15px;">
        <thead>
            <tr>
                <th>Nome</th>
                <th>UF</th>
                <th>Status</th>
                <th>Data Cadastro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($certificados as $cert): ?>
                <tr>
                    <td><?= htmlspecialchars($cert['nome_arquivo']) ?></td>
                    <td><?= htmlspecialchars($cert['sefaz_uf']) ?></td>
                    <td>
                        <?= $cert['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>' ?>
                    </td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($cert['criado_em']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
