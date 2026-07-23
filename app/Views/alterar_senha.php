<?php require_once __DIR__ . '/header.php'; ?>

<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2>Alterar Senha</h2>
    
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
    
    <form method="POST" action="index.php?action=alterar_senha" style="margin-top: 20px;">
        <div class="form-group">
            <label for="senha_atual">Senha Atual:</label>
            <input type="password" id="senha_atual" name="senha_atual" required>
        </div>
        
        <div class="form-group">
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
            <small>Mínimo 6 caracteres</small>
        </div>
        
        <div class="form-group">
            <label for="confirmar_senha">Confirmar Nova Senha:</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
        </div>
        
        <button type="submit" class="btn btn-primary">Alterar Senha</button>
        <a href="index.php?action=dashboard" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
