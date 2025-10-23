<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mon Espace Client Pro' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <meta name="csrf-token" content="<?= Security::generateCSRFToken() ?>">
</head>
<body class="dashboard-page">
    <div class="layout-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>📌 MECP</h2>
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
            </div>

            <nav class="sidebar-nav">
                <?php $role = $_SESSION['user_role'] ?? 'client'; ?>

                <?php if ($role === 'admin'): ?>
                    <a href="/admin/dashboard" class="nav-item"><span>🏠</span> Tableau de bord</a>
                    <a href="/admin/users" class="nav-item"><span>👥</span> Utilisateurs</a>
                    <a href="/admin/clients" class="nav-item"><span>📁</span> Clients</a>
                    <a href="/admin/settings" class="nav-item"><span>⚙️</span> Paramètres</a>
                    <a href="/admin/logs" class="nav-item"><span>📋</span> Logs</a>
                    <a href="/veille" class="nav-item"><span>📰</span> Veille</a>

                <?php elseif ($role === 'collaborateur'): ?>
                    <a href="/collaborateur/dashboard" class="nav-item"><span>🏠</span> Tableau de bord</a>
                    <a href="/collaborateur/tasks" class="nav-item"><span>✓</span> Mes tâches</a>
                    <a href="/collaborateur/clients" class="nav-item"><span>📁</span> Mes clients</a>
                    <a href="/collaborateur/tickets" class="nav-item"><span>💬</span> Messagerie</a>
                    <a href="/collaborateur/workflows" class="nav-item"><span>🔄</span> Workflows</a>
                    <a href="/veille" class="nav-item"><span>📰</span> Veille</a>

                <?php else: ?>
                    <a href="/client/dashboard" class="nav-item"><span>🏠</span> Tableau de bord</a>
                    <a href="/client/documents" class="nav-item"><span>📄</span> Mes documents</a>
                    <a href="/client/tickets" class="nav-item"><span>💬</span> Messagerie</a>
                    <a href="/veille" class="nav-item"><span>📰</span> Veille</a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?= Security::escape($_SESSION['user_name'] ?? 'Utilisateur') ?></strong>
                    <small><?= Security::escape($_SESSION['user_role'] ?? '') ?></small>
                </div>
                <a href="/logout" class="btn-logout">Déconnexion</a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <header class="top-bar">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">☰</button>
                <h1><?= $title ?? 'Tableau de bord' ?></h1>
                <div class="user-actions">
                    <span><?= Security::escape($_SESSION['user_name'] ?? '') ?></span>
                    <a href="/logout" class="btn btn-sm">Déconnexion</a>
                </div>
            </header>

            <div class="content-wrapper">
                <?php
                $flash = $this->getFlashMessage();
                if ($flash):
                ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= Security::escape($flash['message']) ?>
                </div>
                <?php endif; ?>

                <?php
                $viewFile = __DIR__ . '/../' . $viewPath . '.php';
                if (file_exists($viewFile)) {
                    require $viewFile;
                }
                ?>
            </div>

            <footer class="main-footer">
                <p>&copy; <?= date('Y') ?> Mon Espace Client Pro - <a href="/mentions-legales">Mentions légales</a> - <a href="/cgu">CGU</a></p>
            </footer>
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
