<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

$current = currentUser($db);
if ($current && $current['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $stmt = $db->prepare('SELECT id, password_hash, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($account && password_verify((string)($_POST['password'] ?? ''), $account['password_hash']) && $account['role'] === 'admin') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['token'] = bin2hex(random_bytes(32));
        header('Location: admin.php');
        exit;
    }
    $error = 'Invalid administrator username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator sign in - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="auth-container">
    <section class="panel">
        <div class="panel-head">
            <h1>Administrator sign in</h1>
            <p>Enter your administrator account details to access the dashboard.</p>
        </div>
        <?php if ($error): ?><div class="errors" role="alert"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="student-form">
            <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
            <label for="username">Username</label>
            <input id="username" name="username" required autocomplete="username" value="<?= h($username) ?>">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="button primary submit" type="submit">Sign in as administrator</button>
            <a class="cancel" href="login.php">Regular sign in</a>
            <a class="cancel" href="index.php">Back to StudentHub</a>
        </form>
    </section>
</main>
</body>
</html>