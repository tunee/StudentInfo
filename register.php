<?php
require __DIR__ . '/auth.php';
if (currentUser($db)) { header('Location: index.php'); exit; }
$errors = []; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $username)) $errors[] = 'Username must be 3–50 letters, numbers, dots, underscores or hyphens.';
    if (strlen($password) < 12 || strlen($password) > 72) $errors[] = 'Password must be 12–72 bytes long.';
    if ($password !== (string)($_POST['confirm_password'] ?? '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        try {
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$db->lastInsertId();
            $_SESSION['token'] = bin2hex(random_bytes(32));
            header('Location: index.php'); exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'That username is already in use.'; else throw $e;
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Register - StudentHub</title><link rel="stylesheet" href="style.css"></head><body><main class="auth-container"><section class="panel"><div class="panel-head"><h2>Create an account</h2><p>Register to sign in to StudentHub.</p></div><?php if ($errors): ?><div class="errors" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?><form method="post" class="student-form"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><label for="username">Username</label><input id="username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]{3,50}" autocomplete="username" value="<?= h($username) ?>"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><button class="button primary submit">Create account</button><a class="cancel" href="login.php">Already have an account? Sign in</a></form></section></main></body></html>