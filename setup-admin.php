<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Command line only.'); }
$db = new PDO('sqlite:' . __DIR__ . '/students.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL COLLATE NOCASE UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','user')), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
if ((int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() !== 0) exit("An administrator already exists. Use the admin page to manage accounts.\n");
echo "Admin username: "; $username = trim((string)fgets(STDIN));
echo "Admin password (input is visible): "; $password = rtrim((string)fgets(STDIN), "\r\n");
if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $username) || strlen($password) < 12 || strlen($password) > 72) exit("Invalid username or password. Use 3–50 username characters and a 12–72 byte password.\n");
$stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
try {
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') exit("That username is already in use. Choose a different username.\n");
    throw $e;
}
echo "Admin account created.\n";