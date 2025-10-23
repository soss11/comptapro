<?php $viewPath = 'client/documents'; ?>

<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Mes documents</h2>
        <button onclick="document.getElementById('uploadModal').style.display='block'" class="btn btn-primary">
            Déposer un document
        </button>
    </div>

    <?php if (!empty($documents)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Catégorie</th>
                        <th>Période</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td>
                            <?= FileManager::getFileIcon($doc['extension']) ?>
                            <?= Security::escape($doc['original_name']) ?>
                            <?php if ($doc['is_cabinet_document']): ?>
                                <span class="badge" style="background: #27ae60; margin-left: 5px;">Cabinet</span>
                            <?php endif; ?>
                        </td>
                        <td><?= Security::escape($doc['category'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($doc['period_month'] && $doc['period_year']): ?>
                                <?= str_pad($doc['period_month'], 2, '0', STR_PAD_LEFT) ?>/<?= $doc['period_year'] ?>
                            <?php elseif ($doc['period_year']): ?>
                                <?= $doc['period_year'] ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= Security::escape($doc['first_name'] . ' ' . $doc['last_name']) ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                        <td>
                            <a href="/client/documents/download/<?= $doc['id'] ?>" class="btn btn-sm">Télécharger</a>
                            <?php if (FileManager::isPDF($doc['extension']) || FileManager::isImage($doc['extension'])): ?>
                                <a href="/documents/preview/<?= $doc['id'] ?>" target="_blank" class="btn btn-sm" style="background: #95a5a6;">Aperçu</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-state">Aucun document pour le moment.</p>
    <?php endif; ?>
</div>

<!-- Modal Upload -->
<div id="uploadModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); padding:20px;">
    <div style="max-width:600px; margin:100px auto; background:white; padding:30px; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:20px;">Déposer un document</h3>

        <form id="uploadForm" method="POST" action="/client/documents/upload" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

            <div class="form-group">
                <label>Fichier *</label>
                <input type="file" name="document" required>
                <small>Types autorisés: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX, ZIP - Max: 10 MB</small>
            </div>

            <div class="form-group">
                <label>Catégorie</label>
                <select name="category">
                    <option value="">-- Choisir --</option>
                    <option value="factures">Factures</option>
                    <option value="releves">Relevés bancaires</option>
                    <option value="bulletins">Bulletins de paie</option>
                    <option value="contrats">Contrats</option>
                    <option value="autres">Autres</option>
                </select>
            </div>

            <div class="form-row" style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Mois</label>
                    <select name="period_month">
                        <option value="">-- Mois --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Année</label>
                    <select name="period_year">
                        <option value="">-- Année --</option>
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                <button type="button" onclick="document.getElementById('uploadModal').style.display='none'" class="btn btn-secondary">Annuler</button>
                <button type="submit" class="btn btn-primary">Envoyer</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Document uploadé avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        alert('Erreur lors de l\'upload');
        console.error(error);
    });
});
</script>
