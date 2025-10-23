<?php
require_once __DIR__ . '/../Controller.php';

class DocumentController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function download($id) {
        $user = $this->getAuthUser();
        $role = $user['role'];

        // Vérifier les permissions selon le rôle
        if ($role === 'client') {
            $clientId = $user['client_id'];
            $document = $this->db->fetch("SELECT * FROM documents WHERE id = ? AND client_id = ?", [$id, $clientId]);
        } else {
            // Admin ou collaborateur peut accéder à tous les documents
            $document = $this->db->fetch("SELECT * FROM documents WHERE id = ?", [$id]);
        }

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

    public function preview($id) {
        $user = $this->getAuthUser();
        $role = $user['role'];

        if ($role === 'client') {
            $clientId = $user['client_id'];
            $document = $this->db->fetch("SELECT * FROM documents WHERE id = ? AND client_id = ?", [$id, $clientId]);
        } else {
            $document = $this->db->fetch("SELECT * FROM documents WHERE id = ?", [$id]);
        }

        if (!$document) {
            return $this->forbidden();
        }

        $filePath = APP_ROOT . $document['file_path'];

        if (!file_exists($filePath)) {
            die('Fichier introuvable');
        }

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: inline; filename="' . $document['original_name'] . '"');
        readfile($filePath);
        exit;
    }
}
