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
