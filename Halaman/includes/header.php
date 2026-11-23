<?php
$headerProfilePhotoStatement = $pdo->prepare(
    'SELECT profile_photo_mime FROM users WHERE id = :id'
);
$headerProfilePhotoStatement->execute(['id' => (int) currentUser()['id']]);
$headerProfilePhotoMime = $headerProfilePhotoStatement->fetchColumn();
?>
<header class="navbar">
    <div class="nav-brand">
        <div class="logo-box" aria-hidden="true"><img src="../assets/img/servitory-logo.png?v=2" alt=""></div>
        <span class="brand-title">Servitory<span class="brand-dot"></span></span>
    </div>
    <div class="nav-user">
        <a class="user-profile-link" href="<?= h(tabUrl('../Profil/index.php')) ?>"
            aria-label="Buka profil <?= h($headerUserName) ?>">
            <span class="user-avatar">
                <?php if (is_string($headerProfilePhotoMime) && $headerProfilePhotoMime !== ''): ?>
                    <img src="../ProfilFoto.php?tab=<?= h(rawurlencode($tabId)) ?>" alt="">
                <?php else: ?>
                    <span aria-hidden="true"><?= h(mb_strtoupper(mb_substr($headerUserName, 0, 1))) ?></span>
                <?php endif; ?>
            </span>
        </a>
        <div class="user-info">
            <span class="user-name"><?= h($headerUserName) ?></span>
            <span class="user-role"><?= h($headerRoleLabel) ?></span>
        </div>
        <form class="logout-form" method="post" action="../../Login/Keluar.php">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="tab" value="<?= h($tabId) ?>">
            <button class="btn-logout" type="submit">Keluar</button>
        </form>
    </div>
</header>
