# 📌 Mon Espace Client Pro – Portail Sécurisé Cabinet

**Version 1.0** - Application web complète pour cabinets comptables et juridiques

---

## 🎯 Description

**Mon Espace Client Pro** est une application web professionnelle 100% en français, spécialement conçue pour les cabinets comptables et juridiques (notamment en Guadeloupe). Elle offre un portail client sécurisé avec gestion de documents, messagerie, pilotage des tâches, workflows et veille réglementaire intelligente.

### ✅ Fonctionnalités principales

#### 1️⃣ Portail Client (Espace client sécurisé)
- Authentification sécurisée avec protection anti-brute force
- Accès autonome aux dossiers
- **Dépôt sécurisé de documents** :
  - Upload par période (mois/année) et catégories (factures, relevés, etc.)
  - Renommage automatique avec UUID pour la sécurité
  - Aperçu PDF/images
  - Historique des versions
  - Téléchargement de documents cabinet (bilans, liasses, rapports)
- **Messagerie privée (tickets)** :
  - Système de tickets avec statuts
  - Notifications email automatiques
  - Pièces jointes
  - Historique des conversations

#### 2️⃣ Pilotage Cabinet (Vue Collaborateur/Admin)
- **Gestion des tâches et délais** :
  - Assignations par collaborateur
  - Priorités et statuts
  - Rappels automatiques (TVA, URSSAF, échéances, etc.)
- **Tableau de bord global** :
  - KPIs de productivité
  - Charge de travail par collaborateur
  - Tâches en retard
- **Workflows standardisés** :
  - Modèles de processus (ex: onboarding client, clôture annuelle)
  - Instanciation par dossier
  - Suivi de progression (%)

#### 3️⃣ Veille Réglementaire intelligente
- **Base d'articles** avec tags, source et pièces jointes
- **Alertes personnalisées** :
  - Filtres par secteurs d'activité
  - Thèmes spécifiques
  - Localisation (Guadeloupe, National)
- **Communication proactive** :
  - Newsletter segmentée
  - Abonnements personnalisables
  - Fréquence d'envoi configurable (immédiate, quotidienne, hebdomadaire)

---

## 🚀 Installation

### Prérequis système

- **PHP** : >= 8.0
- **MySQL** : >= 5.7
- **Extensions PHP requises** :
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - json
  - fileinfo
  - gd
- **Serveur web** : Apache avec mod_rewrite (ou Nginx)
- **Hébergement** : Compatible hébergement mutualisé cPanel (sans SSH)

### Installation via interface web (type WordPress)

1. **Téléverser les fichiers** :
   - Extraire le fichier ZIP
   - Uploader tous les fichiers à la racine de votre hébergement (ou sous-dossier)

2. **Configuration des permissions** :
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   chmod 755 config/
   ```

3. **Accéder à l'installateur** :
   - Ouvrir votre navigateur : `https://votre-domaine.fr/install.php`
   - Suivre les étapes guidées :
     - ✓ Vérification des prérequis
     - ✓ Configuration base de données MySQL
     - ✓ Paramètres de l'application
     - ✓ Configuration SMTP
     - ✓ Création compte administrateur
     - ✓ Installation des données de démo (optionnel)

4. **Finalisation** :
   - Une fois l'installation terminée, **supprimer ou renommer le fichier `install.php`** pour des raisons de sécurité
   - Se connecter avec le compte administrateur créé

### Configuration manuelle (si nécessaire)

Si vous préférez configurer manuellement :

1. Copier `.env.example` vers `.env`
2. Éditer `.env` avec vos paramètres :
   ```
   DB_HOST=localhost
   DB_NAME=mon_espace_client_pro
   DB_USER=votre_utilisateur
   DB_PASS=votre_mot_de_passe

   APP_URL=https://votre-domaine.fr

   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=noreply@votre-domaine.fr
   MAIL_PASSWORD=votre_mdp_smtp
   ```

3. Importer le schéma SQL :
   ```bash
   mysql -u votre_user -p votre_database < database/schema.sql
   ```

4. (Optionnel) Importer les données de démo :
   ```bash
   mysql -u votre_user -p votre_database < database/demo_data.sql
   ```

---

## ⚙️ Configuration

### Configuration SMTP (Email)

Pour envoyer les emails (notifications, alertes, etc.), configurez vos paramètres SMTP dans `.env` ou via l'installateur :

```env
MAIL_HOST=smtp.gmail.com          # Votre serveur SMTP
MAIL_PORT=587                      # Port (587 pour TLS, 465 pour SSL)
MAIL_USERNAME=votre@email.com     # Utilisateur SMTP
MAIL_PASSWORD=votre_mot_de_passe   # Mot de passe SMTP
MAIL_ENCRYPTION=tls                # tls ou ssl
MAIL_FROM_ADDRESS=noreply@votre-domaine.fr
MAIL_FROM_NAME="Mon Espace Client Pro"
```

