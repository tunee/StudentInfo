<?php
require __DIR__ . '/auth.php';
$user = requireAdmin($db);
$errors = []; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    if (($_POST['action'] ?? '') === 'add') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $username)) $errors[] = 'Username must be 3–50 letters, numbers, dots, underscores or hyphens.';
        if (strlen($password) < 12 || strlen($password) > 72) $errors[] = 'Password must be 12–72 bytes long.';
        if (!in_array($role, ['admin', 'user'], true)) $errors[] = 'Choose a valid role.';
        if (!$errors) {
            try {
                $stmt = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $_SESSION['notice'] = 'User added.'; header('Location: users.php'); exit;
            } catch (PDOException $e) { if ($e->getCode() === '23000') $errors[] = 'That username is already in use.'; else throw $e; }
        }
    } elseif (($_POST['action'] ?? '') === 'remove') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $errors[] = 'Choose a valid account to remove.';
        } elseif ($id === (int)$user['id']) {
            $errors[] = 'You cannot remove your own account.';
        } else {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                $errors[] = 'That account no longer exists.';
            } else {
                $_SESSION['notice'] = 'User removed.';
                header('Location: users.php');
                exit;
            }
        }
    }
}
$users = $db->query('SELECT id, username, role, created_at FROM users ORDER BY username COLLATE NOCASE')->fetchAll(PDO::FETCH_ASSOC);
$notice = $_SESSION['notice'] ?? ''; unset($_SESSION['notice']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage users - StudentHub</title><link rel="stylesheet" href="style.css"></head><body><header class="topbar"><div class="brand"><span class="brand-icon">S</span><div><strong>StudentHub</strong><small>INFORMATION SYSTEM</small></div></div><nav class="account-nav" aria-label="Administration"><a href="admin.php">Admin dashboard</a><a href="index.php">Student directory</a></nav></header><main class="container"><div class="intro"><div><div class="eyebrow">ADMINISTRATION</div><h1>Manage users</h1><p>Add or remove accounts that can sign in.</p></div></div><?php if ($notice): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?><div class="layout"><section class="panel"><div class="panel-head"><h2>Accounts</h2><p>Signed in as <?= h($user['username']) ?>.</p></div><div class="table-wrap"><table><thead><tr><th>USERNAME</th><th>ROLE</th><th>ACTION</th></tr></thead><tbody><?php foreach ($users as $account): ?><tr><td><?= h($account['username']) ?></td><td><?= h(ucfirst($account['role'])) ?></td><td><div class="actions"><?php if ((int)$account['id'] !== (int)$user['id']): ?><form method="post" onsubmit="return confirm('Remove this account?');"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$account['id'] ?>"><button class="delete">Remove</button></form><?php else: ?>Current account<?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section><section class="panel"><div class="panel-head"><h2>Add user</h2><p>Create an account for sign-in.</p></div><?php if ($errors): ?><div class="errors" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?><form method="post" class="student-form"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><input type="hidden" name="action" value="add"><label for="username">Username</label><input id="username" name="username" required maxlength="50" value="<?= h($username) ?>" autocomplete="off"><label for="password">Password</label><input id="password" name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password"><label for="role">Role</label><select id="role" name="role"><option value="user">User</option><option value="admin">Admin</option></select><button class="button primary submit">Add user</button></form></section></div></main></body></html>