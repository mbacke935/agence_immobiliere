<?php

require_once __DIR__ . '/db.php';

if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function require_role(PDO $pdo, string $role, string $login_path): void
{
    if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? null) !== $role) {
        header('Location: ' . $login_path);
        exit();
    }

    $stmt = $pdo->prepare('SELECT statut, role FROM UTILISATEURS WHERE id_user = :id_user');
    $stmt->execute(['id_user' => $_SESSION['id_user']]);
    $user = $stmt->fetch();

    if (!$user || $user['statut'] !== 'Actif' || $user['role'] !== $role) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . $login_path);
        exit();
    }
}

function upload_image(array $file, string $destination, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || ($file['size'] ?? 0) > 5 * 1024 * 1024
        || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        return null;
    }

    if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
        return null;
    }

    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    return move_uploaded_file($file['tmp_name'], $destination . DIRECTORY_SEPARATOR . $filename) ? $filename : null;
}