**Fournisseurs SMTP recommandés** :
- **Gmail** : smtp.gmail.com (port 587, TLS)
- **Sendinblue** : smtp-relay.sendinblue.com (port 587, TLS)
- **Mailgun**, **SendGrid**, etc.

**Note** : Pour Gmail, activez "Accès moins sécurisé" ou créez un "Mot de passe d'application".

### Configuration CRON (Tâches planifiées)

Pour activer les rappels automatiques de tâches et les alertes de veille, configurez une tâche CRON :

#### Sur cPanel :

1. Accéder à **Cron Jobs** dans cPanel
2. Ajouter une nouvelle tâche CRON :

**Fréquence** : Toutes les 15 minutes (recommandé)
```
*/15 * * * *
```

**Commande** :
```bash
curl -s "https://votre-domaine.fr/cron.php?token=VOTRE_TOKEN_CRON" > /dev/null 2>&1
```

**Récupérer le token CRON** :
- Le token est généré automatiquement lors de l'installation
- Il est stocké dans le fichier `.env` sous `CRON_TOKEN`
- Exemple : `CRON_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

#### Alternative avec wget :
```bash
wget -O /dev/null "https://votre-domaine.fr/cron.php?token=VOTRE_TOKEN_CRON" > /dev/null 2>&1
```

**Que fait le CRON ?**
- ✓ Envoie les rappels de tâches à échéance proche (3 jours avant par défaut)
- ✓ Envoie les alertes de veille réglementaire (digest quotidien)
- ✓ Nettoie les fichiers temporaires (> 24h)
- ✓ Archive les anciens logs d'audit (> 1 an)
- ✓ Supprime les documents expirés selon la politique de rétention

**Logs CRON** : Consulter le fichier `logs/cron.log` pour vérifier l'exécution.

---

## 👥 Comptes de démonstration

Si vous avez installé les données de démo, vous pouvez tester l'application avec ces comptes :

### Administrateur
- **Email** : `admin@cabinet.gp`
- **Mot de passe** : `demo123`

### Collaborateurs
- **Email** : `julien.martin@cabinet.gp` / `demo123`
- **Email** : `sophie.bernard@cabinet.gp` / `demo123`

### Clients
- **Email** : `pierre.lafleur@lesoleil.gp` / `demo123` (Restaurant Le Soleil)
- **Email** : `dr.louis@sante.gp` / `demo123` (Cabinet Médical)
- **Email** : `j.moreau@sci-caraibes.gp` / `demo123` (SCI Immobilière)

### Données de démo incluses :
- ✓ 3 clients (dossiers)
- ✓ 2 collaborateurs
- ✓ 10 documents classés
- ✓ 6 tâches (dont 1 en retard)
- ✓ 3 tickets avec messages
- ✓ 5 articles de veille (thématiques Guadeloupe)
- ✓ 1 workflow d'onboarding avec 60% de progression

---

## 🔒 Sécurité

L'application intègre de nombreuses mesures de sécurité :

### Mesures implémentées :
- ✓ **Authentification sécurisée** avec password_hash (bcrypt)
- ✓ **Protection CSRF** sur tous les formulaires
- ✓ **Protection XSS** (échappement systématique des données)
- ✓ **Protection anti-brute force** :
  - Verrouillage après 5 tentatives échouées
  - Durée de verrouillage : 15 minutes
- ✓ **Validation des données** côté serveur
- ✓ **Upload sécurisé** :
  - Renommage UUID
  - Vérification des types MIME
  - Limite de taille configurable
  - Interdiction d'exécution PHP dans /uploads
- ✓ **Logs d'audit** pour toutes les actions sensibles
- ✓ **Sessions sécurisées** (httponly, samesite)
- ✓ **Headers de sécurité** (.htaccess)

### Options de sécurité avancées (facultatives) :

#### 1. Authentification à deux facteurs (2FA)
```env
ENABLE_2FA=true
```
Envoie un code OTP par email à chaque connexion.

#### 2. Whitelist IP pour admin
```env
ENABLE_IP_WHITELIST=true
ADMIN_IP_WHITELIST=192.168.1.100,203.0.113.45
```
Restreint l'accès admin à des IPs spécifiques.

#### 3. Conformité RGPD
```env
ENABLE_RGPD=true
FILE_RETENTION_DAYS=2555  # 7 ans (durée légale comptable)
```
Suppression automatique des documents après X jours.

---

## 📂 Structure du projet

```
mon-espace-client-pro/
├── app/
│   ├── controllers/         # Contrôleurs (Auth, Admin, Client, etc.)
│   ├── helpers/             # Classes utilitaires (Database, Security, Email, etc.)
│   ├── models/              # Modèles de données (optionnel, logique dans contrôleurs)
│   ├── views/               # Vues PHP
│   │   ├── layouts/         # Layouts (auth, app)
│   │   ├── auth/            # Pages d'authentification
│   │   ├── admin/           # Pages admin
│   │   ├── client/          # Pages client
│   │   ├── collaborateur/   # Pages collaborateur
│   │   ├── shared/          # Composants partagés
│   │   └── errors/          # Pages d'erreur (404, 403)
│   ├── Controller.php       # Contrôleur de base
│   └── Router.php           # Système de routage
├── assets/
│   ├── css/                 # Styles CSS
│   ├── js/                  # Scripts JavaScript
│   └── images/              # Images et icônes
├── config/
│   ├── config.php           # Configuration principale
│   └── installed.lock       # Fichier de verrouillage post-installation
├── database/
│   ├── schema.sql           # Schéma de base de données
│   └── demo_data.sql        # Données de démonstration
├── logs/
│   ├── error.log            # Logs d'erreurs PHP
│   └── cron.log             # Logs des tâches CRON
├── tests/
│   └── BasicTests.php       # Tests unitaires basiques
├── uploads/
│   ├── documents/           # Documents uploadés (par client)
│   └── temp/                # Fichiers temporaires
├── .env.example             # Exemple de configuration environnement
├── .htaccess                # Configuration Apache (réécriture, sécurité)
├── index.php                # Point d'entrée principal
├── install.php              # Installateur web
├── cron.php                 # Script CRON
└── README_FR.md             # Ce fichier
```

---

## 🧪 Tests

### Exécuter les tests unitaires basiques :

```bash
php tests/BasicTests.php
```

Les tests couvrent :
- ✓ Sécurité (hashing, validation email, UUID, CSRF, XSS)
- ✓ Validation des données (champs requis, email, longueur, correspondance)
- ✓ Gestion de fichiers (sanitization, formatage, icônes, types)

Tous les tests doivent passer avec succès (100% ✓).

---

## 🛠️ Personnalisation

### Modifier le nom de l'application

Dans `.env` :
```env
APP_NAME="Nom de votre cabinet"
```

### Ajouter des catégories de documents personnalisées

Dans la base de données, les catégories sont stockées en tant que valeurs libres dans la colonne `documents.category`. Vous pouvez les définir dans les formulaires d'upload.

**Catégories suggérées** :
- Factures
- Relevés bancaires
- Bulletins de paie
- Déclarations fiscales
- Contrats
- Bilans
- Liasses fiscales
- Rapports

### Champs personnalisés

Les tables `clients` et `tasks` disposent d'un champ `custom_fields` (JSON) pour stocker des données personnalisées.

Exemple :
```json
{
  "champ_1": "valeur",
  "champ_2": 123,
  "champ_3": true
}
```

---

## 🌐 Compatibilité hébergement

### Hébergement mutualisé (cPanel)

✅ **Totalement compatible** - Aucune ligne de commande requise

**Hébergeurs testés** :
- OVH
- Ionos
- LWS
- O2Switch
- Infomaniak

### Hébergement VPS/Dédié

✅ Compatible avec Apache ou Nginx

**Configuration Nginx** (exemple) :

```nginx
server {
    listen 80;
    server_name votre-domaine.fr;
    root /var/www/mon-espace-client-pro;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location /uploads {
        location ~ \.php$ {
            deny all;
        }
    }
}
```

---

## 📖 Scénarios de tests fonctionnels

### 1. Client upload → Collaborateur voit immédiatement
1. Se connecter en tant que client (`pierre.lafleur@lesoleil.gp` / `demo123`)
2. Aller dans **Mes documents**
3. Uploader un document (ex: facture PDF)
4. Se déconnecter et se connecter en tant que collaborateur (`julien.martin@cabinet.gp` / `demo123`)
5. Aller dans **Mes clients** → Cliquer sur "Restaurant Le Soleil"
6. ✓ Le document uploadé apparaît dans la liste

### 2. Collaborateur publie un bilan → Client télécharge
1. Se connecter en tant que collaborateur
2. Aller dans **Mes clients** → Cliquer sur un client
3. Uploader un document cabinet (cocher "Document cabinet", catégorie "Bilan")
4. Se déconnecter et se connecter en tant que client
5. Aller dans **Mes documents**
6. ✓ Le document cabinet est visible et téléchargeable

### 3. Ticket créé par client → Email au collaborateur
1. Se connecter en tant que client
2. Aller dans **Messagerie** → **Nouveau message**
3. Créer un ticket avec sujet et message
4. ✓ Un email est envoyé au collaborateur assigné (vérifier logs email ou boîte mail)
5. Le collaborateur peut répondre depuis son interface

### 4. Tâche avec échéance → Rappel CRON (mail logué)
1. Se connecter en tant que collaborateur ou admin
2. Créer une tâche avec échéance dans 2-3 jours
3. Attendre l'exécution du CRON (ou forcer : `curl "https://votre-domaine.fr/cron.php?token=TOKEN"`)
4. ✓ Un email de rappel est envoyé
5. ✓ Vérifier le log : `logs/cron.log`

### 5. Article veille → Abonné reçoit une notification
1. Se connecter en tant qu'admin ou collaborateur
2. Aller dans **Veille** → **Nouvel article**
3. Publier un article avec des tags (ex: "TVA", "Guadeloupe")
4. Créer un abonnement (en tant que client) avec les mêmes tags
5. ✓ Le client reçoit immédiatement un email (si fréquence = immédiate)

### 6. Workflow → % progression mis à jour
1. Se connecter en tant que collaborateur
2. Aller dans **Workflows**
3. Voir le workflow "Onboarding Nouveau Client" pour "Restaurant Le Soleil"
4. ✓ La progression est à 60% (3 étapes sur 5 complétées)
5. Marquer une étape comme complétée
6. ✓ La progression est recalculée automatiquement

---

## 🔧 Dépannage

### Problème : Page blanche après installation

**Solution** :
1. Vérifier les logs d'erreur : `logs/error.log`
2. Activer le mode debug dans `.env` :
   ```env
   APP_DEBUG=true
   ```
3. Vérifier les permissions :
   ```bash
   chmod 755 uploads/ logs/ config/
   ```

### Problème : Erreur de connexion à la base de données

**Solution** :
1. Vérifier les identifiants dans `.env`
2. Tester la connexion MySQL depuis phpMyAdmin ou un client
3. Vérifier que la base de données existe
4. Vérifier que l'utilisateur a les permissions nécessaires (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP)

### Problème : Les emails ne sont pas envoyés

**Solution** :
1. Vérifier la configuration SMTP dans `.env`
2. Tester avec un service SMTP fiable (Gmail, Sendinblue)
3. Vérifier les logs : `SELECT * FROM email_logs ORDER BY sent_at DESC`
4. Pour Gmail, activer "Accès moins sécurisé" ou créer un "Mot de passe d'application"

### Problème : Le CRON ne s'exécute pas

**Solution** :
1. Vérifier que le token CRON est correct
2. Tester manuellement : `curl "https://votre-domaine.fr/cron.php?token=VOTRE_TOKEN"`
3. Vérifier les logs : `logs/cron.log`
4. Sur cPanel, vérifier la configuration de la tâche CRON
5. Vérifier que curl ou wget est disponible sur le serveur

### Problème : Upload de fichiers échoue

**Solution** :
1. Vérifier les permissions : `chmod 755 uploads/`
2. Vérifier la taille max dans `.env` : `UPLOAD_MAX_SIZE=10485760` (10MB)
3. Vérifier les limites PHP :
   ```ini
   upload_max_filesize = 10M
   post_max_size = 12M
   ```
4. Vérifier les types autorisés : `UPLOAD_ALLOWED_TYPES=pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip`

---

## 📄 Mentions légales & CGU

Les pages **Mentions légales** et **CGU** sont accessibles depuis le footer de l'application.

**Important** : Personnalisez ces pages avec vos informations légales (nom du cabinet, SIRET, contact, etc.).

Fichiers à modifier :
- `app/controllers/PageController.php` (si créé)
- Ou créer des pages statiques dans `app/views/pages/`

---

## 🤝 Support & Contribution

### Support

Pour toute question ou problème :
1. Consulter cette documentation
2. Vérifier les logs d'erreur (`logs/error.log`, `logs/cron.log`)
3. Activer le mode debug temporairement (`APP_DEBUG=true`)

### Contribution

Ce projet est open-source. Les contributions sont les bienvenues !

---

## 📝 Licence

© 2024 Mon Espace Client Pro - Tous droits réservés

Ce logiciel est fourni "tel quel", sans garantie d'aucune sorte.

---

## 🎉 Félicitations !

Votre portail **Mon Espace Client Pro** est maintenant installé et opérationnel !

**Prochaines étapes recommandées** :
1. ✓ Supprimer ou renommer `install.php`
2. ✓ Configurer le SMTP pour les emails
3. ✓ Configurer le CRON pour les tâches automatiques
4. ✓ Personnaliser les paramètres dans l'interface admin
5. ✓ Créer vos premiers utilisateurs et clients
6. ✓ Tester les fonctionnalités avec les comptes de démo
7. ✓ Configurer un certificat SSL (HTTPS) via Let's Encrypt

**Bon usage ! 🚀**
