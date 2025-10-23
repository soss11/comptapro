<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mon Espace Client Pro' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <h1>📌 Mon Espace Client Pro</h1>
            <p>Portail Sécurisé Cabinet</p>
        </div>

        <?php
        $viewFile = __DIR__ . '/../' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
        ?>
    </div>

    <footer class="auth-footer">
        <p>&copy; <?= date('Y') ?> Mon Espace Client Pro - <a href="/mentions-legales">Mentions légales</a> - <a href="/cgu">CGU</a></p>
    </footer>
</body>
</html>
