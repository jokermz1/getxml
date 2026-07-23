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
            <div style="position: relative;">
                <input type="password" id="senha_atual" name="senha_atual" required style="width: 100%; padding-right: 40px;">
                <button type="button" onclick="toggleSenha('senha_atual', 'icone_senha_atual')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0;">
                    <span id="icone_senha_atual">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="nova_senha">Nova Senha:</label>
            <div style="position: relative;">
                <input type="password" id="nova_senha" name="nova_senha" required minlength="6" style="width: 100%; padding-right: 40px;">
                <button type="button" onclick="toggleSenha('nova_senha', 'icone_nova_senha')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0;">
                    <span id="icone_nova_senha">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </button>
            </div>
            <small>Mínimo 6 caracteres</small>
        </div>

        <div class="form-group">
            <label for="confirmar_senha">Confirmar Nova Senha:</label>
            <div style="position: relative;">
                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6" style="width: 100%; padding-right: 40px;">
                <button type="button" onclick="toggleSenha('confirmar_senha', 'icone_confirmar_senha')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0;">
                    <span id="icone_confirmar_senha">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Alterar Senha</button>
        <a href="index.php?action=dashboard" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
function toggleSenha(inputId, iconeId) {
    var senhaInput = document.getElementById(inputId);
    var iconeSenha = document.getElementById(iconeId);

    if (senhaInput.type === 'password') {
        senhaInput.type = 'text';
        // Ícone de olho fechado
        iconeSenha.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        `;
    } else {
        senhaInput.type = 'password';
        // Ícone de olho aberto
        iconeSenha.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
