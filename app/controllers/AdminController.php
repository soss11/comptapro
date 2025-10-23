<?php
require_once __DIR__ . '/../Controller.php';

class AdminController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('admin');
    }

    public function dashboard() {
        $user = $this->getAuthUser();

        // Statistiques globales
        $stats = [
            'total_users' => $this->db->fetch("SELECT COUNT(*) as count FROM users"),
            'total_clients' => $this->db->fetch("SELECT COUNT(*) as count FROM clients WHERE status = 'actif'"),
            'total_documents' => $this->db->fetch("SELECT COUNT(*) as count FROM documents"),
            'total_tickets' => $this->db->fetch("SELECT COUNT(*) as count FROM tickets WHERE status != 'ferme'"),
            'total_tasks' => $this->db->fetch("SELECT COUNT(*) as count FROM tasks WHERE status != 'terminee'"),
            'tasks_overdue' => $this->db->fetch("SELECT COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'terminee'"),
        ];

        // Activité récente
        $recent_activity = $this->db->fetchAll("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");

        $this->view('admin/dashboard', [
            'title' => 'Tableau de bord Administration',
            'stats' => $stats,
            'recent_activity' => $recent_activity,
            'user' => $user
        ]);
    }

    public function users() {
        $users = $this->db->fetchAll("SELECT u.*, c.code as client_code
                                      FROM users u
                                      LEFT JOIN clients c ON u.client_id = c.id
                                      ORDER BY u.created_at DESC");

        $this->view('admin/users', [
            'title' => 'Gestion des utilisateurs',
            'users' => $users,
            'user' => $this->getAuthUser()
        ]);
    }

    public function createUser() {
        $clients = $this->db->fetchAll("SELECT * FROM clients WHERE status = 'actif' ORDER BY code");

        $this->view('admin/create_user', [
            'title' => 'Créer un utilisateur',
            'clients' => $clients,
            'user' => $this->getAuthUser()
        ]);
    }

    public function storeUser() {
        $this->validateCSRF();

        $validator = new Validator($_POST);
        $validator->required('email')->email('email')
                  ->unique('email', 'users')
                  ->required('first_name')
                  ->required('last_name')
                  ->required('role')
                  ->required('password')
                  ->min('password', 8);

        if ($validator->fails()) {
            return $this->redirect('/admin/users/create', $validator->firstError(), 'error');
        }

        $sql = "INSERT INTO users (email, password, first_name, last_name, phone, role, client_id, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";

        $this->db->query($sql, [
            Security::clean($_POST['email']),
            Security::hashPassword($_POST['password']),
            Security::clean($_POST['first_name']),
            Security::clean($_POST['last_name']),
            Security::clean($_POST['phone'] ?? null),
            $_POST['role'],
            $_POST['client_id'] ?? null
        ]);

        Security::logAction($_SESSION['user_id'], 'USER_CREATE', 'Utilisateur créé: ' . $_POST['email']);

        // Envoyer l'email de bienvenue
        Email::sendWelcomeEmail($_POST['email'], $_POST['first_name'] . ' ' . $_POST['last_name'], $_POST['password']);

        return $this->redirect('/admin/users', 'Utilisateur créé avec succès');
    }

    public function clients() {
        $sql = "SELECT c.*, u.first_name, u.last_name
                FROM clients c
                LEFT JOIN users u ON c.collaborator_id = u.id
                WHERE c.status = 'actif'
                ORDER BY c.code";

        $clients = $this->db->fetchAll($sql);

        $this->view('admin/clients', [
            'title' => 'Gestion des clients',
            'clients' => $clients,
            'user' => $this->getAuthUser()
        ]);
    }

    public function createClient() {
        $collaborators = $this->db->fetchAll("SELECT * FROM users WHERE role = 'collaborateur' AND is_active = 1");

        $this->view('admin/create_client', [
            'title' => 'Créer un dossier client',
            'collaborators' => $collaborators,
            'user' => $this->getAuthUser()
        ]);
    }

    public function storeClient() {
        $this->validateCSRF();

        $validator = new Validator($_POST);
        $validator->required('code')->unique('code', 'clients')
                  ->required('email')->email('email');

        if ($validator->fails()) {
            return $this->redirect('/admin/clients/create', $validator->firstError(), 'error');
        }

        $sql = "INSERT INTO clients (code, company_name, first_name, last_name, siret, address, postal_code,
                city, country, phone, email, activity_sector, collaborator_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif', NOW())";

        $this->db->query($sql, [
            Security::clean($_POST['code']),
            Security::clean($_POST['company_name'] ?? null),
            Security::clean($_POST['first_name'] ?? null),
            Security::clean($_POST['last_name'] ?? null),
            Security::clean($_POST['siret'] ?? null),
            Security::clean($_POST['address'] ?? null),
            Security::clean($_POST['postal_code'] ?? null),
            Security::clean($_POST['city'] ?? null),
            Security::clean($_POST['country'] ?? 'France'),
            Security::clean($_POST['phone'] ?? null),
            Security::clean($_POST['email']),
            Security::clean($_POST['activity_sector'] ?? null),
            $_POST['collaborator_id'] ?? null
        ]);

        $clientId = $this->db->lastInsertId();

        Security::logAction($_SESSION['user_id'], 'CLIENT_CREATE', 'Client créé: ' . $_POST['code']);

        return $this->redirect('/admin/clients', 'Dossier client créé avec succès');
    }

    public function settings() {
        $settings = $this->db->fetchAll("SELECT * FROM settings ORDER BY setting_key");

        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }

        $this->view('admin/settings', [
            'title' => 'Paramètres système',
            'settings' => $settingsArray,
            'user' => $this->getAuthUser()
        ]);
    }

    public function updateSettings() {
        $this->validateCSRF();

        foreach ($_POST as $key => $value) {
            if ($key === 'csrf_token') continue;

            $this->db->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [
                $value,
                $key
            ]);
        }

        Security::logAction($_SESSION['user_id'], 'SETTINGS_UPDATE', 'Paramètres mis à jour');

        return $this->redirect('/admin/settings', 'Paramètres mis à jour avec succès');
    }

    public function logs() {
        $logs = $this->db->fetchAll("SELECT al.*, u.first_name, u.last_name
                                     FROM audit_logs al
                                     LEFT JOIN users u ON al.user_id = u.id
                                     ORDER BY al.created_at DESC
                                     LIMIT 200");

        $this->view('admin/logs', [
            'title' => 'Logs d\'audit',
            'logs' => $logs,
            'user' => $this->getAuthUser()
        ]);
    }
}
