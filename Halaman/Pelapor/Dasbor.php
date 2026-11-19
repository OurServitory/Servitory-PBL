<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pelapor');

$currentUser = currentUser();
$tabId = authTabId();
$reporterId = (int) $currentUser['id'];
$error = '';
$locations = [
    'R.05.01 - R.T 1',
    'R.05.02 - R.T 2',
    'R.05.03 - R.T 3',
    'R.05.04 - R.T 4',
    'R.05.05 - R.T 5',
    'R.05.06 - R.T 6',
    'R.05.07 - R.T 7',
    'R.05.08 - Lab. Proyek 1',
    'R.05.10 - R. Teknisi',
    'R.06.01 - R. Dosen 1',
    'R.06.02 - R. Dosen 2',
    'R.06.03 - R. Dosen 3',
    'R.06.04 - R. Dosen 4',
    'R.06.05 - R. Jurusan TI',
    'R.06.06 - R. Dosen 5',
    'R.06.07 - R. Program Studi',
    'R.06.08 - R. Dosen 6',
    'R.06.13 - Library',
    'R.06.14 - R. Arsip',
    'R.06.15 - LSI 1',
    'R.06.16 - LSI 2',
    'R.06.17 - LSI 3',
    'R.06.18 - Lab. Proyek 2',
    'R.06.19 - Lab. Proyek 3',
    'R.06.20 - R. Ecosystem',
    'R.07.01 - LP 1',
    'R.07.02 - LP 2',
    'R.07.03 - LP 3',
    'R.07.04 - LP 4',
    'R.07.05 - LP 5',
    'R.07.06 - LP 6',
    'R.07.07 - Lab. Komputasi Jaringan 1',
    'R.07.08 - LP 7',
    'R.07.10 - R. Teknisi',
    'R.07.12 - Kantin',
    'R.07.13 - Lab. Komputasi Jaringan 2',
    'R.07.14 - LP 8',
    'R.07.15 - Lab. Komputasi Jaringan 3',
    'R.07.16 - Lab. Visual Komputer 1',
    'R.07.17 - R. Kelas Teori 8',
    'R.07.18 - Lab. Visual Komputer 2',
    'R.07.19 - Lab. Proyek 4',
    'R.07.20 - R. Kecerdasan Buatan 1',
    'R.08.01 - R. Kelas Teori',
    'R.08.04 - R. Teknisi',
    'R.08.05 - R. Kelas Teori 9',
    'R.08.06 - R. Kelas Teori 10',

];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || mb_strlen($title) > 150 || $location === '' || mb_strlen($location) > 100
        || $description === '') {
        $error = 'Lengkapi semua kolom dengan panjang yang sesuai.';
    } elseif (!in_array($location, $locations, true)) {
        $error = 'Pilih lokasi dari daftar nama ruang yang tersedia.';
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO reports (ticket, title, location, description, reporter_id)
             VALUES (:ticket, :title, :location, :description, :reporter_id)'
        );
        $statement->execute([
            'ticket' => 'TKT-' . strtoupper(bin2hex(random_bytes(4))),
            'title' => $title,
            'location' => $location,
            'description' => $description,
            'reporter_id' => $reporterId,
        ]);
        header('Location: ' . tabUrl('Dasbor.php?submitted=1'));
        exit;
    }
}

