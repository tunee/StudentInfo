<?php
require __DIR__ . '/auth.php';
if (currentUser($db)) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
        session_regenerate_id(true); $_SESSION['user_id'] = $user['id']; $_SESSION['token'] = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in - StudentHub</title><link rel="stylesheet" href="style.css"></head><body><main class="auth-container"><section class="panel"><div class="panel-head"><h2>Sign in to StudentHub</h2><p>Enter your account details to continue.</p></div><?php if ($error): ?><div class="errors" role="alert"><?= h($error) ?></div><?php endif; ?><form method="post" class="student-form"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><label for="username">Username</label><input id="username" name="username" required autocomplete="username" value="<?= h($username ?? '') ?>"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"><button class="button primary submit">Sign in</button><a class="cancel" href="register.php">Need an account? Register</a></form></section></main></body></html>