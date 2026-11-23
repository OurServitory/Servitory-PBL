<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teknisi');

$currentUser = currentUser();
$tabId = authTabId();
$technicianId = (int) $currentUser['id'];
$message = '';
$error = '';
$maxEvidenceBytes = 5 * 1024 * 1024;
require_once __DIR__ . '/../includes/report_evidence.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';
    $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
    $transitions = [
        'accept' => ['Diterima', 'Diterima', true],
        'reject' => ['Diterima', 'Ditolak', false],
        'schedule' => ['Diterima', 'Dijadwalkan', false],
        'complete' => ['Dijadwalkan', 'Selesai', false],
    ];

    if (!$reportId || !isset($transitions[$action])) {
        $error = 'Aksi laporan tidak valid.';
    } else {
        $completionEvidenceHex = '';
        $completionEvidenceMime = null;
        if ($action === 'complete') {
            $uploadResult = validateReportEvidenceUpload(
                $_FILES['completion_evidence'] ?? null,
                $maxEvidenceBytes
            );
            $error = $uploadResult['error'];
            if ($error === '' && $uploadResult['data'] !== null) {
                $completionEvidenceHex = bin2hex($uploadResult['data']);
                $completionEvidenceMime = $uploadResult['mime'];
            }
        }

        if ($error === '') {
            [$fromStatus, $toStatus, $assignOnAccept] = $transitions[$action];
            if ($assignOnAccept) {
                $statement = $pdo->prepare(
                    "UPDATE reports SET technician_id = :technician_id
                     WHERE id = :id AND status = :status AND technician_id IS NULL
                       AND confirmed_at IS NOT NULL AND urgency IS NOT NULL"
                );
            } elseif ($action === 'complete') {
                $statement = $pdo->prepare(
                    "UPDATE reports
                     SET status = :new_status, technician_id = :technician_id,
                         completed_at = CURRENT_TIMESTAMP,
                         completion_evidence_data = decode(NULLIF(:completion_evidence_hex, ''), 'hex'),
                         completion_evidence_mime = :completion_evidence_mime
                     WHERE id = :id AND status = :status
                       AND (technician_id IS NULL OR technician_id = :technician_match)
                       AND confirmed_at IS NOT NULL AND urgency IS NOT NULL"
                );
            } else {
                $statement = $pdo->prepare(
                    'UPDATE reports SET status = :new_status, technician_id = :technician_id
                     WHERE id = :id AND status = :status
                       AND (technician_id IS NULL OR technician_id = :technician_match)
                       AND confirmed_at IS NOT NULL AND urgency IS NOT NULL'
                );
            }

            $parameters = [
                'technician_id' => $technicianId,
                'id' => $reportId,
                'status' => $fromStatus,
            ];
            if (!$assignOnAccept) {
                $parameters['new_status'] = $toStatus;
                $parameters['technician_match'] = $technicianId;
                if ($action === 'complete') {
                    $parameters['completion_evidence_hex'] = $completionEvidenceHex;
                    $parameters['completion_evidence_mime'] = $completionEvidenceMime;
                }
            }
            $statement->execute($parameters);

            if ($statement->rowCount() === 1) {
                $message = [
                    'accept' => 'Laporan berhasil diterima dan ditugaskan kepada Anda.',
                    'reject' => 'Laporan berhasil ditolak.',
                    'schedule' => 'Perbaikan berhasil dijadwalkan.',
                    'complete' => 'Laporan berhasil ditandai selesai.',
                ][$action];
            } else {
                $error = 'Laporan sudah diproses teknisi lain atau statusnya telah berubah.';
            }
        }
    }
}

$statement = $pdo->prepare(
    "SELECT r.id, r.ticket, r.title, r.location, r.description, r.evidence_mime,
            r.status, r.urgency, r.reported_at, u.name AS reporter_name,
            technician.name AS technician_name
     FROM reports r
     JOIN users u ON u.id = r.reporter_id
     LEFT JOIN users technician ON technician.id = r.technician_id
     WHERE r.status IN ('Diterima', 'Dijadwalkan')
       AND r.confirmed_at IS NOT NULL AND r.urgency IS NOT NULL
       AND (r.technician_id IS NULL OR r.technician_id = :technician_id)
     ORDER BY CASE r.urgency
                  WHEN 'Tinggi' THEN 1
                  WHEN 'Menengah' THEN 2
                  ELSE 3
              END,
              r.reported_at ASC"
);
$statement->execute(['technician_id' => $technicianId]);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count(array_filter($reports, static fn ($report) => $report['status'] === 'Diterima' && $report['technician_name'] === null));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisi / Dasbor - Servitory</title>
    <link rel="stylesheet" href="../assets/css/teknisi.css?v=3">
    <link rel="stylesheet" href="../assets/css/riwayat.css">
    <link rel="stylesheet" href="../assets/css/app-polish.css?v=5">
