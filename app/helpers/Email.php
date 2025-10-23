<?php
/**
 * Classe Email - Gestion des envois d'emails via SMTP
 */

class Email {

    /**
     * Envoyer un email via SMTP
     */
    public static function send($to, $subject, $body, $attachments = []) {
        try {
            // Headers
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>';
            $headers[] = 'Reply-To: ' . MAIL_FROM_ADDRESS;
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            // Utilisation de la fonction mail() standard pour environnement mutualisé
            // Note: Configurer SMTP via ini_set ou configuration serveur
            if (!empty(MAIL_HOST) && MAIL_HOST !== 'smtp.example.com') {
                ini_set('SMTP', MAIL_HOST);
                ini_set('smtp_port', MAIL_PORT);
            }

            // Corps HTML
            $htmlBody = self::wrapEmailTemplate($body);

            // Boundary pour multipart
            $boundary = md5(uniqid(time()));

            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlBody . "\r\n";

            // Pièces jointes
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $fileContent = chunk_split(base64_encode(file_get_contents($attachment['path'])));
                    $fileName = $attachment['name'] ?? basename($attachment['path']);

                    $message .= "--{$boundary}\r\n";
                    $message .= "Content-Type: application/octet-stream; name=\"{$fileName}\"\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $message .= $fileContent . "\r\n";
                }
            }

            $message .= "--{$boundary}--";

            $result = mail($to, $subject, $message, implode("\r\n", $headers));

            // Logger l'envoi
            self::logEmail($to, $subject, $result ? 'sent' : 'failed');

            return $result;

        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            self::logEmail($to, $subject, 'error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML pour les emails
     */
    private static function wrapEmailTemplate($content) {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . APP_NAME . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
        .content { padding: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #888; text-align: center; }
        .button { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>' . APP_NAME . '</h2>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
            <p>&copy; ' . date('Y') . ' ' . APP_NAME . ' - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Logger les emails envoyés
     */
    private static function logEmail($to, $subject, $status) {
        $db = Database::getInstance();
        $sql = "INSERT INTO email_logs (recipient, subject, status, sent_at) VALUES (?, ?, ?, NOW())";
        try {
            $db->query($sql, [$to, $subject, $status]);
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer un email de bienvenue
     */
    public static function sendWelcomeEmail($email, $name, $tempPassword = null) {
        $subject = 'Bienvenue sur ' . APP_NAME;
        $body = '<h3>Bienvenue ' . Security::escape($name) . ',</h3>';
        $body .= '<p>Votre compte a été créé avec succès sur notre portail.</p>';

        if ($tempPassword) {
            $body .= '<p><strong>Identifiant :</strong> ' . Security::escape($email) . '</p>';
            $body .= '<p><strong>Mot de passe temporaire :</strong> ' . Security::escape($tempPassword) . '</p>';
            $body .= '<p style="color: #e74c3c;"><strong>Important :</strong> Veuillez changer ce mot de passe lors de votre première connexion.</p>';
        }

        $body .= '<p><a href="' . APP_URL . '" class="button">Accéder au portail</a></p>';

        return self::send($email, $subject, $body);
    }

    /**
     * Envoyer une notification de nouveau document
     */
    public static function sendDocumentNotification($email, $documentName, $clientName) {
        $subject = 'Nouveau document disponible';
        $body = '<h3>Nouveau document disponible</h3>';
        $body .= '<p>Un nouveau document a été déposé pour le dossier <strong>' . Security::escape($clientName) . '</strong>.</p>';
        $body .= '<p><strong>Document :</strong> ' . Security::escape($documentName) . '</p>';
        $body .= '<p><a href="' . APP_URL . '" class="button">Consulter mes documents</a></p>';

        return self::send($email, $subject, $body);
    }

    /**
     * Envoyer une notification de nouveau ticket
     */
    public static function sendTicketNotification($email, $ticketSubject, $message) {
        $subject = 'Nouveau message : ' . $ticketSubject;
        $body = '<h3>Nouveau message reçu</h3>';
        $body .= '<p><strong>Sujet :</strong> ' . Security::escape($ticketSubject) . '</p>';
        $body .= '<p><strong>Message :</strong></p>';
        $body .= '<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #3498db;">';
        $body .= nl2br(Security::escape($message));
        $body .= '</div>';
        $body .= '<p><a href="' . APP_URL . '" class="button">Répondre au message</a></p>';

        return self::send($email, $subject, $body);
    }

    /**
     * Envoyer un rappel de tâche
     */
    public static function sendTaskReminder($email, $taskTitle, $dueDate) {
        $subject = 'Rappel : Tâche à échéance proche';
        $body = '<h3>Rappel de tâche</h3>';
        $body .= '<p>Une tâche arrive bientôt à échéance :</p>';
        $body .= '<p><strong>Tâche :</strong> ' . Security::escape($taskTitle) . '</p>';
        $body .= '<p><strong>Échéance :</strong> ' . date('d/m/Y', strtotime($dueDate)) . '</p>';
        $body .= '<p><a href="' . APP_URL . '" class="button">Voir la tâche</a></p>';

        return self::send($email, $subject, $body);
    }

    /**
     * Envoyer un article de veille
     */
    public static function sendVeilleAlert($email, $articleTitle, $articleExcerpt, $articleId) {
        $subject = 'Nouvelle veille réglementaire : ' . $articleTitle;
        $body = '<h3>Nouvelle information réglementaire</h3>';
        $body .= '<p><strong>' . Security::escape($articleTitle) . '</strong></p>';
        $body .= '<p>' . Security::escape($articleExcerpt) . '</p>';
        $body .= '<p><a href="' . APP_URL . '/veille/view/' . $articleId . '" class="button">Lire l\'article complet</a></p>';

        return self::send($email, $subject, $body);
    }
}
