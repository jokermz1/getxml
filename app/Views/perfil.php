<?php require_once __DIR__ . '/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 50px auto;">
    <h2>Meu Perfil</h2>
    
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
    
    <form method="POST" action="index.php?action=perfil" style="margin-top: 20px;">
        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled style="background-color: #f0f0f0;">
            <small>Email não pode ser alterado</small>
        </div>
        
        <div class="form-group">
            <label for="cnpj">CNPJ:</label>
            <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($usuario['cnpj'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="ie">Inscrição Estadual:</label>
            <input type="text" id="ie" name="ie" value="<?= htmlspecialchars($usuario['ie'] ?? '') ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Atualizar</button>
        <a href="index.php?action=dashboard" class="btn btn-secondary">Voltar</a>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
