<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/koneksi.php';

$viewer = currentUser();
$viewerId = filter_var($viewer['id'] ?? null, FILTER_VALIDATE_INT);
if (authTabId() === '' || $viewerId === false
    || !in_array($viewer['role'] ?? '', ['pelapor', 'koordinator', 'teknisi'], true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$statement = $pdo->prepare(
    'SELECT profile_photo_data, profile_photo_mime
     FROM users
     WHERE id = :id'
);
$statement->execute(['id' => (int) $viewerId]);
$profilePhoto = $statement->fetch(PDO::FETCH_ASSOC);
$allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if ($profilePhoto === false || !isset($allowedMimeTypes[$profilePhoto['profile_photo_mime']])) {
    http_response_code(404);
    exit('Foto profil tidak ditemukan.');
}

$imageData = $profilePhoto['profile_photo_data'];
if (is_resource($imageData)) {
    $imageData = stream_get_contents($imageData);
    if ($imageData === false) {
        http_response_code(500);
        exit('Foto profil gagal dibaca.');
    }
}
if (!is_string($imageData) || $imageData === '') {
    http_response_code(404);
    exit('Foto profil tidak ditemukan.');
}

header('Content-Type: ' . $profilePhoto['profile_photo_mime']);
header('Content-Length: ' . strlen($imageData));
header('Content-Disposition: inline; filename="foto-profil.' . $allowedMimeTypes[$profilePhoto['profile_photo_mime']] . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
echo $imageData;
