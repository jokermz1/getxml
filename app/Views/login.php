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
            <div style="position: relative;">
                <input type="password" id="senha" name="senha" required style="width: 100%; padding-right: 40px;">
                <button type="button" onclick="toggleSenha()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 5px;">
                    <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <p>Usuários de teste:</p>
        <small>admin@getxml.com / admin123</small><br>
        <small>contador1@teste.com / contador123</small>
    </div>
</div>

<script>
function toggleSenha() {
    var senhaInput = document.getElementById('senha');
    var eyeIcon = document.getElementById('eyeIcon');
    
    if (senhaInput.type === 'password') {
        senhaInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        senhaInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
