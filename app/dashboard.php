<?php
require_once __DIR__.'/config.php';

if (!isset($mode) || !in_array($mode, ['vulnerable', 'secure'], true)) {
    http_response_code(404);
    exit('Not found');
}

$currentUser = lab_current_user($mode);
if ($currentUser === null) {
    header('Location: index.php');
    exit;
}

$isSecure = ($mode === 'secure');
$label = $isSecure ? 'Protected' : 'Vulnerable';
$color = $isSecure ? '#22c55e' : '#fb7185';
$users = [];
$error = null;

try {
    $db = lab_db();
    $users = $db->query('SELECT username, role FROM demo_users ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    $error = 'User directory is unavailable. Review the database setup.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CYBERMART / <?=h($label)?> Dashboard</title>
<link rel="stylesheet" href="../assets.css">
<script src="../lab.js" defer></script>
</head>
<body>
<div class="glow"></div>
<main>
<header>
    <div class="brand"><span class="logo">&#9671;</span> CYBERMART <span class="muted">LAB EDITION</span></div>
    <div class="header-actions">
        <span class="status" style="--accent:<?=$color?>"><i></i><?=$label?> Build</span>
        <form class="header-logout-form" action="logout.php" method="post" data-preserve-scroll="false">
            <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
            <button class="header-logout" type="submit">Déconnexion</button>
        </form>
    </div>
</header>

<section class="dashboard-hero">
    <p class="eyebrow">AUTHENTICATED DEMO AREA</p>
    <div class="dashboard-heading">
        <div>
            <h1>Member <span>dashboard.</span></h1>
            <p>Signed in as <strong><?=h($currentUser['username'])?></strong>. This directory contains fictitious lab users only.</p>
        </div>
        <a class="back-link" href="index.php">&larr; Back to lab</a>
    </div>
</section>

<section class="dashboard-grid">
    <article class="panel profile-card">
        <p class="eyebrow">CURRENT SESSION</p>
        <div class="avatar"><?=h(strtoupper(substr($currentUser['username'], 0, 1)))?></div>
        <h2><?=h($currentUser['username'])?></h2>
        <span class="role-badge"><?=h($currentUser['role'])?></span>
        <p class="muted">This session belongs only to the current <?=$isSecure ? 'protected' : 'vulnerable'?> build.</p>
    </article>

    <section class="panel directory-card">
        <div class="paneltop">
            <div><p class="eyebrow">DEMO USER DIRECTORY</p><h2>Registered users</h2></div>
            <span class="pill"><?=count($users)?> USERS</span>
        </div>
        <?php if ($error): ?>
            <div class="notice bad"><?=h($error)?></div>
        <?php else: ?>
            <div class="user-table-wrap">
                <table>
                    <thead><tr><th>Username</th><th>Role</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><span class="user-dot"></span><?=h($user['username'])?></td>
                            <td><?=h($user['role'])?></td>
                            <td><span class="online-status">Active</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>

<section class="inspector dashboard-note">
    <div class="paneltop">
        <div><p class="eyebrow">LAB OBSERVATION</p><h2>Why this page matters</h2></div>
        <span class="pill">SESSION REQUIRED</span>
    </div>
    <p class="muted">The dashboard requires a session created by the login page. Compare the two builds after the same login attempt: the protected build creates no session when the input is treated as data.</p>
</section>

<footer>CYBERMART SECURITY LAB <span>SQL Injection / Detection / Mitigation</span></footer>
</main>
</body>
</html>
