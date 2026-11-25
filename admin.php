<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
$user = requireAdmin($db);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator dashboard - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="brand"><span class="brand-icon">S</span><div><strong>StudentHub</strong><small>INFORMATION SYSTEM</small></div></div>
    <nav class="account-nav" aria-label="Account">
        <span><?= h($user['username']) ?></span>
        <form method="post" action="logout.php">
            <input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
            <button type="submit">Sign out</button>
        </form>
    </nav>
</header>
<main class="container">
    <div class="intro">
        <div><div class="eyebrow">ADMINISTRATION</div><h1>Administrator dashboard</h1><p>Choose a task to get started.</p></div>
    </div>
    <div class="layout">
        <section class="panel">
            <div class="panel-head">
                <h2>Manage users</h2>
                <p>View, add, and remove accounts.</p>
            </div>
            <div class="panel-head"><a class="button primary" href="users.php">Manage users</a></div>
        </section>
        <section class="panel">
            <div class="panel-head">
                <h2>Student directory</h2>
                <p>View, search, and manage student records.</p>
            </div>
            <div class="panel-head"><a class="button primary" href="index.php">Open directory</a></div>
        </section>
    </div>
</main>
</body>
</html>