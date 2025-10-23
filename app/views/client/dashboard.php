<?php $viewPath = 'client/dashboard'; ?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">📄</div>
        <div class="stat-info">
            <h3><?= $stats['documents']['count'] ?? 0 ?></h3>
            <p>Documents</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $stats['tickets']['count'] ?? 0 ?></h3>
            <p>Messages ouverts</p>
        </div>
    </div>
</div>

<div class="section">
    <h2>Documents récents</h2>
    <?php if (!empty($stats['recent_documents'])): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Catégorie</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_documents'] as $doc): ?>
                    <tr>
                        <td><?= Security::escape($doc['original_name']) ?></td>
                        <td><span class="badge"><?= Security::escape($doc['category'] ?? 'N/A') ?></span></td>
                        <td><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                        <td>
                            <a href="/client/documents/download/<?= $doc['id'] ?>" class="btn btn-sm">Télécharger</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="/client/documents" class="btn">Voir tous les documents →</a>
    <?php else: ?>
        <p class="empty-state">Aucun document pour le moment.</p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>Messages récents</h2>
    <?php if (!empty($stats['recent_tickets'])): ?>
        <div class="ticket-list">
            <?php foreach ($stats['recent_tickets'] as $ticket): ?>
            <div class="ticket-item">
                <div class="ticket-status status-<?= $ticket['status'] ?>"></div>
                <div class="ticket-content">
                    <h4><a href="/client/tickets/<?= $ticket['id'] ?>"><?= Security::escape($ticket['subject']) ?></a></h4>
                    <small>Créé le <?= date('d/m/Y', strtotime($ticket['created_at'])) ?> - Statut: <?= $ticket['status'] ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="/client/tickets" class="btn">Voir tous les messages →</a>
    <?php else: ?>
        <p class="empty-state">Aucun message.</p>
        <a href="/client/tickets/create" class="btn btn-primary">Nouveau message</a>
    <?php endif; ?>
</div>
