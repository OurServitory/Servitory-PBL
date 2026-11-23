<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../Halaman/includes/koneksi.php';

$dryRun = in_array('--dry-run', $argv, true);
$sql = $dryRun
    ? "SELECT COUNT(*) FROM reports WHERE reported_at < CURRENT_TIMESTAMP - INTERVAL '30 days'"
    : "DELETE FROM reports WHERE reported_at < CURRENT_TIMESTAMP - INTERVAL '30 days'";

try {
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $affectedRows = $dryRun ? (int) $statement->fetchColumn() : $statement->rowCount();
    $action = $dryRun ? 'would be deleted' : 'deleted';
    fwrite(STDOUT, sprintf("%d report(s) older than 30 days %s.%s", $affectedRows, $action, PHP_EOL));
} catch (PDOException $exception) {
    fwrite(STDERR, 'Report cleanup failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
