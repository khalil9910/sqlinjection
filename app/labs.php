<?php
require_once __DIR__.'/config.php';

if (!isset($mode) || !in_array($mode, ['vulnerable', 'secure'], true)) {
    http_response_code(404);
    exit('Not found');
}

$isSecure = ($mode === 'secure');
$label = $isSecure ? 'Protected' : 'Vulnerable';
$color = $isSecure ? '#22c55e' : '#fb7185';
$exercise = request_string($_GET, 'exercise');
$action = request_string($_POST, 'action');

$authUsername = request_string($_POST, 'username');
$authPassword = request_string($_POST, 'password');
$authAttempted = ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'auth');
$authResult = null;
$authError = null;
$authTrace = null;

$unionInput = request_string($_GET, 'union_q');
$unionRows = null;
$unionError = null;
$unionTrace = null;

$errorInput = request_string($_GET, 'error_q');
$errorState = null;
$errorTrace = null;
$errorDetails = null;
$errorRows = [];

$booleanInput = request_string($_GET, 'boolean_q');
$booleanState = null;
$booleanError = null;
$booleanTrace = null;

$timeInput = request_string($_GET, 'time_q');
$timeMs = null;
$timeError = null;
$timeTrace = null;

if ($authAttempted) {
    try {
        if ($isSecure) {
            $authTrace = 'SELECT username, role FROM demo_users WHERE username = ? AND demo_password = ? LIMIT 1';
            $stmt = lab_db()->prepare($authTrace);
            $stmt->execute([$authUsername, $authPassword]);
        } else {
            $authTrace = "SELECT username, role FROM demo_users WHERE username = '$authUsername' AND demo_password = '$authPassword' LIMIT 1";
            $stmt = lab_db()->query($authTrace);
        }
        $authResult = $stmt->fetch() ?: false;
        if ($authResult) {
            lab_sign_in($mode, $authResult);
            header('Location: dashboard.php', true, 303);
            exit;
        }
    } catch (Throwable $e) {
        $authError = $isSecure ? 'The protected query treated the supplied value as data.' : $e->getMessage();
    }
}

if ($exercise === 'union') {
    try {
        if ($isSecure) {
            $unionTrace = 'SELECT name, category, price FROM products WHERE visibility = 1 AND name LIKE ? ORDER BY id';
            $stmt = lab_db()->prepare($unionTrace);
            $stmt->execute(['%'.$unionInput.'%']);
        } else {
            $unionTrace = "SELECT name, category, price FROM products WHERE visibility = 1 AND name LIKE '%$unionInput%' ORDER BY id";
            $stmt = lab_db()->query($unionTrace);
        }
        $unionRows = $stmt->fetchAll();
    } catch (Throwable $e) {
        $unionError = $isSecure ? 'The protected query rejected the input as plain search data.' : $e->getMessage();
    }
}

if ($exercise === 'error') {
    try {
        if ($isSecure) {
            $errorTrace = 'SELECT name FROM products WHERE visibility = 1 AND name LIKE ? ORDER BY id';
            $stmt = lab_db()->prepare($errorTrace);
            $stmt->execute(['%'.$errorInput.'%']);
        } else {
            $errorTrace = "SELECT name FROM products WHERE visibility = 1 AND name LIKE '%$errorInput%' ORDER BY id";
            $stmt = lab_db()->query($errorTrace);
        }
        $errorRows = $stmt->fetchAll();
        $errorState = 'Query completed without a database error.';
    } catch (Throwable $e) {
        $errorState = 'Database query error.';
        $errorDetails = $isSecure ? 'The protected build keeps database details out of the response.' : $e->getMessage();
    }
}

if ($exercise === 'boolean') {
    try {
        if ($isSecure) {
            $booleanTrace = 'SELECT COUNT(*) FROM products WHERE visibility = 1 AND name LIKE ?';
            $stmt = lab_db()->prepare($booleanTrace);
            $stmt->execute(['%'.$booleanInput.'%']);
        } else {
            $booleanTrace = "SELECT COUNT(*) FROM products WHERE visibility = 1 AND name LIKE '%$booleanInput%'";
            $stmt = lab_db()->query($booleanTrace);
        }
        $booleanState = ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        $booleanError = $isSecure ? 'The protected query handled the input as data.' : $e->getMessage();
    }
}

