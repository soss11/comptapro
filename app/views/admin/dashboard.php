<?php $viewPath = 'admin/dashboard'; ?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= $stats['total_users']['count'] ?? 0 ?></h3>
            <p>Utilisateurs</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-info">
            <h3><?= $stats['total_clients']['count'] ?? 0 ?></h3>
            <p>Clients actifs</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📄</div>
        <div class="stat-info">
            <h3><?= $stats['total_documents']['count'] ?? 0 ?></h3>
            <p>Documents</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✓</div>
        <div class="stat-info">
            <h3><?= $stats['total_tasks']['count'] ?? 0 ?></h3>
            <p>Tâches actives</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-info">
            <h3 style="color: #e74c3c;"><?= $stats['tasks_overdue']['count'] ?? 0 ?></h3>
            <p>Tâches en retard</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $stats['total_tickets']['count'] ?? 0 ?></h3>
            <p>Tickets ouverts</p>
        </div>
    </div>
</div>

<div class="section">
    <h2>Activité récente</h2>
    <?php if (!empty($recent_activity)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Détails</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activity as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                        <td><span class="badge"><?= Security::escape($log['action']) ?></span></td>
                        <td><?= Security::escape($log['details'] ?? '-') ?></td>
                        <td><small><?= Security::escape($log['ip_address']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="/admin/logs" class="btn">Voir tous les logs →</a>
    <?php else: ?>
        <p class="empty-state">Aucune activité récente.</p>
    <?php endif; ?>
</div>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
    <h3 style="margin-bottom: 15px;">🚀 Actions rapides</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="/admin/users/create" class="btn btn-primary">Créer un utilisateur</a>
        <a href="/admin/clients/create" class="btn btn-primary">Créer un client</a>
        <a href="/admin/settings" class="btn">Paramètres</a>
    </div>
</div>
