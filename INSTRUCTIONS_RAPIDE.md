# 🚀 INSTALLATION RAPIDE - Mon Espace Client Pro

## ✅ Fichiers de configuration déjà créés !

Le script d'installation a créé :
- ✅ Fichier `.env` avec configuration
- ✅ Dossiers `uploads/`, `logs/`, `config/`
- ✅ Fichier de verrouillage `config/installed.lock`

---

## 📝 ÉTAPES À SUIVRE

### Étape 1 : Modifier vos paramètres MySQL

Éditez le fichier `.env` et modifiez ces lignes :

```env
DB_HOST=localhost          # Votre hôte MySQL
DB_NAME=mon_espace_client_pro
DB_USER=votre_user         # Votre utilisateur MySQL
DB_PASS=votre_password     # Votre mot de passe MySQL
```

### Étape 2 : Importer la base de données

**Option A : Via phpMyAdmin (RECOMMANDÉ)**

1. Ouvrez phpMyAdmin
2. Cliquez sur "Importer" dans le menu du haut
3. Sélectionnez le fichier `install_complete.sql`
4. Cliquez sur "Exécuter"

✅ La base de données sera créée avec toutes les données de démo !

**Option B : Via ligne de commande (si disponible)**

```bash
mysql -u root -p < install_complete.sql
```

### Étape 3 : Accéder à l'application

Ouvrez votre navigateur : `http://localhost/` (ou votre domaine)

**Comptes de test :**
- **Admin** : `admin@cabinet.gp` / `demo123`
- **Collaborateur** : `julien.martin@cabinet.gp` / `demo123`
- **Client** : `pierre.lafleur@lesoleil.gp` / `demo123`

---

## 🔧 EN CAS DE PROBLÈME

### Erreur 403 - Accès refusé

1. **Vérifiez les permissions** :
   ```bash
   chmod 755 uploads/ logs/ config/
   chmod 644 .env
   ```

2. **Vérifiez le fichier .htaccess** :
   - Assurez-vous que mod_rewrite est activé
   - Commentez temporairement les règles de sécurité si nécessaire

3. **Activez le debug** (déjà fait) :
   - Le fichier `.env` a `APP_DEBUG=true`
   - Consultez `logs/error.log` pour voir l'erreur exacte

### Erreur de connexion à la base de données

1. Vérifiez que la base de données `mon_espace_client_pro` existe
2. Vérifiez vos identifiants dans `.env`
3. Testez la connexion MySQL dans phpMyAdmin

### Page blanche

1. Vérifiez `logs/error.log`
2. Vérifiez que PHP >= 8.0
3. Vérifiez les extensions PHP requises :
   - pdo, pdo_mysql, mbstring, openssl, json, fileinfo, gd

---

## 📊 Données de démo incluses

✅ **6 utilisateurs** (1 admin, 2 collaborateurs, 3 clients)
✅ **3 dossiers clients** (Restaurant, Cabinet médical, SCI)
✅ **10 documents** classés
✅ **6 tâches** dont 1 en retard
✅ **3 tickets** avec messages
✅ **5 articles de veille** (Guadeloupe)
✅ **1 workflow** d'onboarding (60% complété)

---

## 🎯 Prochaines étapes après installation

1. ✅ Se connecter avec un compte de test
2. ✅ Tester l'upload de documents
3. ✅ Créer un ticket
4. ✅ Explorer les différentes interfaces (admin, collaborateur, client)
5. ✅ Modifier les paramètres dans `.env` selon vos besoins
6. ✅ Configurer SMTP pour les emails (optionnel)
7. ✅ Configurer CRON pour les automatisations (optionnel)

---

## 📧 Configuration SMTP (Optionnel)

Pour activer l'envoi d'emails, modifiez dans `.env` :

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votredomaine.fr
```

---

## ⏰ Configuration CRON (Optionnel)

Pour les rappels automatiques, ajoutez dans cPanel > Cron Jobs :

```
*/15 * * * * curl -s "http://localhost/cron.php?token=fbdc4784f5bd48cf0e27d9073c32aad6320b4d74bd5ede58ec0bd9dde2aa122a" > /dev/null 2>&1
```

---

## ✅ C'EST PRÊT !

Votre application **Mon Espace Client Pro** est configurée et prête à être utilisée ! 🎉

**Besoin d'aide ?** Consultez `README_FR.md` pour la documentation complète.