$statement = $pdo->prepare(
    'SELECT ticket, title, reported_at, location, status, urgency
     FROM reports
     WHERE reporter_id = :reporter_id
     ORDER BY reported_at DESC'
);
$statement->execute(['reporter_id' => $reporterId]);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);
$statusClasses = [
    'Diterima' => 'status-diterima',
    'Menunggu Konfirmasi' => 'status-menunggu-konfirmasi',
    'Dijadwalkan' => 'status-dijadwalkan',
    'Selesai' => 'status-selesai',
    'Ditolak' => 'status-ditolak',
];
$activeReports = count(array_filter($reports, static fn ($report) => $report['status'] !== 'Selesai' && $report['status'] !== 'Ditolak'));
$completedReports = count(array_filter($reports, static fn ($report) => $report['status'] === 'Selesai'));
$displayName = (string) $currentUser['name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelapor / Dasbor - Servitory</title>
    <link rel="stylesheet" href="../assets/css/dasbor.css?v=6">
    <link rel="stylesheet" href="../assets/css/app-polish.css?v=3">
</head>
<body>
    <?php
    $headerUserName = $displayName;
    $headerRoleLabel = 'Pelapor';
    include __DIR__ . '/../includes/header.php';
    ?>

    <main class="main-container" data-live-updates>
        <section class="welcome-banner">
            <div>
                <span class="eyebrow">PORTAL PELAPOR</span>
                <h1>Halo, <?= h(explode(' ', $displayName)[0]) ?> <span aria-hidden="true">👋</span></h1>
                <p>Pantau laporan fasilitas dan peralatan Anda dengan mudah.</p>
            </div>
            <div class="welcome-mark" aria-hidden="true"><img src="../assets/img/servitory-logo.png?v=2" alt=""></div>
        </section>

        <?php if (isset($_GET['submitted'])): ?>
            <p class="form-message form-message-success" role="status">Laporan berhasil dikirim.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Ringkasan laporan">
            <article class="summary-card">
                <span class="summary-icon icon-total" aria-hidden="true">▤</span>
                <div><p>Total laporan</p><strong><?= count($reports) ?></strong></div>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-progress" aria-hidden="true">◷</span>
                <div><p>Sedang diproses</p><strong><?= $activeReports ?></strong></div>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-done" aria-hidden="true">✓</span>
                <div><p>Selesai</p><strong><?= $completedReports ?></strong></div>
            </article>
        </section>

        <button class="create-report-card" id="openModalBtn" type="button">
            <span class="plus-icon" aria-hidden="true">+</span>
            <span class="create-report-copy">
                <strong>Buat laporan baru</strong>
                <span>Laporkan kendala peralatan atau fasilitas di ruangan Anda.</span>
            </span>
            <span class="create-report-arrow" aria-hidden="true"></span>
        </button>

        <section class="history-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">PEMANTAUAN</span>
                    <h2>Riwayat laporan</h2>
                    <p>Lihat perkembangan laporan yang pernah Anda kirim.</p>
                </div>
                <span class="report-count"><?= count($reports) ?> laporan</span>
            </div>

            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr><th>Nomor Tiket</th><th>Masalah</th><th>Tanggal</th><th>Lokasi</th><th>Urgensi</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($reports === []): ?>
                            <tr><td colspan="6">Anda belum mengirim laporan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td class="ticket-cell"><?= h($report['ticket']) ?></td>
                                    <td class="title-cell"><?= h($report['title']) ?></td>
                                    <td><?= h(date('d M Y', strtotime($report['reported_at']))) ?></td>
                                    <td><?= h($report['location']) ?></td>
                                    <td>
                                        <?php if ($report['urgency'] === null): ?>
                                            -
                                        <?php else: ?>
                                            <span class="urgency-<?= h(strtolower($report['urgency'])) ?>"><?= h($report['urgency']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= h($statusClasses[$report['status']] ?? 'status-ditolak') ?>"><?= h($report['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <div>
                    <span class="eyebrow">LAPORAN BARU</span>
                    <h2 id="modalTitle">Buat laporan baru</h2>
                </div>
                <button class="close-btn" id="closeModalBtn" type="button" aria-label="Tutup">&times;</button>
            </div>

            <form class="modal-form" method="post" action="Dasbor.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                <div class="form-group">
                    <label for="judul">Perangkat Yang Bermasalah <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="judul" name="title" class="form-control" maxlength="150" placeholder="Contoh: Proyektor tidak menyala" required>
                </div>
                <div class="form-group">
                    <label for="lokasi">Lokasi / nama ruang <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="lokasi" name="location" class="form-control" list="room-suggestions" maxlength="100" placeholder="Cari kode atau nama ruang" autocomplete="off" required>
                    <datalist id="room-suggestions">
                        <?php foreach ($locations as $locationOption): ?>
                            <option value="<?= h($locationOption) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi Masalah</label>
                    <textarea id="deskripsi" name="description" class="form-control textarea-control" placeholder="Jelaskan kendala yang terjadi..." rows="4"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelBtn">Batal</button>
                    <button type="submit" class="btn-submit">Kirim laporan</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const modal = document.getElementById('reportModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        function closeModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        document.addEventListener('click', function(event) {
            const target = event.target instanceof Element ? event.target.closest('#openModalBtn') : null;
            if (!target) return;

            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.getElementById('judul').focus();
        });
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        window.addEventListener('click', function(event) {
            if (event.target === modal) closeModal();
        });
        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });
    </script>
    <script src="../assets/js/live-updates.js"></script>
</body>
</html>
