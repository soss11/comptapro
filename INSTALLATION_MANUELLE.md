# INSTALLATION MANUELLE - Mon Espace Client Pro

## Étape 1 : Créer la base de données MySQL

Connectez-vous à phpMyAdmin et exécutez :

```sql
CREATE DATABASE mon_espace_client_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Étape 2 : Importer le schéma

Dans phpMyAdmin, sélectionnez la base de données et importez :
- `database/schema.sql`

## Étape 3 : (Optionnel) Importer les données de démo

Importez aussi :
- `database/demo_data.sql`

## Étape 4 : Créer le fichier .env

Copiez `.env.example` vers `.env` et modifiez avec vos paramètres :

```env
DB_HOST=localhost
DB_NAME=mon_espace_client_pro
DB_USER=votre_utilisateur_mysql
DB_PASS=votre_mot_de_passe_mysql
DB_CHARSET=utf8mb4

APP_NAME="Mon Espace Client Pro"
APP_URL=https://votre-domaine.fr
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Guadeloupe

SESSION_LIFETIME=7200
CSRF_TOKEN_NAME=csrf_token
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900

UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip
UPLOAD_PATH=/uploads/documents

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Mon Espace Client Pro"

CRON_TOKEN=changez_ce_token_12345
CRON_URL=/cron.php

ENABLE_2FA=false
ENABLE_IP_WHITELIST=false
ADMIN_IP_WHITELIST=
ENABLE_PWA=false
ENABLE_RGPD=true
FILE_RETENTION_DAYS=2555
ENABLE_WATERMARK=false
ENABLE_API=false
API_RATE_LIMIT=100
```

## Étape 5 : Créer le fichier de verrouillage

Créez le fichier `config/installed.lock` avec le contenu :
```
2024-11-23 10:00:00
```

## Étape 6 : Permissions des dossiers

```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 config/
chmod 644 .env
```

## Étape 7 : Créer un compte administrateur

Si vous N'AVEZ PAS importé les données de démo, créez un admin en SQL :

```sql
-- Mot de passe: admin123 (à changer après première connexion)
INSERT INTO users (email, password, first_name, last_name, role, is_active, created_at)
VALUES ('admin@votrecabinet.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Cabinet', 'admin', 1, NOW());
```

## Étape 8 : Tester la connexion

Accédez à : https://votre-domaine.fr/

- Si données de démo : `admin@cabinet.gp` / `demo123`
- Sinon : `admin@votrecabinet.fr` / `admin123`

## En cas d'erreur 403

1. Vérifiez le fichier .htaccess
2. Vérifiez les permissions
3. Activez temporairement le debug dans .env : `APP_DEBUG=true`
4. Consultez les logs : `logs/error.log`
