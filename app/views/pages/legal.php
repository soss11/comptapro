<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Page légale' ?> - Mon Espace Client Pro</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div style="max-width: 900px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <a href="/" style="display: inline-block; margin-bottom: 20px; color: #3498db;">← Retour à l'accueil</a>

        <div style="line-height: 1.8;">
            <?= $content ?>
        </div>

        <hr style="margin: 40px 0; border: none; border-top: 1px solid #ddd;">

        <p style="text-align: center; color: #777; font-size: 14px;">
            &copy; <?= date('Y') ?> Mon Espace Client Pro - Tous droits réservés
        </p>
    </div>
</body>
</html>
