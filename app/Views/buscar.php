<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Buscar Notas Fiscais na SEFAZ</h2>
    
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
    
    <form method="POST" action="index.php?action=buscar" style="margin-top: 20px;">
        <div class="form-group">
            <label for="data_inicio">Data Início:</label>
            <input type="date" id="data_inicio" name="data_inicio" required>
        </div>
        
        <div class="form-group">
            <label for="data_fim">Data Fim:</label>
            <input type="date" id="data_fim" name="data_fim" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Buscar Notas</button>
    </form>
</div>

<?php if (!empty($notasEncontradas)): ?>
<div class="card">
    <h3>Notas Encontradas (<?= count($notasEncontradas) ?>)</h3>
    
    <table>
        <thead>
            <tr>
                <th>Chave</th>
                <th>Número</th>
                <th>Série</th>
                <th>Data Emissão</th>
                <th>Valor</th>
                <th>Emitente</th>
                <th>CNPJ Emitente</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notasEncontradas as $nota): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($nota['chave'], 0, 20) . '...') ?></td>
                    <td><?= htmlspecialchars($nota['numero']) ?></td>
                    <td><?= htmlspecialchars($nota['serie']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($nota['data_emissao']))) ?></td>
                    <td>R$ <?= number_format($nota['valor'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($nota['nome_emitente']) ?></td>
                    <td><?= htmlspecialchars($nota['cnpj_emitente']) ?></td>
                    <td>
                        <form method="POST" action="index.php?action=salvar" style="display: inline;">
                            <input type="hidden" name="nota_data" value='<?= json_encode($nota) ?>'>
                            <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Salvar XML</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
