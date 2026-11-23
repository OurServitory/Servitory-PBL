<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teknisi');

$statuses = ['Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak'];
$currentUser = currentUser();
$tabId = authTabId();
$query = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$location = $_GET['location'] ?? '';
$technicianId = $_GET['technician'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$sort = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'oldest' : 'newest';
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 8;

if (!in_array($status, $statuses, true)) {
    $status = '';
}
foreach (['dateFrom', 'dateTo'] as $dateVariable) {
    if ($$dateVariable !== '') {
        $date = DateTime::createFromFormat('!Y-m-d', $$dateVariable);
        if (!$date || $date->format('Y-m-d') !== $$dateVariable) {
            $$dateVariable = '';
        }
    }
}
if ($technicianId !== '' && !ctype_digit((string) $technicianId)) {
    $technicianId = '';
}

$where = ['r.confirmed_at IS NOT NULL', 'r.urgency IS NOT NULL'];
$parameters = [];
if ($query !== '') {
    $where[] = '(r.ticket ILIKE :query_ticket OR r.title ILIKE :query_title OR r.description ILIKE :query_description)';
    $parameters['query_ticket'] = '%' . $query . '%';
    $parameters['query_title'] = '%' . $query . '%';
    $parameters['query_description'] = '%' . $query . '%';
}
if ($status !== '') {
    $where[] = 'r.status = :status';
    $parameters['status'] = $status;
}
if ($location !== '') {
    $where[] = 'r.location = :location';
    $parameters['location'] = $location;
}
if ($technicianId !== '') {
    $where[] = 'r.technician_id = :technician_id';
    $parameters['technician_id'] = (int) $technicianId;
}
if ($dateFrom !== '') {
    $where[] = 'r.reported_at >= CAST(:date_from AS date)';
    $parameters['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'r.reported_at < (CAST(:date_to AS date) + INTERVAL \'1 day\')';
    $parameters['date_to'] = $dateTo;
}

$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$orderSql = $sort === 'oldest' ? 'ASC' : 'DESC';
$fromSql = " FROM reports r
    JOIN users reporter ON reporter.id = r.reporter_id
    LEFT JOIN users technician ON technician.id = r.technician_id";
$selectSql = 'SELECT r.id, r.ticket, reporter.name AS reporter_name, r.title, r.evidence_mime,
                     r.reported_at, r.completed_at, r.location,
                     technician.name AS technician_name, r.status, r.urgency';

$locations = $pdo->query(
    'SELECT DISTINCT location FROM reports
     WHERE confirmed_at IS NOT NULL AND urgency IS NOT NULL
     ORDER BY location'
)->fetchAll(PDO::FETCH_COLUMN);
$technicians = $pdo->query("SELECT id, name FROM users WHERE role = 'teknisi' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if (($_GET['export'] ?? '') === 'csv') {
    $statement = $pdo->prepare($selectSql . $fromSql . $whereSql . " ORDER BY r.reported_at $orderSql");
    $statement->execute($parameters);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="riwayat-laporan.csv"');
    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('Tidak dapat menyiapkan file ekspor CSV.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Nomor Tiket', 'Pelapor', 'Masalah', 'Tanggal Laporan', 'Tanggal Selesai', 'Lokasi', 'Teknisi', 'Urgensi', 'Status']);
    while ($report = $statement->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $report['ticket'],
            $report['reporter_name'],
            $report['title'],
            date('Y-m-d H:i', strtotime($report['reported_at'])),
            $report['completed_at'] === null ? '-' : date('Y-m-d H:i', strtotime($report['completed_at'])),
            $report['location'],
            $report['technician_name'] ?? '-',
            $report['urgency'],
            $report['status'],
        ]);
    }
    fclose($output);
    exit;
}

$countStatement = $pdo->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
$countStatement->execute($parameters);
$totalReports = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalReports / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$statement = $pdo->prepare($selectSql . $fromSql . $whereSql . " ORDER BY r.reported_at $orderSql LIMIT $perPage OFFSET $offset");
$statement->execute($parameters);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);

$filterValues = [
    'tab' => $tabId,
    'q' => $query,
    'status' => $status,
    'location' => $location,
    'technician' => $technicianId,
    'from' => $dateFrom,
    'to' => $dateTo,
    'sort' => $sort,
];
$exportUrl = 'riwayat.php?' . http_build_query(array_merge($filterValues, ['export' => 'csv']));
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
    <title>Teknisi / Riwayat Laporan - Servitory</title>
    <link rel="stylesheet" href="../assets/css/teknisi.css">
    <link rel="stylesheet" href="../assets/css/riwayat.css">
    <link rel="stylesheet" href="../assets/css/app-polish.css?v=6">
