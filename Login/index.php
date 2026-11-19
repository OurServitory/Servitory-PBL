<?php
require_once __DIR__ . '/../Halaman/includes/koneksi.php';
require_once __DIR__ . '/../Halaman/includes/auth.php';

$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$error = '';
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $tabId = authTabId();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = $pdo->prepare(
        'SELECT id, name, password_hash, role
         FROM users
         WHERE username = :username OR email = :email'
    );
    $statement->execute([
        'username' => $username,
        'email' => $username,
    ]);
    $authenticatedUsers = array_values(array_filter(
        $statement->fetchAll(PDO::FETCH_ASSOC),
        static fn (array $user): bool => password_verify($password, $user['password_hash'])
    ));

    $dashboardByRole = [
        'pelapor' => '../Halaman/Pelapor/Dasbor.php',
        'teknisi' => '../Halaman/Teknisi/dasbor.php',
        'koordinator' => '../Halaman/Koordinator/dasbor.php',
    ];

    if ($tabId !== '' && count($authenticatedUsers) === 1
        && isset($dashboardByRole[$authenticatedUsers[0]['role']])) {
        $user = $authenticatedUsers[0];
        $authenticatedTabs = $_SESSION['auth_tabs'] ?? [];
        if (!is_array($authenticatedTabs)) {
            $authenticatedTabs = [];
        }
        if ($authenticatedTabs === []) {
            session_regenerate_id(true);
        }
        $_SESSION['auth_tabs'] = $authenticatedTabs;
        $_SESSION['auth_tabs'][$tabId] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];
        header('Location: ' . $dashboardByRole[$user['role']] . '?tab=' . rawurlencode($tabId));
        exit;
    } else {
        $error = 'Username/email atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Servitory</title>
    <link rel="stylesheet" href="../Halaman/assets/css/style.css?v=4">
</head>
<body>

    <div class="login-card">
        <div class="logo-box" aria-hidden="true"><img src="../Halaman/assets/img/servitory-logo.png?v=2" alt=""></div>
        
        <h1 class="app-title">Servitory<span class="brand-dot">.</span></h1>
        <p class="app-subtitle">Pelaporan Fasilitas dan Peralatan</p>

        <?php if ($registered): ?>
            <p class="form-message form-message-success" role="status">Pendaftaran berhasil. Silakan masuk menggunakan akun Anda.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <form id="loginForm" method="post" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" id="authTabId" name="tab" value="<?= h(authTabId()) ?>">
            <div class="form-group">
                <label for="username">Username atau Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username/email" value="<?= h($username) ?>" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                            <path class="eye-slash" d="m3 3 18 18"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="extra-options">
                <span>Butuh reset password? Hubungi admin.</span>
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="register-link">
            Belum punya akun?             <a href="../Halaman/Pelapor/Daftar.php">Daftar sebagai Pelapor</a>
        </div>
    </div>

    <script>
        const tabInput = document.getElementById('authTabId');
        const loginUrl = new URL(window.location.href);
        let tabId = loginUrl.searchParams.get('tab');
        if (!/^[a-f0-9]{32}$/.test(tabId || '')) {
            const randomBytes = new Uint8Array(16);
            window.crypto.getRandomValues(randomBytes);
            tabId = Array.from(randomBytes, function(byte) {
                return byte.toString(16).padStart(2, '0');
            }).join('');
            loginUrl.searchParams.set('tab', tabId);
            window.history.replaceState(null, '', loginUrl.pathname + loginUrl.search + loginUrl.hash);
        }
        tabInput.value = tabId;
        document.getElementById('loginForm').action = 'index.php?tab=' + encodeURIComponent(tabId);

        document.querySelectorAll('[data-toggle-password]').forEach(function(button) {
            button.addEventListener('click', function() {
                const password = document.getElementById(button.dataset.togglePassword);
                const isVisible = password.type === 'text';
                password.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-pressed', String(!isVisible));
                button.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
            });
        });
    </script>
</body>
</html>