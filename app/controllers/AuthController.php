<?php
/**
 * AuthController - Gestion de l'authentification
 */

require_once __DIR__ . '/../Controller.php';

class AuthController extends Controller {

    /**
     * Afficher la page de connexion
     */
    public function showLogin() {
        // Si déjà connecté, rediriger vers le dashboard approprié
        if (isset($_SESSION['user_id'])) {
            return $this->redirectToDashboard();
        }

        $flash = $this->getFlashMessage();
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Traiter la connexion
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/login');
        }

        $email = Security::clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        $validator = new Validator($_POST);
        $validator->required('email', 'L\'email est requis')
                  ->email('email', 'Email invalide')
                  ->required('password', 'Le mot de passe est requis');

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            return $this->redirect('/login', $validator->firstError(), 'error');
        }

        // Vérifier les tentatives de connexion
        if (!Security::checkLoginAttempts($email)) {
            return $this->redirect('/login', 'Compte temporairement verrouillé suite à trop de tentatives. Réessayez dans ' . (LOGIN_LOCKOUT_TIME / 60) . ' minutes.', 'error');
        }

        // Récupérer l'utilisateur
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $user = $this->db->fetch($sql, [$email]);

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Security::incrementLoginAttempts($email);
            return $this->redirect('/login', 'Identifiants incorrects', 'error');
        }

        // Réinitialiser les tentatives
        Security::resetLoginAttempts($email);

        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['client_id'] = $user['client_id'];

        // Mettre à jour la dernière connexion
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        // Logger l'action
        Security::logAction($user['id'], 'LOGIN', 'Connexion réussie');

        // Rediriger vers le dashboard approprié
        return $this->redirectToDashboard();
    }

    /**
     * Déconnexion
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            Security::logAction($_SESSION['user_id'], 'LOGOUT', 'Déconnexion');
        }

        session_destroy();
        return $this->redirect('/login', 'Vous avez été déconnecté', 'success');
    }

    /**
     * Rediriger vers le dashboard approprié selon le rôle
     */
    private function redirectToDashboard() {
        $role = $_SESSION['user_role'] ?? 'client';

        switch ($role) {
            case 'admin':
                header('Location: /admin/dashboard');
                break;
            case 'collaborateur':
                header('Location: /collaborateur/dashboard');
                break;
            case 'client':
                header('Location: /client/dashboard');
                break;
            default:
                header('Location: /login');
        }
        exit;
    }
}
