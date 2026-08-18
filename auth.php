<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function auth_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_register(string $email, string $password): int
{
    $db = db();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO users (email, password_hash) VALUES (:email, :hash)');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
    $stmt->execute();

    return (int) $db->lastInsertRowID();
}

function auth_login(string $email, string $password): ?int
{
    $db = db();

    $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE email = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return null;
    }

    auth_start();
    $_SESSION['user_id'] = (int) $row['id'];
    session_regenerate_id(true);

    return (int) $row['id'];
}

function auth_logout(): void
{
    auth_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_user_id(): ?int
{
    auth_start();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_require(): int
{
    $userId = auth_user_id();
    if ($userId === null) {
        header('Location: login.php');
        exit;
    }
    return $userId;
}

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

function csrf_verify(): void
{
    auth_start();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}
