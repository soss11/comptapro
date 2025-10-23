<?php
require_once __DIR__ . '/../Controller.php';

class PageController extends Controller {

    public function mentionsLegales() {
        $content = '<h1>Mentions Légales</h1>
        <p><strong>Éditeur du site</strong></p>
        <p>
            Nom du cabinet : [À PERSONNALISER]<br>
            Adresse : [À PERSONNALISER]<br>
            Téléphone : [À PERSONNALISER]<br>
            Email : [À PERSONNALISER]<br>
            SIRET : [À PERSONNALISER]
        </p>

        <p><strong>Hébergement</strong></p>
        <p>
            Hébergeur : [À PERSONNALISER]<br>
            Adresse : [À PERSONNALISER]
        </p>

        <p><strong>Protection des données personnelles</strong></p>
        <p>
            Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification et de suppression
            de vos données personnelles. Pour exercer ce droit, contactez-nous à l\'adresse email ci-dessus.
        </p>';

        require __DIR__ . '/../views/pages/legal.php';
    }

    public function cgu() {
        $content = '<h1>Conditions Générales d\'Utilisation (CGU)</h1>
        <p><strong>1. Objet</strong></p>
        <p>
            Les présentes conditions générales ont pour objet de définir les modalités d\'utilisation
            du portail "Mon Espace Client Pro".
        </p>

        <p><strong>2. Accès au service</strong></p>
        <p>
            L\'accès au portail est réservé aux clients du cabinet et aux collaborateurs autorisés.
            Chaque utilisateur dispose d\'identifiants personnels et confidentiels.
        </p>

        <p><strong>3. Responsabilités</strong></p>
        <p>
            L\'utilisateur s\'engage à utiliser le portail de manière responsable et à ne pas porter atteinte
            à la sécurité du système.
        </p>

        <p><strong>4. Protection des données</strong></p>
        <p>
            Les données personnelles sont traitées conformément au RGPD. Elles ne sont pas communiquées à des tiers.
        </p>

        <p><strong>5. Propriété intellectuelle</strong></p>
        <p>
            L\'ensemble des contenus du portail est protégé par le droit d\'auteur.
        </p>';

        require __DIR__ . '/../views/pages/legal.php';
    }
}
