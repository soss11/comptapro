<?php $viewPath = 'veille/index'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h1><?= $title ?></h1>
    <div style="display: flex; gap: 10px;">
        <a href="/veille/subscriptions" class="btn">Mes alertes</a>
        <?php if (in_array($_SESSION['user_role'], ['admin', 'collaborateur'])): ?>
            <a href="/collaborateur/veille/create" class="btn btn-primary">Nouvel article</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($articles)): ?>
    <div style="display: grid; gap: 20px;">
        <?php foreach ($articles as $article): ?>
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                <div>
                    <h3 style="margin-bottom: 10px;">
                        <a href="/veille/view/<?= $article['id'] ?>" style="color: #2c3e50;">
                            <?php if ($article['is_important']): ?>⭐<?php endif; ?>
                            <?= Security::escape($article['title']) ?>
                        </a>
                    </h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                        <?php if ($article['location']): ?>
                            <span class="badge" style="background: #e74c3c;"><?= Security::escape($article['location']) ?></span>
                        <?php endif; ?>
                        <?php if ($article['sector']): ?>
                            <span class="badge"><?= Security::escape($article['sector']) ?></span>
                        <?php endif; ?>
                        <?php if ($article['tags']): ?>
                            <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                <span class="badge" style="background: #95a5a6;"><?= Security::escape(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <small style="color: #777; white-space: nowrap;">
                    <?= date('d/m/Y', strtotime($article['published_at'] ?? $article['created_at'])) ?>
                </small>
            </div>

            <p style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                <?= Security::escape($article['excerpt'] ?? substr(strip_tags($article['content']), 0, 200) . '...') ?>
            </p>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <small style="color: #777;">
                    <?php if ($article['source']): ?>
                        Source: <?= Security::escape($article['source']) ?> -
                    <?php endif; ?>
                    Par <?= Security::escape($article['first_name'] . ' ' . $article['last_name']) ?>
                </small>
                <a href="/veille/view/<?= $article['id'] ?>" class="btn btn-sm">Lire la suite →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="section">
        <p class="empty-state">Aucun article de veille pour le moment.</p>
    </div>
<?php endif; ?>
