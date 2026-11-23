<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile_photo.php';

$currentUser = currentUser();
$tabId = authTabId();
$roles = [
    'pelapor' => 'Pelapor',
    'teknisi' => 'Teknisi',
    'koordinator' => 'Koordinator',
];
$role = $currentUser['role'] ?? '';
if (authTabId() === '' || !isset($roles[$role])) {
    header('Location: ../../Login/index.php');
    exit;
}

$userId = (int) $currentUser['id'];
$message = isset($_GET['updated']) ? 'Foto profil berhasil diperbarui.' : '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $uploadResult = validateProfilePhotoUpload($_FILES['profile_photo'] ?? null, 5 * 1024 * 1024);
    $error = $uploadResult['error'];

    if ($error === '') {
        $statement = $pdo->prepare(
            "UPDATE users
             SET profile_photo_data = decode(:photo_hex, 'hex'),
                 profile_photo_mime = :photo_mime
             WHERE id = :id"
        );
        $statement->execute([
            'photo_hex' => bin2hex($uploadResult['data']),
            'photo_mime' => $uploadResult['mime'],
            'id' => $userId,
        ]);

        header('Location: ' . tabUrl('index.php?updated=1'));
        exit;
    }
}

$statement = $pdo->prepare(
    'SELECT name, username, email, role, created_at, profile_photo_mime
     FROM users
     WHERE id = :id'
);
$statement->execute(['id' => $userId]);
$profile = $statement->fetch(PDO::FETCH_ASSOC);
if ($profile === false) {
    http_response_code(404);
    exit('Profil tidak ditemukan.');
}

$dashboardByRole = [
    'pelapor' => '../Pelapor/Dasbor.php',
    'teknisi' => '../Teknisi/dasbor.php',
    'koordinator' => '../Koordinator/dasbor.php',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Servitory</title>
    <link rel="stylesheet" href="../assets/css/teknisi.css">
    <link rel="stylesheet" href="../assets/css/app-polish.css?v=5">
    <link rel="stylesheet" href="../assets/css/profile.css?v=3">
</head>
<body>
    <?php
    $headerUserName = (string) $profile['name'];
    $headerRoleLabel = $roles[$profile['role']];
    include __DIR__ . '/../includes/header.php';
    ?>

    <main class="main-container profile-page">
        <div class="profile-heading">
            <div>
                <span class="eyebrow">AKUN SAYA</span>
                <h1>Profil pengguna</h1>
                <p>Informasi akun Anda di Servitory.</p>
            </div>
            <a class="profile-back-link" href="<?= h(tabUrl($dashboardByRole[$role])) ?>">Kembali ke dasbor</a>
        </div>

        <?php if ($message !== ''): ?>
            <p class="form-message form-message-success" role="status"><?= h($message) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <section class="profile-card" aria-labelledby="profile-name">
            <div class="profile-summary">
                <button class="profile-photo-trigger" type="button" aria-label="Lihat foto profil"
                    aria-haspopup="dialog" aria-controls="profile-photo-dialog">
                    <span class="profile-photo">
                        <?php if ($profile['profile_photo_mime'] !== null): ?>
                            <img src="../ProfilFoto.php?tab=<?= h(rawurlencode($tabId)) ?>" alt="">
                        <?php else: ?>
                            <span aria-hidden="true"><?= h(mb_strtoupper(mb_substr($profile['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="profile-photo-hint">Lihat foto</span>
                </button>
                <div>
                    <h2 id="profile-name"><?= h($profile['name']) ?></h2>
                    <span class="profile-role"><?= h($roles[$profile['role']]) ?></span>
                </div>
            </div>

            <dl class="profile-details">
                <div><dt>Nama lengkap</dt><dd><?= h($profile['name']) ?></dd></div>
                <div><dt>Username</dt><dd><?= h($profile['username']) ?></dd></div>
                <div><dt>Email</dt><dd><?= h($profile['email']) ?></dd></div>
                <div><dt>Bergabung sejak</dt><dd><?= h(date('d M Y', strtotime($profile['created_at']))) ?></dd></div>
            </dl>

        </section>

        <dialog class="profile-photo-dialog" id="profile-photo-dialog" aria-labelledby="profile-photo-dialog-title">
            <form class="profile-photo-change" method="post" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                <button class="profile-photo-dialog-close" type="button" aria-label="Tutup">×</button>
                <h2 id="profile-photo-dialog-title">Foto profil</h2>
                <label class="profile-photo-dialog-image" for="profile-photo-input">
                    <?php if ($profile['profile_photo_mime'] !== null): ?>
                        <img src="../ProfilFoto.php?tab=<?= h(rawurlencode($tabId)) ?>" alt="Foto profil <?= h($profile['name']) ?>">
                    <?php else: ?>
                        <span aria-hidden="true"><?= h(mb_strtoupper(mb_substr($profile['name'], 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="profile-photo-dialog-hint">Klik gambar untuk mengganti</span>
                </label>
                <p>JPG, PNG, atau WebP. Maksimal 5 MB.</p>
                <input id="profile-photo-input" class="profile-photo-input" type="file" name="profile_photo"
                    accept="image/jpeg,image/png,image/webp" required>
            </form>
        </dialog>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
        const profilePhotoDialog = document.getElementById('profile-photo-dialog');
        document.querySelector('.profile-photo-trigger').addEventListener('click', function() {
            profilePhotoDialog.showModal();
        });
        document.querySelector('.profile-photo-dialog-close').addEventListener('click', function() {
            profilePhotoDialog.close();
        });
        profilePhotoDialog.addEventListener('click', function(event) {
            if (event.target === profilePhotoDialog) {
                profilePhotoDialog.close();
            }
        });
        document.getElementById('profile-photo-input').addEventListener('change', function(event) {
            if (event.target.files.length > 0) {
                event.target.form.submit();
            }
        });
    </script>
</body>
</html>
