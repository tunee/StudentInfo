<?php
declare(strict_types=1);
session_start();
$db = new PDO('sqlite:' . __DIR__ . '/students.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL COLLATE NOCASE UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','user')), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
if (empty($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): void { if (!hash_equals($_SESSION['token'], (string)($_POST['token'] ?? ''))) { http_response_code(403); exit('Invalid request token.'); } }
function currentUser(PDO $db): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $db->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function requireUser(PDO $db): array {
    $user = currentUser($db);
    if (!$user) { unset($_SESSION['user_id']); header('Location: login.php'); exit; }
    return $user;
}