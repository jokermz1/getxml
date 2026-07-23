<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Painel Administrativo</h2>
    <p style="margin: 15px 0;">Gerenciamento de usuários do sistema</p>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
        <a href="index.php?action=admin_criar_usuario" class="btn btn-success">Criar Novo Usuário</a>
        <a href="index.php?action=dashboard" class="btn btn-secondary">Voltar ao Dashboard</a>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Usuários Cadastrados</h3>
    
    <?php if (!empty($usuarios)): ?>
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>CNPJ</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><?= htmlspecialchars($usuario['cnpj'] ?: 'N/A') ?></td>
                        <td>
                            <span class="badge <?= $usuario['papel'] === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                                <?= htmlspecialchars($usuario['papel']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $usuario['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>' ?>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['criado_em']))) ?></td>
                        <td>
                            <?php if ($usuario['id'] !== $usuarioLogado['id']): ?>
                                <?php if ($usuario['ativo']): ?>
                                    <form method="POST" action="index.php?action=admin_toggle_usuario" style="display: inline;">
                                        <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                        <input type="hidden" name="ativo" value="0">
                                        <button type="submit" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Desativar usuário?')">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="index.php?action=admin_toggle_usuario" style="display: inline;">
                                        <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                        <input type="hidden" name="ativo" value="1">
                                        <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Ativar usuário?')">Ativar</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <small>(Você)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info" style="margin-top: 15px;">
            Nenhum usuário cadastrado.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
