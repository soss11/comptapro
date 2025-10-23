<?php
require_once __DIR__ . '/../Controller.php';

class CollaborateurController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireRole(['admin', 'collaborateur']);
    }

    public function dashboard() {
        $user = $this->getAuthUser();
        $userId = $user['id'];

        $stats = [
            'my_tasks' => $this->db->fetch("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND status != 'terminee'", [$userId]),
            'overdue_tasks' => $this->db->fetch("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = ? AND due_date < CURDATE() AND status != 'terminee'", [$userId]),
            'my_tickets' => $this->db->fetch("SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ? AND status != 'ferme'", [$userId]),
            'my_clients' => $this->db->fetch("SELECT COUNT(*) as count FROM clients WHERE collaborator_id = ? AND status = 'actif'", [$userId]),
        ];

        $recent_tasks = $this->db->fetchAll("SELECT t.*, c.code as client_code FROM tasks t LEFT JOIN clients c ON t.client_id = c.id WHERE t.assigned_to = ? ORDER BY t.due_date ASC LIMIT 5", [$userId]);
        $recent_tickets = $this->db->fetchAll("SELECT t.*, c.code as client_code FROM tickets t LEFT JOIN clients c ON t.client_id = c.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC LIMIT 5", [$userId]);

        $this->view('collaborateur/dashboard', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'recent_tasks' => $recent_tasks,
            'recent_tickets' => $recent_tickets,
            'user' => $user
        ]);
    }

    public function tasks() {
        $user = $this->getAuthUser();
        $tasks = $this->db->fetchAll("SELECT t.*, c.code as client_code, c.company_name FROM tasks t LEFT JOIN clients c ON t.client_id = c.id WHERE t.assigned_to = ? ORDER BY t.due_date ASC", [$user['id']]);

        $this->view('collaborateur/tasks', [
            'title' => 'Mes tâches',
            'tasks' => $tasks,
            'user' => $user
        ]);
    }

    public function createTask() {
        $clients = $this->db->fetchAll("SELECT * FROM clients WHERE status = 'actif' ORDER BY code");
        $this->view('collaborateur/create_task', ['title' => 'Nouvelle tâche', 'clients' => $clients, 'user' => $this->getAuthUser()]);
    }

    public function storeTask() {
        $this->validateCSRF();
        $user = $this->getAuthUser();

        $sql = "INSERT INTO tasks (client_id, assigned_to, created_by, title, description, task_type, status, priority, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $this->db->query($sql, [$_POST['client_id'] ?? null, $_POST['assigned_to'] ?? $user['id'], $user['id'], $_POST['title'], $_POST['description'] ?? null, $_POST['task_type'] ?? null, 'a_faire', $_POST['priority'] ?? 'normale', $_POST['due_date'] ?? null]);

        Security::logAction($user['id'], 'TASK_CREATE', 'Tâche créée: ' . $_POST['title']);
        return $this->redirect('/collaborateur/tasks', 'Tâche créée avec succès');
    }

    public function updateTask($id) {
        $this->validateCSRF();
        $status = $_POST['status'] ?? 'a_faire';
        $completedAt = $status === 'terminee' ? date('Y-m-d H:i:s') : null;

        $this->db->query("UPDATE tasks SET status = ?, completed_at = ? WHERE id = ?", [$status, $completedAt, $id]);
        Security::logAction($_SESSION['user_id'], 'TASK_UPDATE', "Tâche #$id: $status");

        return $this->json(['success' => true]);
    }

    public function clients() {
        $user = $this->getAuthUser();
        $clients = $this->db->fetchAll("SELECT * FROM clients WHERE collaborator_id = ? AND status = 'actif' ORDER BY code", [$user['id']]);

        $this->view('collaborateur/clients', ['title' => 'Mes clients', 'clients' => $clients, 'user' => $user]);
    }

    public function viewClient($id) {
        $user = $this->getAuthUser();
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ? AND collaborator_id = ?", [$id, $user['id']]);

        if (!$client) return $this->forbidden();

        $documents = $this->db->fetchAll("SELECT * FROM documents WHERE client_id = ? ORDER BY created_at DESC LIMIT 20", [$id]);
        $tasks = $this->db->fetchAll("SELECT * FROM tasks WHERE client_id = ? ORDER BY due_date DESC LIMIT 10", [$id]);

        $this->view('collaborateur/view_client', ['title' => 'Dossier: ' . $client['code'], 'client' => $client, 'documents' => $documents, 'tasks' => $tasks, 'user' => $user]);
    }

    public function uploadDocument() {
        $this->validateCSRF();
        $user = $this->getAuthUser();

        if (!isset($_FILES['document']) || !isset($_POST['client_id'])) {
            return $this->json(['success' => false, 'error' => 'Données manquantes'], 400);
        }

        $clientId = $_POST['client_id'];
        $upload = FileManager::upload($_FILES['document'], 'documents', $clientId);

        if (!$upload['success']) return $this->json($upload, 400);

        $sql = "INSERT INTO documents (client_id, uploaded_by, category, period_year, period_month, file_name, original_name, file_path, file_size, mime_type, extension, is_cabinet_document, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";
        $this->db->query($sql, [$clientId, $user['id'], $_POST['category'] ?? null, $_POST['period_year'] ?? null, $_POST['period_month'] ?? null, $upload['file_name'], $upload['original_name'], $upload['relative_path'], $upload['file_size'], $upload['mime_type'], $upload['extension'], $_POST['description'] ?? null]);

        Security::logAction($user['id'], 'DOCUMENT_UPLOAD', 'Document cabinet: ' . $upload['original_name']);

        // Notifier le client
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);
        $clientUser = $this->db->fetch("SELECT * FROM users WHERE client_id = ?", [$clientId]);
        if ($clientUser) {
            Email::sendDocumentNotification($clientUser['email'], $upload['original_name'], $client['company_name'] ?? $client['first_name'] . ' ' . $client['last_name']);
        }

        return $this->json(['success' => true, 'message' => 'Document uploadé']);
    }

    public function tickets() {
        $user = $this->getAuthUser();
        $tickets = $this->db->fetchAll("SELECT t.*, c.code as client_code FROM tickets t LEFT JOIN clients c ON t.client_id = c.id WHERE t.assigned_to = ? ORDER BY t.created_at DESC", [$user['id']]);

        $this->view('collaborateur/tickets', ['title' => 'Messagerie', 'tickets' => $tickets, 'user' => $user]);
    }

    public function viewTicket($id) {
        $user = $this->getAuthUser();
        $ticket = $this->db->fetch("SELECT * FROM tickets WHERE id = ? AND assigned_to = ?", [$id, $user['id']]);

        if (!$ticket) return $this->forbidden();

        $messages = $this->db->fetchAll("SELECT tm.*, u.first_name, u.last_name, u.role FROM ticket_messages tm JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC", [$id]);

        $this->view('collaborateur/view_ticket', ['title' => 'Ticket: ' . $ticket['subject'], 'ticket' => $ticket, 'messages' => $messages, 'user' => $user]);
    }

    public function replyTicket($id) {
        $this->validateCSRF();
        $user = $this->getAuthUser();

        $sql = "INSERT INTO ticket_messages (ticket_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->query($sql, [$id, $user['id'], Security::clean($_POST['message'])]);
        $this->db->query("UPDATE tickets SET last_message_at = NOW(), status = 'en_cours' WHERE id = ?", [$id]);

        return $this->redirect('/collaborateur/tickets/' . $id, 'Réponse envoyée');
    }

    public function workflows() {
        $instances = $this->db->fetchAll("SELECT wi.*, w.name as workflow_name, c.code as client_code FROM workflow_instances wi JOIN workflows w ON wi.workflow_id = w.id JOIN clients c ON wi.client_id = c.id WHERE wi.assigned_to = ? ORDER BY wi.started_at DESC", [$this->getAuthUser()['id']]);

        $this->view('collaborateur/workflows', ['title' => 'Workflows', 'instances' => $instances, 'user' => $this->getAuthUser()]);
    }

    public function viewWorkflow($id) {
        $instance = $this->db->fetch("SELECT wi.*, w.name as workflow_name, c.code as client_code FROM workflow_instances wi JOIN workflows w ON wi.workflow_id = w.id JOIN clients c ON wi.client_id = c.id WHERE wi.id = ?", [$id]);

        if (!$instance) return $this->notFound();

        $steps = $this->db->fetchAll("SELECT wis.*, ws.step_name, ws.step_description FROM workflow_instance_steps wis JOIN workflow_steps ws ON wis.workflow_step_id = ws.id WHERE wis.workflow_instance_id = ? ORDER BY ws.step_order", [$id]);

        $this->view('collaborateur/view_workflow', ['title' => 'Workflow: ' . $instance['workflow_name'], 'instance' => $instance, 'steps' => $steps, 'user' => $this->getAuthUser()]);
    }
}
