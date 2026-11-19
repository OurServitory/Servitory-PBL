<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function authTabId(): string
{
    $tabId = $_POST['tab'] ?? $_GET['tab'] ?? '';
    if (!is_string($tabId) || !preg_match('/^[a-f0-9]{32}$/', $tabId)) {
        return '';
    }

    return $tabId;
}

function currentUser(): array
{
    $tabId = authTabId();
    $tabs = $_SESSION['auth_tabs'] ?? [];
    if (!is_array($tabs)) {
        return [];
    }

    $user = $tabs[$tabId] ?? null;

    return is_array($user) ? $user : [];
}

function tabUrl(string $url): string
{
    $tabId = authTabId();
    if ($tabId === '') {
        return $url;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        throw new InvalidArgumentException('URL halaman tidak valid.');
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['tab'] = $tabId;

    $result = $parts['path'] ?? '';
    $result .= '?' . http_build_query($query);
    if (isset($parts['fragment'])) {
        $result .= '#' . $parts['fragment'];
    }

    return $result;
}

function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function requireRole(string $role): void
{
    $tabId = authTabId();
    if ($tabId === '' || (currentUser()['role'] ?? null) !== $role) {
        header('Location: ' . tabUrl('../../Login/index.php'));
        exit;
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