</head>
<body class="technician-history-page">
    <?php
    $headerUserName = (string) $currentUser['name'];
    $headerRoleLabel = 'Teknisi';
    include __DIR__ . '/../includes/header.php';
    ?>

    <nav class="sub-nav" aria-label="Navigasi teknisi">
        <div class="sub-nav-container">
            <a href="<?= h(tabUrl('dasbor.php')) ?>" class="tab-item">Dasbor</a>
            <a href="<?= h(tabUrl('riwayat.php')) ?>" class="tab-item active" aria-current="page">Riwayat Seluruh Laporan</a>
        </div>
    </nav>

    <main class="main-container" data-live-updates>
        <div class="page-header-row dashboard-hero technician-hero technician-history-hero">
            <div>
                <span class="eyebrow">ARSIP TEKNISI</span>
                <h2>Riwayat Seluruh Laporan</h2>
                <p class="technician-history-intro">Cari, saring, dan ekspor laporan yang tersimpan.</p>
            </div>
            <a class="btn-ekspor" href="<?= h($exportUrl) ?>">Ekspor CSV</a>
        </div>

        <form class="filter-card technician-history-filters" method="get" action="riwayat.php">
            <input type="hidden" name="tab" value="<?= h($tabId) ?>">
            <div class="technician-history-filter-heading">
                <div>
                    <h3>Saring laporan</h3>
                    <p>Gunakan satu atau beberapa kriteria untuk menemukan laporan.</p>
                </div>
            </div>
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="filter-q">Pencarian</label>
                    <input id="filter-q" name="q" type="search" class="filter-input" placeholder="Nomor tiket / kata kunci..." value="<?= h($query) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-status">Status</label>
                    <select id="filter-status" name="status" class="filter-input">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $option): ?>
                            <option value="<?= h($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-location">Ruang</label>
                    <select id="filter-location" name="location" class="filter-input">
                        <option value="">Semua Ruang</option>
                        <?php foreach ($locations as $option): ?>
                            <option value="<?= h($option) ?>" <?= $location === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-technician">Teknisi</label>
                    <select id="filter-technician" name="technician" class="filter-input">
                        <option value="">Semua Teknisi</option>
                        <?php foreach ($technicians as $option): ?>
                            <option value="<?= (int) $option['id'] ?>" <?= (string) $technicianId === (string) $option['id'] ? 'selected' : '' ?>><?= h($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-bottom-row">
                <div class="filter-group">
                    <label for="filter-from">Dari tanggal</label>
                    <input id="filter-from" name="from" type="date" class="filter-input" value="<?= h($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-to">Sampai tanggal</label>
                    <input id="filter-to" name="to" type="date" class="filter-input" value="<?= h($dateTo) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-sort">Urutkan</label>
                    <select id="filter-sort" name="sort" class="filter-input">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                    </select>
                </div>
                <a class="btn-reset" href="<?= h(tabUrl('riwayat.php')) ?>">Reset</a>
                <button class="btn-filter" type="submit">Terapkan Filter</button>
            </div>
        </form>

        <section class="technician-history-section">
            <div class="section-header-top technician-history-heading">
                <div>
                    <span class="eyebrow">DAFTAR LAPORAN</span>
                    <h2>Hasil riwayat</h2>
                </div>
                <span class="technician-history-count">
                    <strong><?= $totalReports ?></strong> laporan ditemukan
                </span>
            </div>
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th scope="col">Nomor Tiket</th>
                            <th scope="col">Pelapor</th>
                            <th scope="col">Masalah</th>
                            <th scope="col">Tanggal Laporan</th>
                            <th scope="col">Tanggal Selesai</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Bukti</th>
                            <th scope="col">Teknisi</th>
                            <th scope="col">Urgensi</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reports === []): ?>
                            <tr><td colspan="10">Tidak ada laporan yang cocok dengan filter.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><span class="technician-history-ticket"><?= h($report['ticket']) ?></span></td>
                                    <td><?= h($report['reporter_name']) ?></td>
                                    <td><?= h($report['title']) ?></td>
                                    <td><?= h(date('d M Y', strtotime($report['reported_at']))) ?></td>
                                    <td><?= $report['completed_at'] === null ? '-' : h(date('d M Y, H:i', strtotime($report['completed_at']))) ?></td>
                                    <td><?= h($report['location']) ?></td>
                                    <td>
                                        <?php if ($report['evidence_mime'] !== null): ?>
                                            <a class="evidence-link" href="<?= h(tabUrl('../Bukti.php?id=' . (int) $report['id'])) ?>" target="_blank" rel="noopener">Lihat foto</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($report['technician_name'] ?? '-') ?></td>
                                    <td><span class="badge <?= h('urgency-' . strtolower($report['urgency'])) ?>"><?= h($report['urgency']) ?></span></td>
                                    <td><span class="badge <?= h($statusClasses[$report['status']] ?? 'status-ditolak') ?>"><?= h($report['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <span>Menampilkan <?= $totalReports === 0 ? 0 : $offset + 1 ?>-<?= min($offset + $perPage, $totalReports) ?> dari <?= $totalReports ?> laporan</span>
                    <nav class="pagination" aria-label="Navigasi halaman">
                        <?php if ($page > 1): ?>
                            <a class="page-btn" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $page - 1]))) ?>">Sebelumnya</a>
                        <?php endif; ?>
                        <?php for ($pageNumber = max(1, $page - 2); $pageNumber <= min($totalPages, $page + 2); $pageNumber++): ?>
                            <a class="page-btn <?= $pageNumber === $page ? 'active' : '' ?>" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $pageNumber]))) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a class="page-btn" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $page + 1]))) ?>">Berikutnya</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/live-updates.js"></script>
</body>
</html>
