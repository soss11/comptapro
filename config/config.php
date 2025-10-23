<?php
/**
 * Fichier de configuration principal
 * Mon Espace Client Pro - Portail Sécurisé Cabinet
 */

// Chargement des variables d'environnement
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Fonction helper pour récupérer les variables d'environnement
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Configuration de base
define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', env('APP_NAME', 'Mon Espace Client Pro'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Guadeloupe'));

// Configuration base de données
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'mon_espace_client_pro'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Configuration sécurité
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_TIME', (int)env('LOGIN_LOCKOUT_TIME', 900));

// Configuration upload
define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 10485760)); // 10MB
define('UPLOAD_ALLOWED_TYPES', env('UPLOAD_ALLOWED_TYPES', 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip'));
define('UPLOAD_PATH', APP_ROOT . env('UPLOAD_PATH', '/uploads/documents'));

// Configuration email
define('MAIL_HOST', env('MAIL_HOST', 'smtp.example.com'));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@example.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME));

// Configuration CRON
define('CRON_TOKEN', env('CRON_TOKEN', ''));
define('CRON_URL', env('CRON_URL', '/cron.php'));

// Options avancées
define('ENABLE_2FA', filter_var(env('ENABLE_2FA', 'false'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_IP_WHITELIST', filter_var(env('ENABLE_IP_WHITELIST', 'false'), FILTER_VALIDATE_BOOLEAN));
define('ADMIN_IP_WHITELIST', env('ADMIN_IP_WHITELIST', ''));
define('ENABLE_PWA', filter_var(env('ENABLE_PWA', 'false'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_RGPD', filter_var(env('ENABLE_RGPD', 'true'), FILTER_VALIDATE_BOOLEAN));
define('FILE_RETENTION_DAYS', (int)env('FILE_RETENTION_DAYS', 2555));
define('ENABLE_WATERMARK', filter_var(env('ENABLE_WATERMARK', 'false'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_API', filter_var(env('ENABLE_API', 'false'), FILTER_VALIDATE_BOOLEAN));
define('API_RATE_LIMIT', (int)env('API_RATE_LIMIT', 100));

// Configuration timezone
date_default_timezone_set(APP_TIMEZONE);

// Configuration erreurs
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}

// Configuration session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

return [
    'app' => [
        'name' => APP_NAME,
        'url' => APP_URL,
        'env' => APP_ENV,
        'debug' => APP_DEBUG,
        'timezone' => APP_TIMEZONE,
    ],
    'database' => [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'charset' => DB_CHARSET,
    ],
    'security' => [
        'session_lifetime' => SESSION_LIFETIME,
        'csrf_token_name' => CSRF_TOKEN_NAME,
        'max_login_attempts' => MAX_LOGIN_ATTEMPTS,
        'login_lockout_time' => LOGIN_LOCKOUT_TIME,
    ],
    'upload' => [
        'max_size' => UPLOAD_MAX_SIZE,
        'allowed_types' => explode(',', UPLOAD_ALLOWED_TYPES),
        'path' => UPLOAD_PATH,
    ],
    'mail' => [
        'host' => MAIL_HOST,
        'port' => MAIL_PORT,
        'username' => MAIL_USERNAME,
        'password' => MAIL_PASSWORD,
        'encryption' => MAIL_ENCRYPTION,
        'from_address' => MAIL_FROM_ADDRESS,
        'from_name' => MAIL_FROM_NAME,
    ],
];
