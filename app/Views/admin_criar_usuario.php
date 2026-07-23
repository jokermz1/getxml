<?php require_once __DIR__ . '/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 50px auto;">
    <h2>Criar Novo Usuário</h2>
    
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
    
    <form method="POST" action="index.php?action=admin_criar_usuario" style="margin-top: 20px;">
        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required minlength="6">
            <small>Mínimo 6 caracteres</small>
        </div>
        
        <div class="form-group">
            <label for="cnpj">CNPJ:</label>
            <input type="text" id="cnpj" name="cnpj">
        </div>
        
        <div class="form-group">
            <label for="ie">Inscrição Estadual:</label>
            <input type="text" id="ie" name="ie">
        </div>
        
        <div class="form-group">
            <label for="papel">Papel:</label>
            <select id="papel" name="papel">
                <option value="contador">Contador</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success">Criar Usuário</button>
        <a href="index.php?action=admin" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
