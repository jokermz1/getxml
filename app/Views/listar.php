<?php require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <h2>Notas Fiscais Capturadas</h2>
    
    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>
    
    <form method="GET" action="index.php" style="margin-top: 20px;">
        <input type="hidden" name="action" value="listar">
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="filtro_data_inicio">Data Início:</label>
                <input type="date" id="filtro_data_inicio" name="filtro_data_inicio" value="<?= htmlspecialchars($_GET['filtro_data_inicio'] ?? '') ?>">
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="filtro_data_fim">Data Fim:</label>
                <input type="date" id="filtro_data_fim" name="filtro_data_fim" value="<?= htmlspecialchars($_GET['filtro_data_fim'] ?? '') ?>">
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="filtro_cnpj">CNPJ:</label>
                <input type="text" id="filtro_cnpj" name="filtro_cnpj" placeholder="00.000.000/0000-00" value="<?= htmlspecialchars($_GET['filtro_cnpj'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="index.php?action=listar" class="btn btn-danger">Limpar Filtros</a>
        </div>
    </form>
</div>

<?php if (!empty($notas)): ?>
<div class="card">
    <p style="margin-bottom: 15px;"><strong>Total de notas:</strong> <?= count($notas) ?></p>
    
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
                <th>Data Captura</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notas as $nota): ?>
                <tr>
                    <td>
                        <small><?= htmlspecialchars(substr($nota['chave'], 0, 20)) ?>...</small>
                    </td>
                    <td><?= htmlspecialchars($nota['numero']) ?></td>
                    <td><?= htmlspecialchars($nota['serie']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($nota['data_emissao']))) ?></td>
                    <td>R$ <?= number_format($nota['valor'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($nota['nome_emitente']) ?></td>
                    <td><?= htmlspecialchars($nota['cnpj_emitente']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($nota['data_captura']))) ?></td>
                    <td>
                        <?php if (!empty($nota['arquivo_xml'])): ?>
                            <a href="<?= htmlspecialchars($nota['arquivo_xml']) ?>" target="_blank" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; display: inline-block;">Ver XML</a>
                        <?php endif; ?>
                        
                        <form method="POST" action="index.php?action=excluir" style="display: inline; margin-left: 5px;">
                            <input type="hidden" name="chave" value="<?= htmlspecialchars($nota['chave']) ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Deseja realmente excluir esta nota?')">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="card">
    <div class="alert alert-info">
        Nenhuma nota fiscal encontrada. Utilize a opção "Buscar Notas" para capturar XMLs da SEFAZ.
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
