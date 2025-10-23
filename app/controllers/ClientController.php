<?php
require_once __DIR__ . '/../Controller.php';

class ClientController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireRole('client');
    }

    public function dashboard() {
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        // Récupérer les statistiques
        $stats = [
            'documents' => $this->db->fetch("SELECT COUNT(*) as count FROM documents WHERE client_id = ?", [$clientId]),
            'tickets' => $this->db->fetch("SELECT COUNT(*) as count FROM tickets WHERE client_id = ? AND status != 'ferme'", [$clientId]),
            'recent_documents' => $this->db->fetchAll("SELECT * FROM documents WHERE client_id = ? ORDER BY created_at DESC LIMIT 5", [$clientId]),
            'recent_tickets' => $this->db->fetchAll("SELECT * FROM tickets WHERE client_id = ? ORDER BY created_at DESC LIMIT 5", [$clientId]),
        ];

        $this->view('client/dashboard', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'user' => $user
        ]);
    }

    public function documents() {
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $sql = "SELECT d.*, u.first_name, u.last_name
                FROM documents d
                JOIN users u ON d.uploaded_by = u.id
                WHERE d.client_id = ?
                ORDER BY d.created_at DESC";

        $documents = $this->db->fetchAll($sql, [$clientId]);

        $this->view('client/documents', [
            'title' => 'Mes documents',
            'documents' => $documents,
            'user' => $user
        ]);
    }

    public function uploadDocument() {
        $this->validateCSRF();
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        if (!isset($_FILES['document'])) {
            return $this->json(['success' => false, 'error' => 'Aucun fichier'], 400);
        }

        $upload = FileManager::upload($_FILES['document'], 'documents', $clientId);

        if (!$upload['success']) {
            return $this->json($upload, 400);
        }

        // Insérer en base
        $sql = "INSERT INTO documents (client_id, uploaded_by, category, period_year, period_month,
                file_name, original_name, file_path, file_size, mime_type, extension, is_cabinet_document, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())";

        $this->db->query($sql, [
            $clientId,
            $user['id'],
            $_POST['category'] ?? null,
            $_POST['period_year'] ?? null,
            $_POST['period_month'] ?? null,
            $upload['file_name'],
            $upload['original_name'],
            $upload['relative_path'],
            $upload['file_size'],
            $upload['mime_type'],
            $upload['extension'],
            $_POST['description'] ?? null
        ]);

        Security::logAction($user['id'], 'DOCUMENT_UPLOAD', 'Upload: ' . $upload['original_name']);

        // Notifier le collaborateur assigné
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);
        if ($client['collaborator_id']) {
            $collaborator = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$client['collaborator_id']]);
            if ($collaborator) {
                Email::sendDocumentNotification($collaborator['email'], $upload['original_name'], $client['company_name'] ?? $client['first_name'] . ' ' . $client['last_name']);
            }
        }

        return $this->json(['success' => true, 'message' => 'Document uploadé avec succès']);
    }

    public function downloadDocument($id) {
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $document = $this->db->fetch("SELECT * FROM documents WHERE id = ? AND client_id = ?", [$id, $clientId]);

        if (!$document) {
            return $this->forbidden();
        }

        $filePath = APP_ROOT . $document['file_path'];

        if (!file_exists($filePath)) {
            die('Fichier introuvable');
        }

        // Incrémenter le compteur
        $this->db->query("UPDATE documents SET download_count = download_count + 1 WHERE id = ?", [$id]);

        Security::logAction($user['id'], 'DOCUMENT_DOWNLOAD', 'Téléchargement: ' . $document['original_name']);

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: attachment; filename="' . $document['original_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function tickets() {
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $tickets = $this->db->fetchAll("SELECT * FROM tickets WHERE client_id = ? ORDER BY created_at DESC", [$clientId]);

        $this->view('client/tickets', [
            'title' => 'Mes messages',
            'tickets' => $tickets,
            'user' => $user
        ]);
    }

    public function createTicket() {
        $user = $this->getAuthUser();

        $this->view('client/create_ticket', [
            'title' => 'Nouveau message',
            'user' => $user
        ]);
    }

    public function storeTicket() {
        $this->validateCSRF();
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $validator = new Validator($_POST);
        $validator->required('subject')->required('message');

        if ($validator->fails()) {
            return $this->redirect('/client/tickets/create', $validator->firstError(), 'error');
        }

        // Créer le ticket
        $sql = "INSERT INTO tickets (client_id, created_by, subject, status, priority, last_message_at, created_at)
                VALUES (?, ?, ?, 'ouvert', ?, NOW(), NOW())";

        $this->db->query($sql, [
            $clientId,
            $user['id'],
            Security::clean($_POST['subject']),
            $_POST['priority'] ?? 'normale'
        ]);

        $ticketId = $this->db->lastInsertId();

        // Ajouter le premier message
        $sql = "INSERT INTO ticket_messages (ticket_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->query($sql, [$ticketId, $user['id'], Security::clean($_POST['message'])]);

        Security::logAction($user['id'], 'TICKET_CREATE', 'Nouveau ticket: ' . $_POST['subject']);

        // Notifier le collaborateur
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);
        if ($client['collaborator_id']) {
            $collaborator = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$client['collaborator_id']]);
            if ($collaborator) {
                Email::sendTicketNotification($collaborator['email'], $_POST['subject'], $_POST['message']);
            }
        }

        return $this->redirect('/client/tickets', 'Message envoyé avec succès');
    }

    public function viewTicket($id) {
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $ticket = $this->db->fetch("SELECT * FROM tickets WHERE id = ? AND client_id = ?", [$id, $clientId]);

        if (!$ticket) {
            return $this->forbidden();
        }

        $messages = $this->db->fetchAll("SELECT tm.*, u.first_name, u.last_name, u.role
                                         FROM ticket_messages tm
                                         JOIN users u ON tm.user_id = u.id
                                         WHERE tm.ticket_id = ?
                                         ORDER BY tm.created_at ASC", [$id]);

        $this->view('client/view_ticket', [
            'title' => 'Message: ' . $ticket['subject'],
            'ticket' => $ticket,
            'messages' => $messages,
            'user' => $user
        ]);
    }

    public function replyTicket($id) {
        $this->validateCSRF();
        $user = $this->getAuthUser();
        $clientId = $user['client_id'];

        $ticket = $this->db->fetch("SELECT * FROM tickets WHERE id = ? AND client_id = ?", [$id, $clientId]);

        if (!$ticket) {
            return $this->forbidden();
        }

        $sql = "INSERT INTO ticket_messages (ticket_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->query($sql, [$id, $user['id'], Security::clean($_POST['message'])]);

        // Mettre à jour le ticket
        $this->db->query("UPDATE tickets SET last_message_at = NOW(), status = 'en_cours' WHERE id = ?", [$id]);

        return $this->redirect('/client/tickets/' . $id, 'Réponse envoyée');
    }
}
