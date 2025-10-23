<?php
/**
 * Installateur Web - Mon Espace Client Pro
 * Interface d'installation type WordPress
 */

// Empêcher l'accès si déjà installé
if (file_exists(__DIR__ . '/.env') && file_exists(__DIR__ . '/config/installed.lock')) {
    die('L\'application est déjà installée. Supprimez le fichier config/installed.lock pour réinstaller.');
}

session_start();

$step = $_GET['step'] ?? 1;
$error = null;
$success = null;

// Traitement selon l'étape
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step == 2) {
        // Vérification des prérequis
        $step = 3;
    }

    if ($step == 3 && isset($_POST['db_test'])) {
        // Test de connexion à la base de données
        try {
            $pdo = new PDO(
                'mysql:host=' . $_POST['db_host'] . ';charset=utf8mb4',
                $_POST['db_user'],
                $_POST['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Vérifier si la base existe, sinon la créer
            $dbname = $_POST['db_name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");

            $_SESSION['db_config'] = $_POST;
            $success = "Connexion à la base de données réussie ! La base de données a été créée ou vérifiée.";

        } catch (PDOException $e) {
            $error = "Erreur de connexion : " . $e->getMessage();
        }
    }

    if ($step == 3 && isset($_POST['db_continue']) && !$error) {
        $step = 4;
    }

    if ($step == 4 && isset($_POST['install'])) {
        // Installation complète
        try {
            $dbConfig = $_SESSION['db_config'];

            // Connexion à la base
            $pdo = new PDO(
                'mysql:host=' . $dbConfig['db_host'] . ';dbname=' . $dbConfig['db_name'] . ';charset=utf8mb4',
                $dbConfig['db_user'],
                $dbConfig['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Exécuter le schéma
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            $pdo->exec($schema);

            // Insérer les données de démo si demandé
            if (isset($_POST['install_demo']) && $_POST['install_demo'] == '1') {
                $demoData = file_get_contents(__DIR__ . '/database/demo_data.sql');
                $pdo->exec($demoData);
            }

            // Créer le compte admin
            $adminEmail = $_POST['admin_email'];
            $adminPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
            $adminFirstName = $_POST['admin_first_name'];
            $adminLastName = $_POST['admin_last_name'];

            $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_active, created_at)
                                   VALUES (?, ?, ?, ?, 'admin', 1, NOW())");
            $stmt->execute([$adminEmail, $adminPassword, $adminFirstName, $adminLastName]);

            // Créer le fichier .env
            $envContent = "# Configuration Mon Espace Client Pro
DB_HOST={$dbConfig['db_host']}
DB_NAME={$dbConfig['db_name']}
DB_USER={$dbConfig['db_user']}
DB_PASS={$dbConfig['db_pass']}
DB_CHARSET=utf8mb4

APP_NAME=\"{$_POST['app_name']}\"
APP_URL={$_POST['app_url']}
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Guadeloupe

SESSION_LIFETIME=7200
CSRF_TOKEN_NAME=csrf_token
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900

UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip
UPLOAD_PATH=/uploads/documents

MAIL_HOST={$_POST['mail_host']}
MAIL_PORT={$_POST['mail_port']}
MAIL_USERNAME={$_POST['mail_username']}
MAIL_PASSWORD={$_POST['mail_password']}
MAIL_ENCRYPTION={$_POST['mail_encryption']}
MAIL_FROM_ADDRESS={$_POST['mail_from_address']}
MAIL_FROM_NAME=\"{$_POST['app_name']}\"

CRON_TOKEN=" . bin2hex(random_bytes(32)) . "
CRON_URL=/cron.php

ENABLE_2FA=false
ENABLE_IP_WHITELIST=false
ADMIN_IP_WHITELIST=
ENABLE_PWA=false
ENABLE_RGPD=true
FILE_RETENTION_DAYS=2555
ENABLE_WATERMARK=false
ENABLE_API=false
API_RATE_LIMIT=100
";

            file_put_contents(__DIR__ . '/.env', $envContent);

            // Créer les dossiers nécessaires
            $folders = [
                'uploads/documents',
                'uploads/temp',
                'logs',
                'config'
            ];

            foreach ($folders as $folder) {
                $path = __DIR__ . '/' . $folder;
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
            }

            // Créer le fichier .htaccess dans uploads
            file_put_contents(__DIR__ . '/uploads/.htaccess', "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps)$\">\n    Deny from all\n</FilesMatch>");

            // Créer le fichier de verrouillage
            file_put_contents(__DIR__ . '/config/installed.lock', date('Y-m-d H:i:s'));

            $step = 5;
            $success = "Installation terminée avec succès !";

        } catch (Exception $e) {
            $error = "Erreur lors de l'installation : " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Mon Espace Client Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .content { padding: 40px; }
        .steps { display: flex; justify-content: space-between; margin-bottom: 40px; padding: 0 20px; }
        .step { flex: 1; text-align: center; position: relative; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 15px; right: -50%; width: 100%; height: 2px; background: #ddd; z-index: -1; }
        .step-number { width: 30px; height: 30px; background: #ddd; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px; }
        .step.active .step-number { background: #3498db; }
        .step.completed .step-number { background: #27ae60; }
        .step.completed::after { background: #27ae60; }
        .step-label { font-size: 12px; color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3498db; }
        .form-group small { display: block; margin-top: 5px; color: #666; font-size: 12px; }
        .btn { padding: 12px 30px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
        .btn-secondary { background: #95a5a6; }
        .btn-secondary:hover { background: #7f8c8d; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; border-left: 4px solid #e74c3c; color: #c0392b; }
        .alert-success { background: #efe; border-left: 4px solid #27ae60; color: #229954; }
        .alert-warning { background: #fef5e7; border-left: 4px solid #f39c12; color: #d68910; }
        .alert-info { background: #eef; border-left: 4px solid #3498db; color: #2874a6; }
        .requirement { display: flex; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 5px; margin-bottom: 10px; }
        .requirement .status { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; }
        .requirement.ok .status { background: #27ae60; color: white; }
        .requirement.error .status { background: #e74c3c; color: white; }
        .requirement.warning .status { background: #f39c12; color: white; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        .final-info { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db; }
        .final-info h3 { color: #2c3e50; margin-bottom: 15px; }
        .final-info p { margin-bottom: 10px; line-height: 1.6; }
        .final-info code { background: #e8e8e8; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn-group { display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📌 Mon Espace Client Pro</h1>
            <p>Portail Sécurisé Cabinet - Installation</p>
        </div>

        <div class="content">
            <div class="steps">
                <div class="step <?= $step >= 1 ? 'completed' : '' ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">Bienvenue</div>
                </div>
                <div class="step <?= $step >= 2 ? 'completed' : '' ?> <?= $step == 2 ? 'active' : '' ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Prérequis</div>
                </div>
                <div class="step <?= $step >= 3 ? 'completed' : '' ?> <?= $step == 3 ? 'active' : '' ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">Base de données</div>
                </div>
                <div class="step <?= $step >= 4 ? 'completed' : '' ?> <?= $step == 4 ? 'active' : '' ?>">
                    <div class="step-number">4</div>
                    <div class="step-label">Configuration</div>
                </div>
                <div class="step <?= $step >= 5 ? 'completed' : '' ?> <?= $step == 5 ? 'active' : '' ?>">
                    <div class="step-number">5</div>
                    <div class="step-label">Terminé</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Succès :</strong> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <h2>Bienvenue dans l'installation</h2>
                <p style="margin: 20px 0; line-height: 1.8;">
                    Bienvenue dans l'assistant d'installation de <strong>Mon Espace Client Pro</strong>,
                    le portail sécurisé pour cabinets comptables et juridiques.
                </p>
                <div class="alert alert-info">
                    <strong>À propos de cette application :</strong><br>
                    • Portail client sécurisé avec gestion de documents<br>
                    • Système de messagerie (tickets) client-cabinet<br>
                    • Pilotage des tâches et workflows<br>
                    • Veille réglementaire intelligente<br>
                    • 100% en français, conforme RGPD
                </div>
                <p style="margin: 20px 0;">
                    Cet assistant vous guidera à travers les étapes suivantes :
                </p>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Vérification des prérequis système</li>
                    <li>Configuration de la base de données</li>
                    <li>Paramètres de l'application</li>
                    <li>Création du compte administrateur</li>
                </ol>
                <p style="margin: 20px 0; color: #e74c3c;">
                    <strong>Important :</strong> Assurez-vous d'avoir vos identifiants de base de données MySQL avant de continuer.
                </p>
                <form method="post" action="?step=2">
                    <div class="btn-group">
                        <button type="submit" class="btn">Commencer l'installation →</button>
                    </div>
                </form>

            <?php elseif ($step == 2): ?>
                <h2>Vérification des prérequis</h2>
                <p style="margin-bottom: 20px;">Vérification de la configuration de votre serveur...</p>

                <?php
                $allOk = true;

                // PHP Version
                $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
                if (!$phpOk) $allOk = false;
                ?>

                <div class="requirement <?= $phpOk ? 'ok' : 'error' ?>">
                    <div class="status"><?= $phpOk ? '✓' : '✗' ?></div>
                    <div>
                        <strong>PHP Version (>= 8.0)</strong><br>
                        <small>Version actuelle : <?= PHP_VERSION ?></small>
                    </div>
                </div>

                <?php
                // Extensions PHP
                $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'fileinfo', 'gd'];
                foreach ($extensions as $ext):
                    $extOk = extension_loaded($ext);
                    if (!$extOk) $allOk = false;
                ?>
                <div class="requirement <?= $extOk ? 'ok' : 'error' ?>">
                    <div class="status"><?= $extOk ? '✓' : '✗' ?></div>
                    <div>
                        <strong>Extension PHP : <?= $ext ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php
                // Permissions
                $writableDirs = [
                    'uploads' => is_writable(__DIR__ . '/uploads'),
                    'logs' => is_writable(__DIR__ . '/logs'),
                    'config' => is_writable(__DIR__ . '/config'),
                    'racine' => is_writable(__DIR__),
                ];

                foreach ($writableDirs as $dir => $writable):
                    if (!$writable) $allOk = false;
                ?>
                <div class="requirement <?= $writable ? 'ok' : 'error' ?>">
                    <div class="status"><?= $writable ? '✓' : '✗' ?></div>
                    <div>
                        <strong>Dossier <?= $dir ?> accessible en écriture</strong>
                        <?php if (!$writable): ?>
                            <br><small style="color: #e74c3c;">Exécutez : chmod 755 <?= $dir ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!$allOk): ?>
                    <div class="alert alert-error" style="margin-top: 20px;">
                        <strong>Attention :</strong> Certains prérequis ne sont pas remplis. Veuillez corriger les erreurs avant de continuer.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-top: 20px;">
                        <strong>Excellent !</strong> Tous les prérequis sont remplis. Vous pouvez continuer l'installation.
                    </div>
                <?php endif; ?>

                <form method="post" action="?step=3">
                    <div class="btn-group">
                        <button type="submit" class="btn" <?= !$allOk ? 'disabled' : '' ?>>Continuer →</button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <h2>Configuration de la base de données</h2>
                <p style="margin-bottom: 20px;">Entrez les informations de connexion à votre base de données MySQL.</p>

                <form method="post" action="?step=3">
                    <div class="form-group">
                        <label>Hôte de la base de données</label>
                        <input type="text" name="db_host" value="<?= $_POST['db_host'] ?? 'localhost' ?>" required>
                        <small>Généralement "localhost" sur un hébergement mutualisé</small>
                    </div>

                    <div class="form-group">
                        <label>Nom de la base de données</label>
                        <input type="text" name="db_name" value="<?= $_POST['db_name'] ?? 'mon_espace_client_pro' ?>" required>
                        <small>Le nom de votre base de données MySQL</small>
                    </div>

                    <div class="form-group">
                        <label>Utilisateur de la base de données</label>
                        <input type="text" name="db_user" value="<?= $_POST['db_user'] ?? '' ?>" required>
                        <small>L'utilisateur MySQL</small>
                    </div>

                    <div class="form-group">
                        <label>Mot de passe de la base de données</label>
                        <input type="password" name="db_pass" value="<?= $_POST['db_pass'] ?? '' ?>">
                        <small>Le mot de passe MySQL (laisser vide si aucun)</small>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Note :</strong> Si la base de données n'existe pas, l'installateur tentera de la créer automatiquement.
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="db_test" class="btn">Tester la connexion</button>
                        <?php if ($success): ?>
                            <button type="submit" name="db_continue" class="btn">Continuer →</button>
                        <?php endif; ?>
                    </div>
                </form>

            <?php elseif ($step == 4): ?>
                <h2>Configuration de l'application</h2>
                <p style="margin-bottom: 20px;">Configurez les paramètres de votre application.</p>

                <form method="post" action="?step=4">
                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Informations générales</h3>

                    <div class="form-group">
                        <label>Nom de l'application</label>
                        <input type="text" name="app_name" value="Mon Espace Client Pro" required>
                    </div>

                    <div class="form-group">
                        <label>URL de l'application</label>
                        <input type="url" name="app_url" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) ?>" required>
                        <small>L'URL complète de votre installation (sans slash final)</small>
                    </div>

                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Compte administrateur</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="admin_first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="admin_last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email administrateur</label>
                        <input type="email" name="admin_email" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="admin_password" minlength="8" required>
                            <small>Minimum 8 caractères</small>
                        </div>
                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="admin_password_confirm" minlength="8" required>
                        </div>
                    </div>

                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Configuration email (SMTP)</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Serveur SMTP</label>
                            <input type="text" name="mail_host" value="smtp.example.com">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="mail_port" value="587">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="mail_encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">Aucune</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Utilisateur SMTP</label>
                        <input type="text" name="mail_username">
                    </div>

                    <div class="form-group">
                        <label>Mot de passe SMTP</label>
                        <input type="password" name="mail_password">
                    </div>

                    <div class="form-group">
                        <label>Email d'expédition</label>
                        <input type="email" name="mail_from_address" value="noreply@example.com">
                        <small>L'adresse email qui apparaîtra comme expéditeur</small>
                    </div>

                    <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Options</h3>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="install_demo" value="1" id="install_demo" checked>
                        <label for="install_demo">Installer les données de démonstration (recommandé pour tester l'application)</label>
                    </div>

                    <div class="alert alert-info">
                        <strong>Données de démo :</strong> 3 clients, 2 collaborateurs, 10 documents, 6 tâches, 3 tickets, 5 articles de veille, 1 workflow.<br>
                        <strong>Identifiants de test :</strong> Email des utilisateurs démo / Mot de passe : <code>demo123</code>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="install" class="btn">Lancer l'installation</button>
                    </div>
                </form>

            <?php elseif ($step == 5): ?>
                <div class="final-info">
                    <h3>🎉 Installation terminée avec succès !</h3>
                    <p>
                        Votre portail <strong>Mon Espace Client Pro</strong> est maintenant installé et prêt à être utilisé.
                    </p>

                    <h4 style="margin-top: 20px; margin-bottom: 10px;">Informations importantes :</h4>

                    <p><strong>Compte administrateur :</strong></p>
                    <p>
                        Email : <code><?= htmlspecialchars($_POST['admin_email'] ?? 'N/A') ?></code><br>
                        Mot de passe : <em>Celui que vous avez défini</em>
                    </p>

                    <?php if (isset($_POST['install_demo']) && $_POST['install_demo'] == '1'): ?>
                    <p><strong>Comptes de démonstration :</strong></p>
                    <p>
                        • Admin : <code>admin@cabinet.gp</code> / <code>demo123</code><br>
                        • Collaborateur 1 : <code>julien.martin@cabinet.gp</code> / <code>demo123</code><br>
                        • Collaborateur 2 : <code>sophie.bernard@cabinet.gp</code> / <code>demo123</code><br>
                        • Client 1 : <code>pierre.lafleur@lesoleil.gp</code> / <code>demo123</code><br>
                        • Client 2 : <code>dr.louis@sante.gp</code> / <code>demo123</code><br>
                        • Client 3 : <code>j.moreau@sci-caraibes.gp</code> / <code>demo123</code>
                    </p>
                    <?php endif; ?>

                    <p><strong>Configuration CRON :</strong></p>
                    <p>
                        Pour activer les rappels automatiques et les tâches planifiées, configurez cette tâche CRON :<br>
                        <code style="display: block; margin-top: 10px; padding: 10px; background: white;">
                            */15 * * * * curl -s "<?= $_POST['app_url'] ?? '' ?>/cron.php?token=<?= substr(bin2hex(random_bytes(32)), 0, 20) ?>..."
                        </code>
                        <small>Consultez le fichier README_FR.md pour plus de détails</small>
                    </p>

                    <div class="alert alert-warning" style="margin-top: 20px;">
                        <strong>Sécurité :</strong> Pour des raisons de sécurité, veuillez supprimer ou renommer le fichier <code>install.php</code> après l'installation.
                    </div>
                </div>

                <div class="btn-group">
                    <a href="index.php" class="btn">Accéder à l'application →</a>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Validation du formulaire d'installation
        const form = document.querySelector('form[action="?step=4"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.querySelector('input[name="admin_password"]').value;
                const confirm = document.querySelector('input[name="admin_password_confirm"]').value;

                if (password !== confirm) {
                    e.preventDefault();
                    alert('Les mots de passe ne correspondent pas !');
                    return false;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    alert('Le mot de passe doit contenir au moins 8 caractères !');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
