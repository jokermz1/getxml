<?php require_once __DIR__ . '/header.php'; ?>

<div class="card" style="max-width: 400px; margin: 50px auto;">
    <h2 style="text-align: center;">Login</h2>
    
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
    
    <form method="POST" action="index.php?action=login" style="margin-top: 20px;">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <p>Usuários de teste:</p>
        <small>admin@getxml.com / admin123</small><br>
        <small>contador1@teste.com / contador123</small>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
