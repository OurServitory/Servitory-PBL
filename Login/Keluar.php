<?php
require_once __DIR__ . '/../Halaman/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}

verifyCsrfToken();
$tabId = authTabId();
if ($tabId !== '' && isset($_SESSION['auth_tabs']) && is_array($_SESSION['auth_tabs'])) {
    unset($_SESSION['auth_tabs'][$tabId]);
}

header('Location: index.php' . ($tabId === '' ? '' : '?tab=' . rawurlencode($tabId)));
exit;
