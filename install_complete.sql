-- ============================================
-- INSTALLATION COMPLETE - Mon Espace Client Pro
-- ============================================
-- Fichier à exécuter dans phpMyAdmin
-- ============================================

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS `mon_espace_client_pro` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utiliser la base de données
USE `mon_espace_client_pro`;

-- ============================================
-- SCHEMA : Création des tables
-- ============================================
-- Mon Espace Client Pro - Schéma de base de données
-- Version 1.0
-- Encodage: UTF-8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- Table: users
-- Description: Utilisateurs du système (admin, collaborateurs, clients)
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','collaborateur','client') NOT NULL DEFAULT 'client',
  `client_id` int(11) DEFAULT NULL COMMENT 'Lien vers la table clients si role=client',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `two_fa_secret` varchar(255) DEFAULT NULL,
  `two_fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: clients
-- Description: Dossiers clients du cabinet
-- ============================================
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Code dossier interne',
  `company_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `siret` varchar(14) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'France',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `activity_sector` varchar(255) DEFAULT NULL,
  `collaborator_id` int(11) DEFAULT NULL COMMENT 'Collaborateur assigné',
  `status` enum('actif','inactif','archive') NOT NULL DEFAULT 'actif',
  `notes` text DEFAULT NULL,
  `custom_fields` text DEFAULT NULL COMMENT 'JSON pour champs personnalisés',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `collaborator_id` (`collaborator_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: documents
-- Description: Documents uploadés (par clients ou cabinet)
-- ============================================
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL COMMENT 'User ID',
  `category` varchar(100) DEFAULT NULL COMMENT 'factures, releves, bilans, etc.',
  `period_year` int(11) DEFAULT NULL,
  `period_month` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL COMMENT 'Nom du fichier sur le serveur (UUID)',
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `is_cabinet_document` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Document du cabinet pour le client',
  `version` int(11) NOT NULL DEFAULT 1,
  `parent_document_id` int(11) DEFAULT NULL COMMENT 'Pour versioning',
  `description` text DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL COMMENT 'Tags séparés par virgules',
  `download_count` int(11) NOT NULL DEFAULT 0,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `category` (`category`),
  KEY `is_cabinet_document` (`is_cabinet_document`),
  KEY `parent_document_id` (`parent_document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: tickets
-- Description: Tickets de messagerie client-cabinet
-- ============================================
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Collaborateur assigné',
  `subject` varchar(255) NOT NULL,
  `status` enum('ouvert','en_cours','resolu','ferme') NOT NULL DEFAULT 'ouvert',
  `priority` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `last_message_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `created_by` (`created_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ticket_messages
-- Description: Messages dans les tickets
-- ============================================
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachments` text DEFAULT NULL COMMENT 'JSON array des fichiers joints',
  `is_internal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Note interne collaborateurs',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: tasks
-- Description: Tâches et échéances
-- ============================================
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Collaborateur assigné',
  `created_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` varchar(100) DEFAULT NULL COMMENT 'TVA, URSSAF, déclaration, etc.',
  `status` enum('a_faire','en_cours','terminee','annulee') NOT NULL DEFAULT 'a_faire',
  `priority` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_days_before` int(11) DEFAULT 3,
  `custom_fields` text DEFAULT NULL COMMENT 'JSON pour champs personnalisés',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`),
  KEY `due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: workflows
-- Description: Modèles de workflows standardisés
-- ============================================
CREATE TABLE `workflows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `workflow_type` varchar(100) DEFAULT NULL COMMENT 'onboarding, cloture_annuelle, etc.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: workflow_steps
-- Description: Étapes d'un workflow
-- ============================================
CREATE TABLE `workflow_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL,
  `step_name` varchar(255) NOT NULL,
  `step_description` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workflow_id` (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: workflow_instances
-- Description: Instances de workflows pour les clients
-- ============================================
CREATE TABLE `workflow_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('en_cours','complete','annule') NOT NULL DEFAULT 'en_cours',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `current_step_id` int(11) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_id` (`workflow_id`),
  KEY `client_id` (`client_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: workflow_instance_steps
-- Description: Progression des étapes pour une instance
-- ============================================
CREATE TABLE `workflow_instance_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_instance_id` int(11) NOT NULL,
  `workflow_step_id` int(11) NOT NULL,
  `status` enum('pending','completed','skipped') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_instance_id` (`workflow_instance_id`),
  KEY `workflow_step_id` (`workflow_step_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: veille_articles
-- Description: Articles de veille réglementaire
-- ============================================
CREATE TABLE `veille_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL COMMENT 'Tags séparés par virgules',
  `sector` varchar(100) DEFAULT NULL COMMENT 'Secteur d\'activité concerné',
  `location` varchar(100) DEFAULT NULL COMMENT 'Guadeloupe, National, etc.',
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `attachments` text DEFAULT NULL COMMENT 'JSON des pièces jointes',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `published_at` (`published_at`),
  KEY `is_important` (`is_important`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: veille_subscriptions
-- Description: Abonnements aux alertes de veille
-- ============================================
CREATE TABLE `veille_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tags` varchar(500) DEFAULT NULL COMMENT 'Tags pour filtrer',
  `sectors` varchar(500) DEFAULT NULL,
  `locations` varchar(500) DEFAULT NULL,
  `email_frequency` enum('immediate','daily','weekly') NOT NULL DEFAULT 'immediate',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: veille_notifications
-- Description: Notifications de veille envoyées
-- ============================================
CREATE TABLE `veille_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: audit_logs
-- Description: Logs d'audit des actions sensibles
-- ============================================
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: email_logs
-- Description: Logs des emails envoyés
-- ============================================
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`),
  KEY `sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: settings
-- Description: Paramètres système
-- ============================================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: notifications
-- Description: Notifications utilisateurs
-- ============================================
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'document, ticket, task, veille',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Contraintes de clés étrangères
-- ============================================
ALTER TABLE `users`
  ADD CONSTRAINT `users_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

ALTER TABLE `clients`
  ADD CONSTRAINT `clients_collaborator_fk` FOREIGN KEY (`collaborator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `documents`
  ADD CONSTRAINT `documents_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_user_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_assigned_to_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_assigned_to_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `workflow_steps`
  ADD CONSTRAINT `workflow_steps_workflow_fk` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE;

ALTER TABLE `workflow_instances`
  ADD CONSTRAINT `workflow_instances_workflow_fk` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_instances_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_instances_assigned_to_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `workflow_instance_steps`
  ADD CONSTRAINT `workflow_instance_steps_instance_fk` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_instance_steps_step_fk` FOREIGN KEY (`workflow_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE CASCADE;

ALTER TABLE `veille_articles`
  ADD CONSTRAINT `veille_articles_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `veille_subscriptions`
  ADD CONSTRAINT `veille_subscriptions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `veille_notifications`
  ADD CONSTRAINT `veille_notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `veille_notifications_article_fk` FOREIGN KEY (`article_id`) REFERENCES `veille_articles` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;


-- ============================================
-- DONNEES DE DEMO
-- ============================================

-- Mon Espace Client Pro - Données de démonstration
-- Ces données seront insérées automatiquement lors de l'installation

-- ============================================
-- Utilisateurs (1 admin, 2 collaborateurs, 3 clients)
-- Mot de passe pour tous: demo123 (hashé)
-- ============================================

-- Admin
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin@cabinet.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marie', 'Dupont', '0590 12 34 56', 'admin', 1, NOW());

-- Collaborateurs
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `is_active`, `created_at`) VALUES
(2, 'julien.martin@cabinet.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Julien', 'Martin', '0590 23 45 67', 'collaborateur', 1, NOW()),
(3, 'sophie.bernard@cabinet.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophie', 'Bernard', '0590 34 56 78', 'collaborateur', 1, NOW());

-- ============================================
-- Clients (3 dossiers)
-- ============================================
INSERT INTO `clients` (`id`, `code`, `company_name`, `first_name`, `last_name`, `siret`, `address`, `postal_code`, `city`, `phone`, `email`, `activity_sector`, `collaborator_id`, `status`, `created_at`) VALUES
(1, 'CLI001', 'Restaurant Le Soleil', 'Pierre', 'Lafleur', '12345678901234', '15 Rue de la République', '97110', 'Pointe-à-Pitre', '0590 45 67 89', 'contact@lesoleil.gp', 'Restauration', 2, 'actif', NOW()),
(2, 'CLI002', 'Cabinet Médical Dr. Louis', 'Isabelle', 'Louis', '23456789012345', '8 Avenue de la Liberté', '97139', 'Les Abymes', '0590 56 78 90', 'dr.louis@sante.gp', 'Santé', 2, 'actif', NOW()),
(3, 'CLI003', 'SCI Immobilière Caraïbes', 'Jean', 'Moreau', '34567890123456', '22 Boulevard Maritime', '97118', 'Saint-François', '0590 67 89 01', 'contact@sci-caraibes.gp', 'Immobilier', 3, 'actif', NOW());

-- Utilisateurs clients (liés aux dossiers)
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `client_id`, `is_active`, `created_at`) VALUES
(4, 'pierre.lafleur@lesoleil.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pierre', 'Lafleur', '0590 45 67 89', 'client', 1, 1, NOW()),
(5, 'dr.louis@sante.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Isabelle', 'Louis', '0590 56 78 90', 'client', 2, 1, NOW()),
(6, 'j.moreau@sci-caraibes.gp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean', 'Moreau', '0590 67 89 01', 'client', 3, 1, NOW());

-- ============================================
-- Documents (10 documents classés)
-- ============================================
INSERT INTO `documents` (`client_id`, `uploaded_by`, `category`, `period_year`, `period_month`, `file_name`, `original_name`, `file_path`, `file_size`, `mime_type`, `extension`, `is_cabinet_document`, `description`, `created_at`) VALUES
-- Documents clients
(1, 4, 'factures', 2024, 10, 'demo-uuid-001.pdf', 'Factures_Octobre_2024.pdf', '/uploads/documents/1/demo-uuid-001.pdf', 245678, 'application/pdf', 'pdf', 0, 'Factures fournisseurs octobre 2024', '2024-11-01 10:30:00'),
(1, 4, 'releves', 2024, 10, 'demo-uuid-002.pdf', 'Releve_Bancaire_Oct_2024.pdf', '/uploads/documents/1/demo-uuid-002.pdf', 156789, 'application/pdf', 'pdf', 0, 'Relevé bancaire octobre 2024', '2024-11-02 14:15:00'),
(1, 2, 'bilans', 2023, NULL, 'demo-uuid-003.pdf', 'Bilan_2023.pdf', '/uploads/documents/1/demo-uuid-003.pdf', 589123, 'application/pdf', 'pdf', 1, 'Bilan comptable 2023', '2024-10-15 09:00:00'),
(2, 5, 'factures', 2024, 10, 'demo-uuid-004.pdf', 'Factures_Cabinet_Oct_2024.pdf', '/uploads/documents/2/demo-uuid-004.pdf', 198456, 'application/pdf', 'pdf', 0, 'Factures cabinet médical octobre', '2024-11-01 11:20:00'),
(2, 5, 'releves', 2024, 10, 'demo-uuid-005.pdf', 'Releve_Oct_2024.pdf', '/uploads/documents/2/demo-uuid-005.pdf', 134567, 'application/pdf', 'pdf', 0, 'Relevé bancaire octobre 2024', '2024-11-02 16:45:00'),
(2, 2, 'rapports', 2024, 9, 'demo-uuid-006.pdf', 'Rapport_Trimestriel_Q3_2024.pdf', '/uploads/documents/2/demo-uuid-006.pdf', 456789, 'application/pdf', 'pdf', 1, 'Rapport d\'activité T3 2024', '2024-10-10 10:30:00'),
(3, 6, 'factures', 2024, 9, 'demo-uuid-007.pdf', 'Charges_Sept_2024.pdf', '/uploads/documents/3/demo-uuid-007.pdf', 234567, 'application/pdf', 'pdf', 0, 'Charges copropriété septembre', '2024-10-05 08:15:00'),
(3, 6, 'contrats', 2024, NULL, 'demo-uuid-008.pdf', 'Bail_Commercial_A.pdf', '/uploads/documents/3/demo-uuid-008.pdf', 789012, 'application/pdf', 'pdf', 0, 'Bail commercial - Local A', '2024-09-20 14:00:00'),
(3, 3, 'bilans', 2023, NULL, 'demo-uuid-009.pdf', 'Bilan_SCI_2023.pdf', '/uploads/documents/3/demo-uuid-009.pdf', 623456, 'application/pdf', 'pdf', 1, 'Bilan SCI exercice 2023', '2024-10-12 11:30:00'),
(1, 2, 'liasses', 2023, NULL, 'demo-uuid-010.pdf', 'Liasse_Fiscale_2023.pdf', '/uploads/documents/1/demo-uuid-010.pdf', 845678, 'application/pdf', 'pdf', 1, 'Liasse fiscale 2023', '2024-10-18 15:45:00');

-- ============================================
-- Tâches (6 tâches dont 1 en retard)
-- ============================================
INSERT INTO `tasks` (`client_id`, `assigned_to`, `created_by`, `title`, `description`, `task_type`, `status`, `priority`, `due_date`, `created_at`) VALUES
-- Tâche en retard (échéance passée)
(1, 2, 1, 'Déclaration TVA Octobre 2024', 'Préparer et déposer la déclaration de TVA pour le Restaurant Le Soleil', 'TVA', 'en_cours', 'haute', '2024-11-05', '2024-10-25 09:00:00'),
-- Tâches à venir
(1, 2, 1, 'Clôture mensuelle Octobre', 'Effectuer la clôture comptable du mois d\'octobre', 'cloture', 'a_faire', 'normale', '2024-11-30', '2024-11-01 10:00:00'),
(2, 2, 1, 'Préparation bilan 2024', 'Commencer la préparation du bilan annuel 2024', 'bilan', 'a_faire', 'normale', '2024-12-15', '2024-11-01 11:00:00'),
(2, 2, 1, 'Déclaration URSSAF T4', 'Déclaration trimestrielle URSSAF pour le cabinet médical', 'URSSAF', 'a_faire', 'haute', '2024-12-05', '2024-11-10 14:00:00'),
(3, 3, 1, 'Révision comptable T3', 'Révision des comptes du 3ème trimestre', 'revision', 'terminee', 'normale', '2024-10-31', '2024-10-01 09:00:00'),
(3, 3, 1, 'Déclaration revenus fonciers', 'Préparer la déclaration des revenus fonciers 2024', 'declaration', 'en_cours', 'normale', '2024-12-20', '2024-11-05 10:30:00');

-- ============================================
-- Tickets (3 tickets avec messages et pièces jointes)
-- ============================================
INSERT INTO `tickets` (`id`, `client_id`, `created_by`, `assigned_to`, `subject`, `status`, `priority`, `last_message_at`, `created_at`) VALUES
(1, 1, 4, 2, 'Question sur la TVA restauration', 'resolu', 'normale', '2024-11-08 15:30:00', '2024-11-05 10:15:00'),
(2, 2, 5, 2, 'Besoin d\'un justificatif pour la banque', 'en_cours', 'haute', '2024-11-10 11:20:00', '2024-11-09 09:30:00'),
(3, 3, 6, 3, 'Documents manquants pour déclaration', 'ouvert', 'haute', '2024-11-10 14:45:00', '2024-11-10 14:45:00');

-- Messages des tickets
INSERT INTO `ticket_messages` (`ticket_id`, `user_id`, `message`, `created_at`) VALUES
-- Ticket 1
(1, 4, 'Bonjour,\n\nJ\'ai une question concernant le taux de TVA applicable pour les ventes à emporter. Pouvez-vous m\'éclairer ?\n\nMerci', '2024-11-05 10:15:00'),
(1, 2, 'Bonjour M. Lafleur,\n\nPour la vente à emporter, le taux de TVA est de 10% (restauration) si c\'est consommé immédiatement, et 5,5% (alimentation) pour les produits non préparés.\n\nJe vous prépare une note détaillée.', '2024-11-05 14:30:00'),
(1, 4, 'Parfait, merci pour ces précisions !', '2024-11-08 15:30:00'),
-- Ticket 2
(2, 5, 'Bonjour,\n\nMa banque me demande un justificatif de revenus pour un prêt professionnel. Pouvez-vous me fournir une attestation ?\n\nC\'est assez urgent.\n\nCordialement', '2024-11-09 09:30:00'),
(2, 2, 'Bonjour Dr. Louis,\n\nBien sûr, je prépare l\'attestation de revenus pour vous. Je vous la transmets d\'ici ce soir.\n\nBien cordialement', '2024-11-10 11:20:00'),
-- Ticket 3
(3, 6, 'Bonjour,\n\nIl me manque les justificatifs des travaux réalisés en septembre pour finaliser la déclaration. Pouvez-vous me dire où les trouver ?\n\nMerci', '2024-11-10 14:45:00');

-- ============================================
-- Workflow (1 workflow d'onboarding avec progression)
-- ============================================
INSERT INTO `workflows` (`id`, `name`, `description`, `workflow_type`, `is_active`, `created_at`) VALUES
(1, 'Onboarding Nouveau Client', 'Processus d\'intégration d\'un nouveau client au cabinet', 'onboarding', 1, NOW());

-- Étapes du workflow
INSERT INTO `workflow_steps` (`workflow_id`, `step_order`, `step_name`, `step_description`, `is_required`, `created_at`) VALUES
(1, 1, 'Collecte des informations juridiques', 'Récupérer KBIS, statuts, etc.', 1, NOW()),
(1, 2, 'Création du dossier comptable', 'Ouvrir le dossier dans le logiciel', 1, NOW()),
(1, 3, 'Configuration des accès portail', 'Créer les identifiants du client', 1, NOW()),
(1, 4, 'Première réunion de cadrage', 'Rendez-vous pour définir les besoins', 1, NOW()),
(1, 5, 'Formation à l\'utilisation du portail', 'Expliquer l\'utilisation de l\'espace client', 0, NOW());

-- Instance de workflow pour le client 1 (Restaurant Le Soleil) - 60% complété
INSERT INTO `workflow_instances` (`id`, `workflow_id`, `client_id`, `assigned_to`, `status`, `progress_percentage`, `started_at`) VALUES
(1, 1, 1, 2, 'en_cours', 60.00, '2024-10-15 09:00:00');

-- Progression des étapes
INSERT INTO `workflow_instance_steps` (`workflow_instance_id`, `workflow_step_id`, `status`, `completed_at`, `notes`) VALUES
(1, 1, 'completed', '2024-10-16 11:00:00', 'Documents reçus et validés'),
(1, 2, 'completed', '2024-10-18 14:30:00', 'Dossier créé - référence CLI001'),
(1, 3, 'completed', '2024-10-20 10:00:00', 'Identifiants envoyés par email'),
(1, 4, 'pending', NULL, NULL),
(1, 5, 'pending', NULL, NULL);

-- ============================================
-- Articles de veille (5 articles Guadeloupe)
-- ============================================
INSERT INTO `veille_articles` (`title`, `content`, `excerpt`, `source`, `tags`, `sector`, `location`, `is_important`, `published_at`, `created_by`, `created_at`) VALUES
('Nouvelles mesures fiscales pour les entreprises en Guadeloupe 2024',
'<p>La Direction Générale des Finances Publiques (DGFiP) annonce de nouvelles mesures fiscales spécifiques aux entreprises de Guadeloupe pour l\'année 2024...</p><p>Les principales nouveautés concernent:</p><ul><li>Exonération de cotisation foncière pour les nouvelles entreprises</li><li>Crédit d\'impôt renforcé pour la transition énergétique</li><li>Dispositifs d\'aide à l\'investissement productif</li></ul>',
'Nouvelles exonérations et crédits d\'impôt pour les entreprises de Guadeloupe en 2024',
'DGFiP Guadeloupe',
'fiscal,guadeloupe,exoneration,credit-impot',
'Tous secteurs',
'Guadeloupe',
1,
'2024-10-15 08:00:00',
1,
'2024-10-15 08:30:00'),

('Réforme des cotisations URSSAF pour les professions libérales',
'<p>À partir du 1er janvier 2025, les professions libérales en Guadeloupe verront leurs modalités de cotisations URSSAF évoluer...</p><p>Points clés:</p><ul><li>Nouveau calcul du taux de cotisation</li><li>Simplification des déclarations trimestrielles</li><li>Option de mensualisation disponible</li></ul>',
'Évolution des cotisations URSSAF pour les professions libérales à partir de 2025',
'URSSAF Guadeloupe',
'urssaf,professions-liberales,cotisations,social',
'Santé,Juridique',
'Guadeloupe',
1,
'2024-10-20 09:00:00',
1,
'2024-10-20 09:30:00'),

('TVA restauration: rappel des taux applicables en 2024',
'<p>Un rappel des différents taux de TVA applicables dans le secteur de la restauration en Guadeloupe...</p><p>Taux en vigueur:</p><ul><li>Vente à emporter immédiate: 10%</li><li>Consommation sur place: 10%</li><li>Produits alimentaires non préparés: 5,5%</li><li>Boissons alcoolisées: 20%</li></ul>',
'Clarification sur les taux de TVA dans la restauration',
'Service des Impôts des Entreprises',
'tva,restauration,taux',
'Restauration',
'National',
0,
'2024-10-25 10:00:00',
1,
'2024-10-25 10:30:00'),

('Obligation de facturation électronique reportée à 2026',
'<p>Le gouvernement annonce un nouveau report de l\'obligation de facturation électronique pour les PME...</p><p>Nouveau calendrier:</p><ul><li>Grandes entreprises: septembre 2026</li><li>ETI: septembre 2027</li><li>PME et TPE: septembre 2027</li></ul><p>Cette mesure laisse plus de temps aux entreprises guadeloupéennes pour s\'adapter.</p>',
'Report de l\'obligation de facturation électronique à 2026-2027',
'Ministère de l\'Économie',
'facturation,electronique,obligation,dematerialisation',
'Tous secteurs',
'National',
1,
'2024-11-01 08:00:00',
1,
'2024-11-01 08:30:00'),

('Aide à l\'investissement pour la transition énergétique en Guadeloupe',
'<p>La Région Guadeloupe lance un nouveau dispositif d\'aide financière pour accompagner les entreprises dans leur transition énergétique...</p><p>Sont concernés:</p><ul><li>Installation de panneaux solaires</li><li>Acquisition de véhicules électriques</li><li>Travaux d\'isolation thermique</li><li>Équipements économes en énergie</li></ul><p>Subventions jusqu\'à 40% du montant des investissements.</p>',
'Nouveau dispositif d\'aide régionale pour la transition énergétique des entreprises',
'Région Guadeloupe',
'energie,transition-energetique,subvention,environnement',
'Tous secteurs',
'Guadeloupe',
0,
'2024-11-05 09:00:00',
1,
'2024-11-05 09:30:00');

-- ============================================
-- Abonnements veille (quelques abonnements)
-- ============================================
INSERT INTO `veille_subscriptions` (`user_id`, `tags`, `sectors`, `locations`, `email_frequency`, `is_active`) VALUES
(4, 'tva,restauration,fiscal', 'Restauration', 'Guadeloupe,National', 'immediate', 1),
(5, 'urssaf,professions-liberales,cotisations', 'Santé', 'Guadeloupe,National', 'weekly', 1),
(6, 'fiscal,immobilier', 'Immobilier', 'Guadeloupe', 'daily', 1);

-- ============================================
-- Paramètres système
-- ============================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Mon Espace Client Pro', 'string', 'Nom du site'),
('maintenance_mode', '0', 'boolean', 'Mode maintenance'),
('registration_enabled', '0', 'boolean', 'Inscription publique activée'),
('default_language', 'fr', 'string', 'Langue par défaut'),
('items_per_page', '20', 'integer', 'Nombre d\'éléments par page'),
('session_timeout', '7200', 'integer', 'Durée de session en secondes'),
('max_upload_size', '10485760', 'integer', 'Taille max upload en octets'),
('date_format', 'd/m/Y', 'string', 'Format de date'),
('time_format', 'H:i', 'string', 'Format d\'heure');

-- ============================================
-- Logs d'audit (quelques exemples)
-- ============================================
INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'LOGIN', 'Connexion réussie', '192.168.1.100', '2024-11-10 08:30:00'),
(4, 'LOGIN', 'Connexion réussie', '192.168.1.105', '2024-11-10 09:15:00'),
(4, 'DOCUMENT_UPLOAD', 'Upload du document: Factures_Octobre_2024.pdf', '192.168.1.105', '2024-11-01 10:30:00'),
(2, 'DOCUMENT_UPLOAD', 'Upload du document cabinet: Bilan_2023.pdf', '192.168.1.101', '2024-10-15 09:00:00'),
(4, 'TICKET_CREATE', 'Création du ticket #1: Question sur la TVA restauration', '192.168.1.105', '2024-11-05 10:15:00'),
(2, 'TASK_UPDATE', 'Mise à jour statut tâche #5: terminee', '192.168.1.101', '2024-10-31 16:00:00');

-- Réinitialiser les auto-increments pour les nouvelles entrées
ALTER TABLE `users` AUTO_INCREMENT = 7;
ALTER TABLE `clients` AUTO_INCREMENT = 4;
ALTER TABLE `documents` AUTO_INCREMENT = 11;
ALTER TABLE `tickets` AUTO_INCREMENT = 4;
ALTER TABLE `ticket_messages` AUTO_INCREMENT = 7;
ALTER TABLE `tasks` AUTO_INCREMENT = 7;
ALTER TABLE `workflows` AUTO_INCREMENT = 2;
ALTER TABLE `workflow_steps` AUTO_INCREMENT = 6;
ALTER TABLE `workflow_instances` AUTO_INCREMENT = 2;
ALTER TABLE `veille_articles` AUTO_INCREMENT = 6;
