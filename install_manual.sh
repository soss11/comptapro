#!/bin/bash
# Script d'installation manuelle rapide
# Usage: bash install_manual.sh

echo "======================================"
echo "Installation manuelle - Mon Espace Client Pro"
echo "======================================"
echo ""

# Créer le fichier .env
if [ ! -f .env ]; then
    echo "Création du fichier .env..."
    cat > .env << 'EOF'
DB_HOST=localhost
DB_NAME=mon_espace_client_pro
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

APP_NAME="Mon Espace Client Pro"
APP_URL=http://localhost
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

CRON_TOKEN=$(openssl rand -hex 32)
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
EOF
    echo "✓ Fichier .env créé"
else
    echo "✓ Fichier .env existe déjà"
fi

# Créer les dossiers nécessaires
echo "Création des dossiers..."
mkdir -p uploads/documents uploads/temp logs config
chmod 755 uploads logs config
echo "✓ Dossiers créés"

# Créer le fichier de verrouillage
echo "$(date '+%Y-%m-%d %H:%M:%S')" > config/installed.lock
echo "✓ Fichier de verrouillage créé"

# Créer .htaccess dans uploads pour sécurité
cat > uploads/.htaccess << 'EOF'
Options -Indexes
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps)$">
    Deny from all
</FilesMatch>
EOF
echo "✓ Sécurité uploads configurée"

echo ""
echo "======================================"
echo "Configuration terminée !"
echo "======================================"
echo ""
echo "PROCHAINES ÉTAPES :"
echo ""
echo "1. Modifier le fichier .env avec vos paramètres MySQL"
echo "2. Créer la base de données MySQL :"
echo "   CREATE DATABASE mon_espace_client_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo ""
echo "3. Importer le schéma :"
echo "   mysql -u root -p mon_espace_client_pro < database/schema.sql"
echo ""
echo "4. (Optionnel) Importer les données de démo :"
echo "   mysql -u root -p mon_espace_client_pro < database/demo_data.sql"
echo ""
echo "5. Accéder à votre site et vous connecter"
echo "   - Avec démo : admin@cabinet.gp / demo123"
echo "   - Sans démo : créer un admin en SQL (voir INSTALLATION_MANUELLE.md)"
echo ""
echo "======================================"
