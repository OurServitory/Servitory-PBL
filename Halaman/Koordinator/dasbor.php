<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('koordinator');

$urgencies = ['Tinggi', 'Menengah', 'Rendah'];
$currentUser = currentUser();
$tabId = authTabId();
$message = isset($_GET['confirmed']) ? 'Urgensi berhasil ditentukan dan laporan dikonfirmasi untuk Teknisi.' : '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
    $urgency = $_POST['urgency'] ?? '';

    if (!$reportId || !in_array($urgency, $urgencies, true)) {
        $error = 'Laporan atau tingkat urgensi tidak valid.';
    } else {
        $statement = $pdo->prepare(
            "UPDATE reports
             SET urgency = :urgency, status = 'Diterima', confirmed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = 'Menunggu Konfirmasi'
               AND confirmed_at IS NULL AND urgency IS NULL"
        );
        $statement->execute([
            'urgency' => $urgency,
            'id' => $reportId,
        ]);

        if ($statement->rowCount() === 1) {
            header('Location: ' . tabUrl('dasbor.php?confirmed=1'));
            exit;
        }

        $error = 'Laporan sudah diproses atau statusnya telah berubah.';
    }
}

$reports = $pdo->query(
    "SELECT r.id, r.ticket, r.title, r.location, r.description, r.reported_at,
            reporter.name AS reporter_name
     FROM reports r
     JOIN users reporter ON reporter.id = r.reporter_id
     WHERE r.status = 'Menunggu Konfirmasi'
       AND r.confirmed_at IS NULL AND r.urgency IS NULL
     ORDER BY r.reported_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$historyReports = $pdo->query(
    "SELECT r.ticket, r.title, r.reported_at, r.urgency, r.status
     FROM reports r
     WHERE r.confirmed_at IS NOT NULL AND r.urgency IS NOT NULL
     ORDER BY r.reported_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$statusClasses = [
    'Diterima' => 'status-diterima',
    'Dijadwalkan' => 'status-dijadwalkan',
    'Selesai' => 'status-selesai',
    'Ditolak' => 'status-ditolak',
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koordinator / Dasbor - Servitory</title>
    <link rel="stylesheet" href="../assets/css/teknisi.css">
    <link rel="stylesheet" href="../assets/css/koordinator.css">
    <link rel="stylesheet" href="../assets/css/app-polish.css?v=3">
</head>

<body>
    <?php
    $headerUserName = (string) $currentUser['name'];
    $headerRoleLabel = 'Koordinator';
    include __DIR__ . '/../includes/header.php';
    ?>

    <main class="main-container" data-live-updates>
        <section class="section-block">
            <div class="section-header-top">
                <div>
                    <span class="eyebrow">PORTAL KOORDINATOR</span>
                    <h1>Verifikasi laporan masuk</h1>
                </div>
                <span class="info-text"><?= count($reports) ?> laporan menunggu konfirmasi</span>
            </div>

            <?php if ($message !== ''): ?>
                <p class="form-message form-message-success" role="status"><?= h($message) ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
            <?php endif; ?>

            <div class="reports-grid">
                <?php if ($reports === []): ?>
                    <p class="empty-state">Tidak ada laporan yang menunggu konfirmasi.</p>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <article class="report-card">
                            <div class="card-top-row">
                                <span class="ticket-id"><?= h($report['ticket']) ?></span>
                                <span class="badge status-menunggu-konfirmasi">Menunggu Konfirmasi</span>
                            </div>
                            <h2 class="report-title"><?= h($report['title']) ?></h2>
                            <p class="report-detail"><strong>Pelapor:</strong> <?= h($report['reporter_name']) ?></p>
                            <p class="report-detail"><strong>Lokasi:</strong> <?= h($report['location']) ?></p>
                            <p class="report-detail"><strong>Tanggal:</strong>
                                <?= h(date('d M Y', strtotime($report['reported_at']))) ?></p>
                            <details class="report-description">
                                <summary>Lihat deskripsi</summary>
                                <p><?= nl2br(h($report['description'])) ?></p>
                            </details>
                            <form class="confirmation-form" method="post" action="dasbor.php">
                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                                <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                <label for="urgency-<?= (int) $report['id'] ?>">Tingkat urgensi</label>
                                <select id="urgency-<?= (int) $report['id'] ?>" name="urgency" required>
                                    <option value="">Pilih urgensi</option>
                                    <?php foreach ($urgencies as $urgency): ?>
                                        <option value="<?= h($urgency) ?>"><?= h($urgency) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-confirm" type="submit">Tentukan &amp; konfirmasi</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-block history-section">
            <div class="section-header-top">
                <h2>Riwayat laporan masuk</h2>
                <span class="info-text"><?= count($historyReports) ?> laporan terkonfirmasi</span>
            </div>

            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Nomor Tiket</th>
                            <th>Laporan</th>
                            <th>Tanggal Masuk</th>
                            <th>Urgensi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($historyReports === []): ?>
                            <tr>
                                <td colspan="5">Belum ada riwayat laporan terkonfirmasi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyReports as $report): ?>
                                <tr>
                                    <td><?= h($report['ticket']) ?></td>
                                    <td><?= h($report['title']) ?></td>
                                    <td><?= h(date('d M Y', strtotime($report['reported_at']))) ?></td>
                                    <td><span
                                            class="badge <?= h('urgency-' . strtolower($report['urgency'])) ?>"><?= h($report['urgency']) ?></span>
                                    </td>
                                    <td><span
                                            class="badge <?= h($statusClasses[$report['status']]) ?>"><?= h($report['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/live-updates.js"></script>
</body>

</html>
