<?php
/**
 * Classe Security - Gestion de la sécurité (CSRF, XSS, validation)
 */

class Security {

    /**
     * Générer un token CSRF
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Vérifier un token CSRF
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Échapper les données pour affichage HTML (protection XSS)
     */
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Nettoyer une chaîne de caractères
     */
    public static function clean($data) {
        return trim(strip_tags($data));
    }

    /**
     * Valider une adresse email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valider un numéro de téléphone (format français/Guadeloupe)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+590|0590|0[1-9])[0-9]{8,9}$/', $phone);
    }

    /**
     * Hasher un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifier un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Générer un token aléatoire
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Générer un UUID v4
     */
    public static function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Vérifier les tentatives de connexion
     */
    public static function checkLoginAttempts($email) {
        $db = Database::getInstance();
        $sql = "SELECT attempts, locked_until FROM users WHERE email = ?";
        $user = $db->fetch($sql, [$email]);

        if (!$user) {
            return true; // Utilisateur inexistant, on laisse continuer pour ne pas révéler l'existence
        }

        // Vérifier si le compte est verrouillé
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return false;
        }

        // Réinitialiser si le délai est passé
        if ($user['locked_until'] && strtotime($user['locked_until']) <= time()) {
            $db->query("UPDATE users SET attempts = 0, locked_until = NULL WHERE email = ?", [$email]);
        }

        return $user['attempts'] < MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Incrémenter les tentatives de connexion
     */
    public static function incrementLoginAttempts($email) {
        $db = Database::getInstance();
        $db->query("UPDATE users SET attempts = attempts + 1 WHERE email = ?", [$email]);

        // Vérifier si on doit verrouiller
        $sql = "SELECT attempts FROM users WHERE email = ?";
        $user = $db->fetch($sql, [$email]);

        if ($user && $user['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $db->query("UPDATE users SET locked_until = ? WHERE email = ?", [$lockedUntil, $email]);
        }
    }

    /**
     * Réinitialiser les tentatives de connexion
     */
    public static function resetLoginAttempts($email) {
        $db = Database::getInstance();
        $db->query("UPDATE users SET attempts = 0, locked_until = NULL WHERE email = ?", [$email]);
    }

    /**
     * Vérifier l'IP whitelist pour admin
     */
    public static function checkIPWhitelist() {
        if (!ENABLE_IP_WHITELIST || empty(ADMIN_IP_WHITELIST)) {
            return true;
        }

        $allowedIPs = explode(',', ADMIN_IP_WHITELIST);
        $allowedIPs = array_map('trim', $allowedIPs);
        $clientIP = self::getClientIP();

        return in_array($clientIP, $allowedIPs);
    }

    /**
     * Récupérer l'IP du client
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Logger une action pour audit
     */
    public static function logAction($userId, $action, $details = null, $ipAddress = null) {
        $db = Database::getInstance();

        if ($ipAddress === null) {
            $ipAddress = self::getClientIP();
        }

        $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())";

        try {
            $db->query($sql, [$userId, $action, $details, $ipAddress]);
        } catch (Exception $e) {
            error_log('Failed to log action: ' . $e->getMessage());
        }
    }
}
