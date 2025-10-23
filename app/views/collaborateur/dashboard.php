<?php $viewPath = 'collaborateur/dashboard'; ?>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">✓</div>
        <div class="stat-info">
            <h3><?= $stats['my_tasks']['count'] ?? 0 ?></h3>
            <p>Mes tâches</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-info">
            <h3 style="color: #e74c3c;"><?= $stats['overdue_tasks']['count'] ?? 0 ?></h3>
            <p>En retard</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $stats['my_tickets']['count'] ?? 0 ?></h3>
            <p>Messages</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-info">
            <h3><?= $stats['my_clients']['count'] ?? 0 ?></h3>
            <p>Mes clients</p>
        </div>
    </div>
</div>

<div class="section">
    <h2>Mes tâches prioritaires</h2>
    <?php if (!empty($recent_tasks)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tâche</th>
                        <th>Client</th>
                        <th>Échéance</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_tasks as $task): ?>
                    <tr>
                        <td><strong><?= Security::escape($task['title']) ?></strong></td>
                        <td><?= Security::escape($task['client_code'] ?? 'N/A') ?></td>
                        <td>
                            <?php
                            $dueDate = strtotime($task['due_date']);
                            $today = time();
                            $isOverdue = $dueDate < $today;
                            ?>
                            <span style="color: <?= $isOverdue ? '#e74c3c' : '#333' ?>;">
                                <?= date('d/m/Y', $dueDate) ?>
                                <?php if ($isOverdue): ?>
                                    <strong>(En retard)</strong>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: <?= $task['priority'] === 'haute' || $task['priority'] === 'urgente' ? '#e74c3c' : '#3498db' ?>;">
                                <?= Security::escape($task['priority']) ?>
                            </span>
                        </td>
                        <td><?= Security::escape($task['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="/collaborateur/tasks" class="btn">Voir toutes les tâches →</a>
    <?php else: ?>
        <p class="empty-state">Aucune tâche en cours.</p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>Messages récents</h2>
    <?php if (!empty($recent_tickets)): ?>
        <div class="ticket-list">
            <?php foreach ($recent_tickets as $ticket): ?>
            <div class="ticket-item">
                <div class="ticket-status status-<?= $ticket['status'] ?>"></div>
                <div class="ticket-content">
                    <h4><a href="/collaborateur/tickets/<?= $ticket['id'] ?>"><?= Security::escape($ticket['subject']) ?></a></h4>
                    <small>Client: <?= Security::escape($ticket['client_code']) ?> - <?= date('d/m/Y', strtotime($ticket['created_at'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="/collaborateur/tickets" class="btn">Voir tous les messages →</a>
    <?php else: ?>
        <p class="empty-state">Aucun message.</p>
    <?php endif; ?>
</div>
