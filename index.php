<?php

declare(strict_types=1);
require __DIR__ . '/auth.php';

$user = currentUser($db);
if (!$user) {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>StudentHub - Student Information System</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <header class="topbar">
        <div class="brand"><span class="brand-icon">S</span><div><strong>StudentHub</strong><small>INFORMATION SYSTEM</small></div></div>
        <nav class="account-nav" aria-label="Account"><a href="login.php">Sign in</a><a href="admin-login.php">Administrator sign in</a><a href="register.php">Register</a></nav>
    </header>
    <main class="container">
        <section class="intro">
            <div>
                <div class="eyebrow">STUDENT INFORMATION SYSTEM</div>
                <h1>Student information, all in one place.</h1>
                <p>Keep student records organized and easy to find.</p>
            </div>
        </section>
        <section class="panel" aria-labelledby="welcome-heading">
            <div class="panel-head">
                <h2 id="welcome-heading">Welcome to StudentHub</h2>
                <p>Sign in to view, search, and manage student details, including student IDs, courses, and year levels.</p>
            </div>
            <div class="panel-head">
                <a class="button primary" href="login.php">Sign in to the directory</a>
                <p>New to StudentHub? <a href="register.php">Create an account</a>.</p>
            </div>
        </section>
        <footer>StudentHub <span>&bull;</span> Student Information System</footer>
    </main>
    </body>
    </html>
    <?php
    exit;
}
$db->exec('CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY AUTOINCREMENT, student_number TEXT NOT NULL UNIQUE, name TEXT NOT NULL, email TEXT NOT NULL, course TEXT NOT NULL, year_level INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$errors = [];
$form = ['id'=>'', 'student_number'=>'', 'name'=>'', 'email'=>'', 'course'=>'', 'year_level'=>'1'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['token'], $_POST['token'] ?? '')) { http_response_code(403); exit('Invalid request token.'); }
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    if ($action === 'delete' && $id) {
        $stmt = $db->prepare('DELETE FROM students WHERE id = ?'); $stmt->execute([$id]);
        $_SESSION['notice'] = 'Student removed successfully.'; header('Location: index.php'); exit;
    }
    if ($action === 'save') {
        foreach ($form as $key => $value) $form[$key] = trim((string)($_POST[$key] ?? ''));
        if ($form['student_number'] === '' || $form['name'] === '' || $form['course'] === '') $errors[] = 'Student ID, name and course are required.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!in_array($form['year_level'], ['1','2','3','4','5','6'], true)) $errors[] = 'Choose a valid year level.';
        if (strlen($form['student_number']) > 50 || strlen($form['name']) > 120 || strlen($form['email']) > 254 || strlen($form['course']) > 120) $errors[] = 'One or more fields are too long.';
        if (!$errors) {
            try {
                if ($id) {
                    $stmt = $db->prepare('UPDATE students SET student_number=?, name=?, email=?, course=?, year_level=? WHERE id=?');
                    $stmt->execute([$form['student_number'], $form['name'], $form['email'], $form['course'], $form['year_level'], $id]);
                } else {
                    $stmt = $db->prepare('INSERT INTO students (student_number, name, email, course, year_level) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$form['student_number'], $form['name'], $form['email'], $form['course'], $form['year_level']]);
                }
                $_SESSION['notice'] = $id ? 'Student updated successfully.' : 'Student added successfully.'; header('Location: index.php'); exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') $errors[] = 'That student ID is already in use.'; else throw $e;
            }
        }
    }
} elseif (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
    $stmt = $db->prepare('SELECT * FROM students WHERE id = ?'); $stmt->execute([$_GET['edit']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC); if ($student) $form = $student;
}
$search = trim((string)($_GET['q'] ?? ''));
$stmt = $db->prepare('SELECT * FROM students WHERE name LIKE :q OR student_number LIKE :q OR email LIKE :q OR course LIKE :q ORDER BY name COLLATE NOCASE');
$stmt->execute(['q'=>'%' . $search . '%']); $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = (int)$db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$courses = (int)$db->query('SELECT COUNT(DISTINCT course) FROM students')->fetchColumn();
$notice = $_SESSION['notice'] ?? ''; unset($_SESSION['notice']);
?>


<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Student Information System</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><div class="brand"><span class="brand-icon">S</span><div><strong>StudentHub</strong><small>INFORMATION SYSTEM</small></div></div><nav class="account-nav"><span><?= h($user['username']) ?></span><?php if ($user['role'] === 'admin'): ?><a href="admin.php">Admin dashboard</a><a href="users.php">Manage users</a><?php endif; ?><form method="post" action="logout.php"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><button type="submit">Sign out</button></form></nav></header>
<main class="container"><div class="intro"><div><div class="eyebrow">DASHBOARD FOR STUDENTS</div><h1>Student directory</h1><p>Manage student records in one simple place.</p></div><a class="button primary" href="#student-form">+ Add student</a></div>
<div class="stats"><div class="stat"><span class="stat-icon blue">◈</span><div><span>Total students</span><strong><?= $total ?></strong></div></div><div class="stat"><span class="stat-icon purple">▤</span><div><span>Courses</span><strong><?= $courses ?></strong></div></div><div class="stat note"><span class="dot"></span><div><strong>Your directory, organized.</strong><span>Add and update student details below.</span></div></div></div>
<?php if ($notice): ?><div class="notice" role="status"><?= h($notice) ?></div><?php endif; ?>
<div class="layout"><section class="panel directory"><div class="panel-head"><div><h2>All students <span class="count"><?= count($students) ?></span></h2><p>View and manage your student records.</p></div></div><form class="search" method="get"><label class="sr-only" for="q">Search students</label><span>⌕</span><input id="q" name="q" value="<?= h($search) ?>" placeholder="Search name, ID, email or course"><button type="submit">Search</button><?php if ($search !== ''): ?><a href="index.php">Clear</a><?php endif; ?></form>
<?php if (!$students): ?><div class="empty"><div class="empty-icon">▤</div><h3><?= $search !== '' ? 'No students found' : 'No students yet' ?></h3><p><?= $search !== '' ? 'Try a different search term.' : 'Add your first student to get started.' ?></p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>STUDENT</th><th>STUDENT ID</th><th>COURSE</th><th>YEAR</th><th>ACTIONS</th></tr></thead><tbody><?php foreach ($students as $s): ?><tr><td><div class="student"><span class="avatar"><?= h(mb_strtoupper(mb_substr($s['name'], 0, 1))) ?></span><div><strong><?= h($s['name']) ?></strong><small><?= h($s['email']) ?></small></div></div></td><td><?= h($s['student_number']) ?></td><td><?= h($s['course']) ?></td><td><span class="badge">Year <?= h($s['year_level']) ?></span></td><td><div class="actions"><a href="?edit=<?= (int)$s['id'] ?>#student-form">Edit</a><form method="post" onsubmit="return confirm('Remove this student?');"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="delete" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel form-panel" id="student-form"><div class="panel-head"><div><h2><?= $form['id'] ? 'Edit student' : 'Add a student' ?></h2><p><?= $form['id'] ? 'Update the student details below.' : 'Enter the details to create a record.' ?></p></div></div><?php if ($errors): ?><div class="errors" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?><form method="post" class="student-form"><input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($form['id']) ?>"><label for="student_number">Student ID</label><input id="student_number" name="student_number" maxlength="50" required placeholder="e.g. STU-2026-001" value="<?= h($form['student_number']) ?>"><label for="name">Full name</label><input id="name" name="name" maxlength="120" required placeholder="e.g. Alex Johnson" value="<?= h($form['name']) ?>"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" required placeholder="e.g. alex@example.com" value="<?= h($form['email']) ?>"><label for="course">Course</label><input id="course" name="course" maxlength="120" required placeholder="e.g. Computer Science" value="<?= h($form['course']) ?>"><label for="year_level">Year level</label><select id="year_level" name="year_level"><?php for ($i=1; $i<=6; $i++): ?><option value="<?= $i ?>" <?= (string)$form['year_level'] === (string)$i ? 'selected' : '' ?>>Year <?= $i ?></option><?php endfor; ?></select><button class="button primary submit" type="submit"><?= $form['id'] ? 'Save changes' : '+ Add student' ?></button><?php if ($form['id']): ?><a class="cancel" href="index.php">Cancel editing</a><?php endif; ?></form></section></div><footer>StudentHub <span>•</span> Student Information System</footer></main></body></html>
