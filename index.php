<?php
/**
 * Point d'entrée principal - Mon Espace Client Pro
 */

// Vérifier si l'application est installée
if (!file_exists(__DIR__ . '/.env') || !file_exists(__DIR__ . '/config/installed.lock')) {
    header('Location: install.php');
    exit;
}

// Démarrer la session
session_start();

// Charger la configuration
require_once __DIR__ . '/config/config.php';

// Charger les helpers
require_once __DIR__ . '/app/helpers/Database.php';
require_once __DIR__ . '/app/helpers/Security.php';
require_once __DIR__ . '/app/helpers/Email.php';
require_once __DIR__ . '/app/helpers/Validator.php';
require_once __DIR__ . '/app/helpers/FileManager.php';

// Charger le router
require_once __DIR__ . '/app/Router.php';

// Initialiser le router
$router = new Router();

// Routes d'authentification
$router->add('GET', '/', 'AuthController@showLogin');
$router->add('GET', '/login', 'AuthController@showLogin');
$router->add('POST', '/login', 'AuthController@login');
$router->add('GET', '/logout', 'AuthController@logout');

// Routes admin
$router->add('GET', '/admin/dashboard', 'AdminController@dashboard');
$router->add('GET', '/admin/users', 'AdminController@users');
$router->add('GET', '/admin/users/create', 'AdminController@createUser');
$router->add('POST', '/admin/users/create', 'AdminController@storeUser');
$router->add('GET', '/admin/clients', 'AdminController@clients');
$router->add('GET', '/admin/clients/create', 'AdminController@createClient');
$router->add('POST', '/admin/clients/create', 'AdminController@storeClient');
$router->add('GET', '/admin/settings', 'AdminController@settings');
$router->add('POST', '/admin/settings', 'AdminController@updateSettings');
$router->add('GET', '/admin/logs', 'AdminController@logs');

// Routes collaborateur
$router->add('GET', '/collaborateur/dashboard', 'CollaborateurController@dashboard');
$router->add('GET', '/collaborateur/tasks', 'CollaborateurController@tasks');
$router->add('GET', '/collaborateur/tasks/create', 'CollaborateurController@createTask');
$router->add('POST', '/collaborateur/tasks/create', 'CollaborateurController@storeTask');
$router->add('POST', '/collaborateur/tasks/:id/update', 'CollaborateurController@updateTask');
$router->add('GET', '/collaborateur/clients', 'CollaborateurController@clients');
$router->add('GET', '/collaborateur/clients/:id', 'CollaborateurController@viewClient');
$router->add('POST', '/collaborateur/documents/upload', 'CollaborateurController@uploadDocument');
$router->add('GET', '/collaborateur/tickets', 'CollaborateurController@tickets');
$router->add('GET', '/collaborateur/tickets/:id', 'CollaborateurController@viewTicket');
$router->add('POST', '/collaborateur/tickets/:id/reply', 'CollaborateurController@replyTicket');
$router->add('GET', '/collaborateur/workflows', 'CollaborateurController@workflows');
$router->add('GET', '/collaborateur/workflows/:id', 'CollaborateurController@viewWorkflow');
$router->add('GET', '/collaborateur/veille', 'VeilleController@index');
$router->add('GET', '/collaborateur/veille/create', 'VeilleController@create');
$router->add('POST', '/collaborateur/veille/create', 'VeilleController@store');

// Routes client
$router->add('GET', '/client/dashboard', 'ClientController@dashboard');
$router->add('GET', '/client/documents', 'ClientController@documents');
$router->add('POST', '/client/documents/upload', 'ClientController@uploadDocument');
$router->add('GET', '/client/documents/download/:id', 'ClientController@downloadDocument');
$router->add('GET', '/client/tickets', 'ClientController@tickets');
$router->add('GET', '/client/tickets/create', 'ClientController@createTicket');
$router->add('POST', '/client/tickets/create', 'ClientController@storeTicket');
$router->add('GET', '/client/tickets/:id', 'ClientController@viewTicket');
$router->add('POST', '/client/tickets/:id/reply', 'ClientController@replyTicket');

// Routes veille (commune)
$router->add('GET', '/veille', 'VeilleController@index');
$router->add('GET', '/veille/view/:id', 'VeilleController@view');
$router->add('GET', '/veille/subscriptions', 'VeilleController@subscriptions');
$router->add('POST', '/veille/subscriptions', 'VeilleController@updateSubscriptions');

// Routes documents (téléchargement)
$router->add('GET', '/documents/download/:id', 'DocumentController@download');
$router->add('GET', '/documents/preview/:id', 'DocumentController@preview');

// Pages légales
$router->add('GET', '/mentions-legales', 'PageController@mentionsLegales');
$router->add('GET', '/cgu', 'PageController@cgu');

// Dispatcher
$router->dispatch();
