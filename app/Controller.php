<?php
/**
 * Classe Controller de base - Parent de tous les contrôleurs
 */

class Controller {

    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Vérifier le rôle de l'utilisateur
     */
    protected function requireRole($roles) {
        $this->requireAuth();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($_SESSION['user_role'], $roles)) {
            $this->forbidden();
        }
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    protected function getAuthUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$_SESSION['user_id']]);
    }

    /**
     * Charger une vue
     */
    protected function view($viewPath, $data = []) {
        extract($data);

        // Charger le layout selon le rôle
        $role = $_SESSION['user_role'] ?? 'guest';

        if ($role === 'guest') {
            require __DIR__ . '/views/layouts/auth.php';
        } else {
            require __DIR__ . '/views/layouts/app.php';
        }
    }

    /**
     * Rediriger
     */
    protected function redirect($path, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header('Location: ' . $path);
        exit;
    }

    /**
     * Retourner du JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Page 403 Forbidden
     */
    protected function forbidden() {
        http_response_code(403);
        require __DIR__ . '/views/errors/403.php';
        exit;
    }

    /**
     * Obtenir le message flash
     */
    protected function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }

    /**
     * Valider le token CSRF
     */
    protected function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
            if (!Security::verifyCSRFToken($token)) {
                die('Token CSRF invalide');
            }
        }
    }
}