</head>
<body class="technician-dashboard">
    <?php
    $headerUserName = (string) $currentUser['name'];
    $headerRoleLabel = 'Teknisi';
    include __DIR__ . '/../includes/header.php';
    ?>

    <nav class="sub-nav" aria-label="Navigasi teknisi">
        <div class="sub-nav-container">
            <a href="<?= h(tabUrl('dasbor.php')) ?>" class="tab-item active">Dasbor</a>
            <a href="<?= h(tabUrl('riwayat.php')) ?>" class="tab-item">Riwayat Seluruh Laporan</a>
        </div>
    </nav>

    <main class="main-container" data-live-updates>
        <?php if ($message !== ''): ?>
            <p class="form-message form-message-success" role="status"><?= h($message) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <section class="section-block">
            <div class="section-header-top dashboard-hero technician-hero">
                <div>
                    <span class="eyebrow">PORTAL TEKNISI</span>
                    <h2>Laporan aktif</h2>
                </div>
                <span class="info-text"><?= $pendingCount ?> laporan baru menunggu diambil</span>
            </div>
            <div class="reports-grid">
                <?php if ($reports === []): ?>
                    <p class="empty-state">Belum ada laporan aktif untuk ditangani.</p>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <article class="report-card">
                            <div class="card-top-row">
                                <span class="ticket-id"><?= h($report['ticket']) ?></span>
                                <span class="badge-baru"><?= h($report['status']) ?></span>
                            </div>
                            <h3 class="report-title"><?= h($report['title']) ?></h3>
                            <p class="report-detail"><strong>Urgensi:</strong> <span class="badge <?= h('urgency-' . strtolower($report['urgency'])) ?>"><?= h($report['urgency']) ?></span></p>
                            <p class="report-detail"><strong>Pelapor:</strong> <?= h($report['reporter_name']) ?></p>
                            <p class="report-detail"><strong>Lokasi:</strong> <?= h($report['location']) ?></p>
                            <p class="report-detail"><strong>Tanggal:</strong> <?= h(date('d M Y', strtotime($report['reported_at']))) ?></p>
                            <details class="report-description">
                                <summary>Lihat deskripsi</summary>
                                <p><?= nl2br(h($report['description'])) ?></p>
                            </details>
                            <?php if ($report['evidence_mime'] !== null): ?>
                                <p class="report-detail"><strong>Bukti:</strong> <a class="evidence-link" href="<?= h(tabUrl('../Bukti.php?id=' . (int) $report['id'])) ?>" target="_blank" rel="noopener">Lihat foto</a></p>
                            <?php endif; ?>
                            <div class="card-actions">
                                <?php if ($report['status'] === 'Diterima' && $report['technician_name'] === null): ?>
                                    <form method="post" action="dasbor.php">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-terima" type="submit" name="action" value="accept">Terima</button>
                                    </form>
                                    <form method="post" action="dasbor.php" data-confirm="Tolak laporan ini?">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-tolak" type="submit" name="action" value="reject">Tolak</button>
                                    </form>
                                <?php elseif ($report['status'] === 'Diterima'): ?>
                                    <form method="post" action="dasbor.php">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-jadwal" type="submit" name="action" value="schedule">Jadwalkan Perbaikan</button>
                                    </form>
                                <?php else: ?>
                                    <form class="complete-report-form" method="post" action="dasbor.php" enctype="multipart/form-data" data-confirm="Tandai laporan ini selesai?">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="tab" value="<?= h($tabId) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <label for="completion-evidence-<?= (int) $report['id'] ?>">Foto bukti perbaikan (opsional)</label>
                                        <input id="completion-evidence-<?= (int) $report['id'] ?>" type="file" name="completion_evidence" accept="image/jpeg,image/png,image/webp">
                                        <small>JPG, PNG, atau WebP. Maksimal 5 MB.</small>
                                        <button class="btn-terima" type="submit" name="action" value="complete">Tandai Selesai</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-block scheduling-section">
            <div class="scheduling-content">
                <h2>Alur penanganan</h2>
                <div class="workflow-flow">
                    <span class="status-box status-box-received">Diterima</span>
                    <span class="workflow-arrow" aria-hidden="true">&rarr;</span>
                    <span class="status-box status-box-scheduled">Dijadwalkan</span>
                    <span class="workflow-arrow" aria-hidden="true">&rarr;</span>
                    <span class="status-box status-box-completed">Selesai</span>
                </div>
            </div>
            <a class="btn-jadwal" href="<?= h(tabUrl('riwayat.php')) ?>">Lihat Riwayat Laporan</a>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
        document.addEventListener('submit', function(event) {
            const form = event.target.closest('form[data-confirm]');
            if (form && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    </script>
    <script src="../assets/js/live-updates.js"></script>
</body>
</html>
