<?php
require_once __DIR__ . '/../Controller.php';

class VeilleController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index() {
        $articles = $this->db->fetchAll("SELECT va.*, u.first_name, u.last_name FROM veille_articles va JOIN users u ON va.created_by = u.id ORDER BY va.published_at DESC, va.created_at DESC LIMIT 50");

        $this->view('veille/index', ['title' => 'Veille Réglementaire', 'articles' => $articles, 'user' => $this->getAuthUser()]);
    }

    public function view($id) {
        $article = $this->db->fetch("SELECT va.*, u.first_name, u.last_name FROM veille_articles va JOIN users u ON va.created_by = u.id WHERE va.id = ?", [$id]);

        if (!$article) return $this->notFound();

        $this->view('veille/view', ['title' => $article['title'], 'article' => $article, 'user' => $this->getAuthUser()]);
    }

    public function create() {
        $this->requireRole(['admin', 'collaborateur']);
        $this->view('veille/create', ['title' => 'Nouvel article de veille', 'user' => $this->getAuthUser()]);
    }

    public function store() {
        $this->requireRole(['admin', 'collaborateur']);
        $this->validateCSRF();

        $validator = new Validator($_POST);
        $validator->required('title')->required('content');

        if ($validator->fails()) return $this->redirect('/collaborateur/veille/create', $validator->firstError(), 'error');

        $sql = "INSERT INTO veille_articles (title, content, excerpt, source, source_url, tags, sector, location, is_important, published_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";

        $this->db->query($sql, [
            Security::clean($_POST['title']),
            $_POST['content'],
            Security::clean($_POST['excerpt'] ?? substr(strip_tags($_POST['content']), 0, 500)),
            Security::clean($_POST['source'] ?? null),
            Security::clean($_POST['source_url'] ?? null),
            Security::clean($_POST['tags'] ?? null),
            Security::clean($_POST['sector'] ?? null),
            Security::clean($_POST['location'] ?? 'National'),
            isset($_POST['is_important']) ? 1 : 0,
            $_SESSION['user_id']
        ]);

        $articleId = $this->db->lastInsertId();
        Security::logAction($_SESSION['user_id'], 'VEILLE_CREATE', 'Article créé: ' . $_POST['title']);

        // Envoyer notifications aux abonnés
        $this->sendNotificationsToSubscribers($articleId);

        return $this->redirect('/veille', 'Article publié avec succès');
    }

    public function subscriptions() {
        $user = $this->getAuthUser();
        $subscription = $this->db->fetch("SELECT * FROM veille_subscriptions WHERE user_id = ?", [$user['id']]);

        $this->view('veille/subscriptions', ['title' => 'Mes alertes', 'subscription' => $subscription, 'user' => $user]);
    }

    public function updateSubscriptions() {
        $this->validateCSRF();
        $user = $this->getAuthUser();

        // Vérifier si abonnement existe
        $existing = $this->db->fetch("SELECT * FROM veille_subscriptions WHERE user_id = ?", [$user['id']]);

        if ($existing) {
            $sql = "UPDATE veille_subscriptions SET tags = ?, sectors = ?, locations = ?, email_frequency = ?, is_active = ? WHERE user_id = ?";
            $this->db->query($sql, [$_POST['tags'] ?? null, $_POST['sectors'] ?? null, $_POST['locations'] ?? null, $_POST['email_frequency'] ?? 'immediate', isset($_POST['is_active']) ? 1 : 0, $user['id']]);
        } else {
            $sql = "INSERT INTO veille_subscriptions (user_id, tags, sectors, locations, email_frequency, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $this->db->query($sql, [$user['id'], $_POST['tags'] ?? null, $_POST['sectors'] ?? null, $_POST['locations'] ?? null, $_POST['email_frequency'] ?? 'immediate', isset($_POST['is_active']) ? 1 : 0]);
        }

        return $this->redirect('/veille/subscriptions', 'Abonnement mis à jour');
    }

    private function sendNotificationsToSubscribers($articleId) {
        $article = $this->db->fetch("SELECT * FROM veille_articles WHERE id = ?", [$articleId]);
        $subscriptions = $this->db->fetchAll("SELECT vs.*, u.email FROM veille_subscriptions vs JOIN users u ON vs.user_id = u.id WHERE vs.is_active = 1 AND vs.email_frequency = 'immediate'");

        foreach ($subscriptions as $sub) {
            // Vérifier si l'article correspond aux critères
            $match = false;

            if (!empty($sub['tags'])) {
                $subTags = explode(',', $sub['tags']);
                $articleTags = explode(',', $article['tags'] ?? '');
                if (array_intersect($subTags, $articleTags)) $match = true;
            }

            if (!empty($sub['sectors']) && strpos($sub['sectors'], $article['sector']) !== false) $match = true;
            if (!empty($sub['locations']) && strpos($sub['locations'], $article['location']) !== false) $match = true;

            if ($match) {
                Email::sendVeilleAlert($sub['email'], $article['title'], $article['excerpt'], $articleId);
                $this->db->query("INSERT INTO veille_notifications (user_id, article_id, sent_at) VALUES (?, ?, NOW())", [$sub['user_id'], $articleId]);
            }
        }
    }
}
