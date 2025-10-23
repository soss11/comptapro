<?php $viewPath = 'auth/login'; ?>

<div class="auth-box">
    <?php if ($flash = $this->getFlashMessage()): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= Security::escape($flash['message']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" class="auth-form">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Security::generateCSRFToken() ?>">

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
    </form>

    <p class="auth-hint">
        Pour tester l'application avec les données de démo :<br>
        <code>admin@cabinet.gp</code> / <code>demo123</code>
    </p>
</div>
