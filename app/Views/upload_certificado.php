<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Gerenciar Certificados</h2>
    
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
    
    <div class="card" style="background-color: #f8f9fa; margin-top: 20px;">
        <h3>Upload de Novo Certificado</h3>
        <form method="POST" action="index.php?action=upload_certificado" enctype="multipart/form-data" style="margin-top: 15px;">
            <div class="form-group">
                <label for="certificado">Arquivo do Certificado (.pfx ou .p12):</label>
                <input type="file" id="certificado" name="certificado" accept=".pfx,.p12" required>
                <small>Tamanho máximo: 10MB</small>
            </div>
            
            <div class="form-group">
                <label for="senha_certificado">Senha do Certificado:</label>
                <input type="password" id="senha_certificado" name="senha_certificado" required>
            </div>
            
            <div class="form-group">
                <label for="sefaz_uf">UF da SEFAZ:</label>
                <select id="sefaz_uf" name="sefaz_uf">
                    <option value="SP" <?= $usuario['cnpj'] ? 'selected' : '' ?>>SP - São Paulo</option>
                    <option value="MG">MG - Minas Gerais</option>
                    <option value="PR">PR - Paraná</option>
                    <option value="RJ">RJ - Rio de Janeiro</option>
                    <option value="RS">RS - Rio Grande do Sul</option>
                    <option value="SC">SC - Santa Catarina</option>
                    <option value="BA">BA - Bahia</option>
                    <option value="PE">PE - Pernambuco</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success">Enviar Certificado</button>
        </form>
    </div>
    
    <?php if (!empty($certificados)): ?>
    <div class="card" style="margin-top: 20px;">
        <h3>Certificados Cadastrados</h3>
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>UF</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
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
                        <td>
                            <?php if ($cert['ativo']): ?>
                                <form method="POST" action="index.php?action=toggle_certificado" style="display: inline;">
                                    <input type="hidden" name="certificado_id" value="<?= $cert['id'] ?>">
                                    <input type="hidden" name="ativo" value="0">
                                    <button type="submit" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="index.php?action=toggle_certificado" style="display: inline;">
                                    <input type="hidden" name="certificado_id" value="<?= $cert['id'] ?>">
                                    <input type="hidden" name="ativo" value="1">
                                    <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Ativar</button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" action="index.php?action=remover_certificado" style="display: inline; margin-left: 5px;">
                                <input type="hidden" name="certificado_id" value="<?= $cert['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Deseja realmente remover este certificado?')">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info" style="margin-top: 20px;">
        Nenhum certificado cadastrado. Faça upload do seu certificado digital para começar a usar o sistema.
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="index.php?action=dashboard" class="btn btn-secondary">Voltar ao Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
