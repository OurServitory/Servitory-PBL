<header class="navbar">
    <div class="nav-brand">
        <div class="logo-box" aria-hidden="true"><img src="../assets/img/servitory-logo.png?v=2" alt=""></div>
        <span class="brand-title">Servitory<span class="brand-dot">.</span></span>
    </div>
    <div class="nav-user">
        <div class="user-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($headerUserName, 0, 1))) ?></div>
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
