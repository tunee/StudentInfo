<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
$user = requireUser($db);
$db->exec('CREATE TABLE IF NOT EXISTS admissions (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, name TEXT NOT NULL, email TEXT NOT NULL, programme TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$errors = []; $name = ''; $email = ''; $programme = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    $name = trim((string)($_POST['name'] ?? '')); $email = trim((string)($_POST['email'] ?? '')); $programme = trim((string)($_POST['programme'] ?? ''));
    if ($name === '' || strlen($name) > 120) $errors[] = 'Enter a name of up to 120 bytes.';
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($programme === '' || strlen($programme) > 120) $errors[] = 'Enter a programme of up to 120 bytes.';
    if (!$errors) {
        $stmt = $db->prepare('INSERT INTO admissions (user_id, name, email, programme) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $name, $email, $programme]);
        $_SESSION['admission_notice'] = 'Application submitted successfully.';
        header('Location: admission.php'); exit;
    }
}
$review = isset($_GET['review']);
if ($review && $user['role'] !== 'admin') { http_response_code(403); exit('Admin access required.'); }
if ($review) {
    $applications = $db->query('SELECT a.*, u.username FROM admissions a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare('SELECT * FROM admissions WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$user['id']]); $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$notice = $_SESSION['admission_notice'] ?? ''; unset($_SESSION['admission_notice']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admission - StudentHub</title><link rel="stylesheet" href="style.css"></head><body>
<header class="topbar"><div class="brand"><span class="brand-icon">S</span><strong>StudentHub</strong></div><nav class="account-nav"><span><?= h($user['username']) ?></span><a href="index.php">Directory</a><?php if ($user['role'] === 'admin'): ?><a href="admission.php?review=1">Review applications</a><?php endif; ?></nav></header>
<main class="container"><div class="intro"><div><h1><?= $review ? 'Admission applications' : 'Apply for admission' ?></h1><p><?= $review ? 'Review submitted applications.' : 'Enter the programme you are interested in.' ?></p></div></div>
<?php if ($notice): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?><div class="layout"><section class="panel"><div class="panel-head"><h2><?= $review ? 'All applications' : 'Your applications' ?></h2></div>
<?php if (!$applications): ?><div class="empty"><p>No applications submitted yet.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>PROGRAMME</th><th>NAME</th><th>EMAIL</th><?php if ($review): ?><th>ACCOUNT</th><?php endif; ?><th>SUBMITTED (UTC)</th></tr></thead><tbody><?php foreach ($applications as $a): ?><tr><td><?= h($a['programme']) ?></td><td><?= h($a['name']) ?></td><td><?= h($a['email']) ?></td><?php if ($review): ?><td><?= h($a['username'] ?? 'Deleted account') ?></td><?php endif; ?><td><?= h($a['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php if (!$review): ?><section class="panel"><div class="panel-head"><h2>Application details</h2><p>All fields are required.</p></div><?php if ($errors): ?><div class="errors" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?><form method="post" class="student-form"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><label for="name">Full name</label><input id="name" name="name" maxlength="120" required value="<?= h($name) ?>"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" required value="<?= h($email) ?>"><label for="programme">Programme</label><input id="programme" name="programme" maxlength="120" required value="<?= h($programme) ?>"><button class="button primary submit">Submit application</button></form></section><?php endif; ?></div></main></body></html>