<?php
/**
 * Script CRON - Mon Espace Client Pro
 * À exécuter régulièrement (toutes les 15 minutes recommandé)
 *
 * Configuration cPanel:
 * */15 * * * * curl -s "https://votre-domaine.fr/cron.php?token=VOTRE_TOKEN" > /dev/null 2>&1
 */

// Charger la configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/helpers/Database.php';
require_once __DIR__ . '/app/helpers/Security.php';
require_once __DIR__ . '/app/helpers/Email.php';
require_once __DIR__ . '/app/helpers/FileManager.php';

// Vérifier le token de sécurité
$token = $_GET['token'] ?? '';
if (empty(CRON_TOKEN) || $token !== CRON_TOKEN) {
    http_response_code(403);
    die('Accès refusé');
}

// Logger le démarrage
$logFile = APP_ROOT . '/logs/cron.log';
$logMessage = "[" . date('Y-m-d H:i:s') . "] CRON démarré\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

$db = Database::getInstance();

// 1. Envoyer les rappels de tâches à échéance proche
$sql = "SELECT t.*, u.email, u.first_name, u.last_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE t.status != 'terminee'
          AND t.status != 'annulee'
          AND t.due_date IS NOT NULL
          AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL t.reminder_days_before DAY)
          AND t.reminder_sent = 0";

$tasks = $db->fetchAll($sql);

foreach ($tasks as $task) {
    $sent = Email::sendTaskReminder(
        $task['email'],
        $task['title'],
        $task['due_date']
    );

    if ($sent) {
        // Marquer le rappel comme envoyé
        $db->query("UPDATE tasks SET reminder_sent = 1 WHERE id = ?", [$task['id']]);
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Rappel tâche #{$task['id']} envoyé à {$task['email']}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// 2. Envoyer les alertes de veille (fréquence quotidienne)
$hour = date('H');
if ($hour === '09') { // Envoyer à 9h
    $sql = "SELECT vs.*, u.email, u.first_name
            FROM veille_subscriptions vs
            JOIN users u ON vs.user_id = u.id
            WHERE vs.is_active = 1
              AND vs.email_frequency = 'daily'";

    $subscriptions = $db->fetchAll($sql);

    foreach ($subscriptions as $sub) {
        // Récupérer les articles non envoyés des dernières 24h
        $sql = "SELECT va.*
                FROM veille_articles va
                WHERE va.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                  AND va.id NOT IN (
                      SELECT article_id FROM veille_notifications WHERE user_id = ?
                  )
                ORDER BY va.created_at DESC";

        $articles = $db->fetchAll($sql, [$sub['user_id']]);

        if (count($articles) > 0) {
            // Envoyer un digest
            $subject = "Digest veille réglementaire - " . count($articles) . " nouveaux articles";
            $body = "<h3>Bonjour {$sub['first_name']},</h3>";
            $body .= "<p>Voici les nouveaux articles de veille réglementaire des dernières 24 heures :</p>";

            foreach ($articles as $article) {
                $body .= "<div style='margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ddd;'>";
                $body .= "<h4>" . Security::escape($article['title']) . "</h4>";
                $body .= "<p>" . Security::escape($article['excerpt']) . "</p>";
                $body .= "<a href='" . APP_URL . "/veille/view/{$article['id']}' class='button'>Lire l'article</a>";
                $body .= "</div>";
            }

            $sent = Email::send($sub['email'], $subject, $body);

            if ($sent) {
                // Marquer comme envoyé
                foreach ($articles as $article) {
                    $db->query("INSERT INTO veille_notifications (user_id, article_id, sent_at) VALUES (?, ?, NOW())",
                               [$sub['user_id'], $article['id']]);
                }

                $logMessage = "[" . date('Y-m-d H:i:s') . "] Digest veille envoyé à {$sub['email']} (" . count($articles) . " articles)\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
        }
    }
}

// 3. Nettoyer les fichiers temporaires anciens (> 24h)
FileManager::cleanTempFiles(24);
$logMessage = "[" . date('Y-m-d H:i:s') . "] Fichiers temporaires nettoyés\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// 4. Nettoyer les anciens logs d'audit (> 1 an)
if (date('H:i') === '03:00') { // Exécuter à 3h du matin
    $sql = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $db->query($sql);

    $logMessage = "[" . date('Y-m-d H:i:s') . "] Anciens logs d'audit nettoyés\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// 5. Supprimer les anciens fichiers selon la rétention (si > FILE_RETENTION_DAYS)
if (ENABLE_RGPD && FILE_RETENTION_DAYS > 0) {
    if (date('H:i') === '02:00') { // Exécuter à 2h du matin
        $sql = "SELECT * FROM documents
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND is_cabinet_document = 0";

        $oldDocuments = $db->fetchAll($sql, [FILE_RETENTION_DAYS]);

        foreach ($oldDocuments as $doc) {
            $filePath = APP_ROOT . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $db->query("DELETE FROM documents WHERE id = ?", [$doc['id']]);
        }

        if (count($oldDocuments) > 0) {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] " . count($oldDocuments) . " documents expirés supprimés (rétention: " . FILE_RETENTION_DAYS . " jours)\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
}

// Logger la fin
$logMessage = "[" . date('Y-m-d H:i:s') . "] CRON terminé\n\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

echo "CRON exécuté avec succès";
