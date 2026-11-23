<?php
require_once __DIR__ . '/includes/auth.php';

$viewer = currentUser();
$role = $viewer['role'] ?? '';
$viewerId = filter_var($viewer['id'] ?? null, FILTER_VALIDATE_INT);
if (authTabId() === '' || $viewerId === false
    || !in_array($role, ['pelapor', 'koordinator', 'teknisi'], true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$reportId = $_GET['id'] ?? '';
if (!is_string($reportId) || !ctype_digit($reportId) || (int) $reportId < 1) {
    http_response_code(404);
    exit('Bukti laporan tidak ditemukan.');
}
$evidenceSource = $_GET['source'] ?? 'report';
if (!is_string($evidenceSource) || !in_array($evidenceSource, ['report', 'completion'], true)) {
    http_response_code(404);
    exit('Bukti laporan tidak ditemukan.');
}

require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/report_evidence.php';

$statement = $pdo->prepare(
    'SELECT reporter_id, confirmed_at, evidence_data, evidence_mime,
            completion_evidence_data, completion_evidence_mime
     FROM reports
     WHERE id = :id'
);
$statement->execute(['id' => (int) $reportId]);
$report = $statement->fetch(PDO::FETCH_ASSOC);

if ($report === false
    || ($role === 'pelapor' && (int) $report['reporter_id'] !== (int) $viewerId)
    || ($role === 'teknisi' && $report['confirmed_at'] === null)
    || $report[$evidenceSource === 'completion' ? 'completion_evidence_data' : 'evidence_data'] === null) {
    http_response_code(404);
    exit('Bukti laporan tidak ditemukan.');
}

$mimeTypes = reportEvidenceMimeExtensions();
$mime = $report[$evidenceSource === 'completion' ? 'completion_evidence_mime' : 'evidence_mime'];
if (!is_string($mime) || !isset($mimeTypes[$mime])) {
    http_response_code(404);
    exit('Bukti laporan tidak ditemukan.');
}

$evidence = $report[$evidenceSource === 'completion' ? 'completion_evidence_data' : 'evidence_data'];
if (is_resource($evidence)) {
    $evidenceStream = $evidence;
    $evidence = stream_get_contents($evidenceStream);
    fclose($evidenceStream);
    if ($evidence === false) {
        http_response_code(500);
        exit('Bukti laporan gagal dibaca.');
    }
}
if (!is_string($evidence) || $evidence === '') {
    http_response_code(404);
    exit('Bukti laporan tidak ditemukan.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($evidence));
$filenamePrefix = $evidenceSource === 'completion' ? 'bukti-perbaikan-' : 'bukti-laporan-';
header('Content-Disposition: inline; filename="' . $filenamePrefix . (int) $reportId . '.' . $mimeTypes[$mime] . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
echo $evidence;