if ($exercise === 'time') {
    $startedAt = microtime(true);
    try {
        if ($isSecure) {
            $timeTrace = 'SELECT COUNT(*) FROM products WHERE visibility = 1 AND name LIKE ?';
            $stmt = lab_db()->prepare($timeTrace);
            $stmt->execute(['%'.$timeInput.'%']);
        } else {
            $timeTrace = "SELECT COUNT(*) FROM products WHERE visibility = 1 AND name LIKE '%$timeInput%'";
            $stmt = lab_db()->query($timeTrace);
        }
        $stmt->fetchColumn();
    } catch (Throwable $e) {
        $timeError = $isSecure ? 'The protected query handled the input as data.' : $e->getMessage();
    }
    $timeMs = (int)round((microtime(true) - $startedAt) * 1000);
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CYBERMART / <?=h($label)?> SQL Injection Lab</title>
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
        <?php if (false): ?>
            <form class="header-logout-form" action="logout.php" method="post" data-preserve-scroll="false">
                <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
                <button class="header-logout" type="submit">Déconnexion</button>
            </form>
        <?php endif; ?>
    </div>
</header>

<section class="dashboard-hero labs-hero">
    <p class="eyebrow">SQL INJECTION EXERCISES</p>
    <div class="dashboard-heading">
        <div>
            <h1>Five attacks.<br><span>One build at a time.</span></h1>
            <p>Everything is on this page: run an attack, inspect its returned data or response, then use the same payload in the other build.</p>
        </div>
    </div>
    <div class="tabs">
        <a href="../vulnerable/" class="<?=!$isSecure ? 'active danger' : ''?>">01 / Vulnerable: 5 attacks</a>
        <a href="../secure/" class="<?=$isSecure ? 'active safe' : ''?>">02 / Protected: 5 attacks</a>
    </div>
</section>

<section class="exercise-grid">
    <article class="panel exercise-card">
        <span class="exercise-number">01</span>
        <p class="eyebrow">AUTHENTICATION BYPASS</p>
        <h2>Login condition</h2>
        <p class="muted">Paste this in <strong>Username</strong>; enter any value for Password. The returned account appears below.</p>
        <div class="payload-guide">
            <div class="payload-heading"><span>PAYLOAD TO TRY</span><button type="button" class="copy-payload" data-copy="' OR 1=1 -- ">Copy</button></div>
            <code>' OR 1=1 -- <span class="visible-space">&#9251;</span></code>
            <p>The final space is required after <code>--</code> in MySQL.</p>
        </div>
        <div class="sql-explainer">
            <span>VULNERABLE SQL BECOMES</span>
            <pre>SELECT username, role FROM demo_users
WHERE username = '' OR 1=1 -- ' AND demo_password = 'anything'
LIMIT 1</pre>
            <p><strong>Result:</strong> <code>--</code> comments out the password check, so the condition is always true.</p>
        </div>
        <form method="post" class="exercise-form">
            <input type="hidden" name="action" value="auth">
            <label>Username<input name="username" value="<?=h($authAttempted ? $authUsername : '')?>" placeholder="Controlled lab input" autocomplete="off"></label>
            <label>Password<input type="password" name="password" placeholder="Any value for this test" autocomplete="off"></label>
            <button type="submit">Run login query <span>&rarr;</span></button>
        </form>
        <?php if ($authAttempted): ?>
            <?php if ($authError): ?><div class="notice bad"><?=h($authError)?></div>
            <?php elseif ($authResult): ?><div class="notice ok">ACCESS GRANTED &middot; <?=h($authResult['username'])?> / <?=h($authResult['role'])?></div>
            <?php else: ?><div class="notice bad">ACCESS DENIED &middot; No account returned</div><?php endif; ?>
            <pre class="lab-trace"><?=h($authTrace ?? '')?><?php if ($isSecure): ?>

Bound values: <?=h(json_encode(['username' => $authUsername, 'password' => $authPassword], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE))?><?php endif; ?></pre>
        <?php endif; ?>
    </article>

    <article class="panel exercise-card">
        <span class="exercise-number">02</span>
        <p class="eyebrow">UNION-BASED SQL INJECTION</p>
        <h2>Catalog union</h2>
        <p class="muted">Paste the payload below into Catalog input. It uses three columns because the catalog query returns three columns.</p>
        <div class="payload-guide">
            <div class="payload-heading"><span>PAYLOAD TO TRY</span><button type="button" class="copy-payload" data-copy="' UNION SELECT username, role, 0 FROM demo_users -- ">Copy</button></div>
            <code>' UNION SELECT username, role, 0 FROM demo_users -- <span class="visible-space">&#9251;</span></code>
        </div>
        <div class="sql-explainer">
            <span>VULNERABLE SQL BECOMES</span>
            <pre>SELECT name, category, price FROM products
WHERE visibility = 1 AND name LIKE '%'
UNION SELECT username, role, 0 FROM demo_users -- %'
ORDER BY id</pre>
            <p><strong>Result:</strong> the two fictitious users are returned as catalog rows. The protected page searches for the payload as text.</p>
        </div>
        <form method="get" class="exercise-form">
            <input type="hidden" name="exercise" value="union">
            <label>Catalog input<input name="union_q" value="<?=h($exercise === 'union' ? $unionInput : '')?>" placeholder="Controlled lab input" autocomplete="off"></label>
            <button type="submit">Run catalog query <span>&rarr;</span></button>
        </form>
        <?php if ($exercise === 'union'): ?>
            <?php if ($unionError): ?><div class="notice bad"><?=h($unionError)?></div><?php else: ?>
                <p class="exercise-result">Returned rows: <strong><?=count($unionRows)?></strong></p>
                <div class="mini-results">
                    <?php foreach ($unionRows as $row): ?><div><strong><?=h($row['name'])?></strong><span><?=h($row['category'])?> &middot; <?=h(number_format((float)$row['price'], 2))?></span></div><?php endforeach; ?>
                    <?php if (!$unionRows): ?><span class="muted">No matching rows.</span><?php endif; ?>
                </div>
            <?php endif; ?>
            <pre class="lab-trace"><?=h($unionTrace ?? '')?><?php if ($isSecure): ?>

Bound value: <?=h('%'.$unionInput.'%')?><?php endif; ?></pre>
        <?php endif; ?>
    </article>

    <article class="panel exercise-card">
        <span class="exercise-number">03</span>
        <p class="eyebrow">ERROR-GUIDED UNION INJECTION</p>
        <h2>Error, then union</h2>
        <p class="muted">The quote proves that input reaches the SQL syntax. Then try the compatible UNION payload to return a value as data.</p>
        <div class="payload-guide">
            <div class="payload-heading"><span>BASIC SYNTAX ERROR</span><button type="button" class="copy-payload" data-copy="'">Copy</button></div>
            <code>'</code>
            <div class="payload-heading payload-heading-secondary"><span>UNION: DATABASE NAME</span><button type="button" class="copy-payload" data-copy="%' UNION SELECT DATABASE() -- ">Copy</button></div>
            <code class="long-payload">%' UNION SELECT DATABASE() -- <span class="visible-space">&#9251;</span></code>
        </div>
        <div class="sql-explainer">
            <span>WHAT THE UNION PAYLOAD DOES IN VULNERABLE SQL</span>
            <pre>SELECT name FROM products
WHERE visibility = 1 AND name LIKE '%%'
UNION SELECT DATABASE() -- %'</pre>
            <p><strong>Result:</strong> the vulnerable page returns <code>sqli_demo</code> as a row. The syntax error first shows where SQL is broken; the compatible one-column UNION then reads a safe fictitious value. The protected build treats it as text.</p>
        </div>
        <form method="get" class="exercise-form">
            <input type="hidden" name="exercise" value="error">
            <label>Diagnostic input<input name="error_q" value="<?=h($exercise === 'error' ? $errorInput : '')?>" placeholder="Controlled lab input" autocomplete="off"></label>
            <button type="submit">Run diagnostic query <span>&rarr;</span></button>
        </form>
        <?php if ($exercise === 'error'): ?>
            <div class="notice <?=$errorDetails ? 'bad' : 'ok'?>"><?=h($errorState ?? '')?></div>
            <?php if ($errorDetails): ?><pre class="lab-error"><?=h($errorDetails)?></pre><?php endif; ?>
            <?php if (!$errorDetails): ?>
                <p class="exercise-result">Returned rows: <strong><?=count($errorRows)?></strong></p>
                <div class="mini-results">
                    <?php foreach ($errorRows as $row): ?><div><strong><?=h($row['name'])?></strong></div><?php endforeach; ?>
                    <?php if (!$errorRows): ?><span class="muted">No matching rows.</span><?php endif; ?>
                </div>
            <?php endif; ?>
            <pre class="lab-trace"><?=h($errorTrace ?? '')?><?php if ($isSecure): ?>

Bound value: <?=h('%'.$errorInput.'%')?><?php endif; ?></pre>
        <?php endif; ?>
    </article>

    <article class="panel exercise-card">
        <span class="exercise-number">04</span>
        <p class="eyebrow">BOOLEAN-BASED BLIND SQL INJECTION</p>
        <h2>True or false</h2>
        <p class="muted">Try each payload in Condition input. The vulnerable page reveals only whether the hidden condition is true or false.</p>
        <div class="payload-guide">
            <div class="payload-heading"><span>TRUE: DOES ADMIN EXIST?</span><button type="button" class="copy-payload" data-copy="hkzzzz' OR (SELECT COUNT(*) FROM demo_users WHERE username='admin')=1 -- ">Copy</button></div>
            <code class="long-payload">hkzzzz' OR (SELECT COUNT(*) FROM demo_users WHERE username='admin')=1 -- <span class="visible-space">&#9251;</span></code>
            <div class="payload-heading payload-heading-secondary"><span>FALSE: DOES GHOST EXIST?</span><button type="button" class="copy-payload" data-copy="hkzzzz' OR (SELECT COUNT(*) FROM demo_users WHERE username='ghost')=1 -- ">Copy</button></div>
            <code class="long-payload">hkzzzz' OR (SELECT COUNT(*) FROM demo_users WHERE username='ghost')=1 -- <span class="visible-space">&#9251;</span></code>
        </div>
        <div class="sql-explainer">
            <span>VULNERABLE SQL BECOMES</span>
            <pre>-- TRUE:  ... WHERE visibility = 1 AND name LIKE '%hkzzzz'
--        OR (SELECT COUNT(*) FROM demo_users WHERE username='admin')=1 -- %'

-- FALSE: ... WHERE visibility = 1 AND name LIKE '%hkzzzz'
--        OR (SELECT COUNT(*) FROM demo_users WHERE username='ghost')=1 -- %'</pre>
            <p><strong>Result:</strong> <code>admin</code> exists in this fictitious lab, so the first response is TRUE. <code>ghost</code> does not, so the second is FALSE. The protected build binds the whole input as text.</p>
        </div>
        <form method="get" class="exercise-form">
            <input type="hidden" name="exercise" value="boolean">
            <label>Condition input<input name="boolean_q" value="<?=h($exercise === 'boolean' ? $booleanInput : '')?>" placeholder="Controlled lab input" autocomplete="off"></label>
            <button type="submit">Check response <span>&rarr;</span></button>
        </form>
        <?php if ($exercise === 'boolean'): ?>
            <?php if ($booleanError): ?><div class="notice bad"><?=h($booleanError)?></div><?php else: ?><div class="blind-state <?=$booleanState ? 'true' : 'false'?>"><?=$booleanState ? 'MATCH FOUND / TRUE' : 'NO MATCH / FALSE'?></div><?php endif; ?>
            <pre class="lab-trace"><?=h($booleanTrace ?? '')?><?php if ($isSecure): ?>

Bound value: <?=h('%'.$booleanInput.'%')?><?php endif; ?></pre>
        <?php endif; ?>
    </article>

    <article class="panel exercise-card">
        <span class="exercise-number">05</span>
        <p class="eyebrow">TIME-BASED BLIND SQL INJECTION</p>
        <h2>Response delay</h2>
        <p class="muted">Paste this short, local-only delay test into Timing input. The lab caps supported database servers at about two seconds.</p>
        <div class="payload-guide">
            <div class="payload-heading"><span>PAYLOAD TO TRY</span><button type="button" class="copy-payload" data-copy="zzzz' UNION SELECT IF(SUBSTRING(DATABASE(),1,1)='a',SLEEP(1),0) --  ">Copy</button></div>
            <code>zzzz' UNION SELECT IF(SUBSTRING(DATABASE(),1,1)='a',SLEEP(1),0) -- <span class="visible-space">&#9251;</span></code>
            <p>The additional local result row calls <code>SLEEP(1)</code> once, producing an approximately one-second delay.</p>
        </div>
        <div class="sql-explainer">
            <span>VULNERABLE SQL BECOMES</span>
            <pre>SELECT COUNT(*) FROM products
WHERE visibility = 1 AND name LIKE '%%'
UNION SELECT SLEEP(1) -- %'</pre>
            <p><strong>Result:</strong> the response is delayed by roughly one second. The protected page treats <code>SLEEP(1)</code> as ordinary search text.</p>
        </div>
        <form method="get" class="exercise-form">
            <input type="hidden" name="exercise" value="time">
            <label>Timing input<input name="time_q" value="<?=h($exercise === 'time' ? $timeInput : '')?>" placeholder="Controlled lab input" autocomplete="off"></label>
            <button type="submit">Measure response <span>&rarr;</span></button>
        </form>
        <?php if ($exercise === 'time'): ?>
            <?php if ($timeError): ?><div class="notice bad"><?=h($timeError)?></div><?php endif; ?>
            <div class="time-result"><strong><?=h((string)$timeMs)?> ms</strong><span>Measured server-side query time</span></div>
            <pre class="lab-trace"><?=h($timeTrace ?? '')?><?php if ($isSecure): ?>

Bound value: <?=h('%'.$timeInput.'%')?><?php endif; ?></pre>
        <?php endif; ?>
    </article>

</section>

<footer>CYBERMART SECURITY LAB <span>SQL Injection / Detection / Mitigation</span></footer>
</main>
</body>
</html>
