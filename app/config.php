<?php
// LAB ONLY. Use isolated VM networking; do not deploy this project publicly.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (($_SERVER['HTTPS'] ?? '') === 'on'),
    ]);
    session_start();
}

/**
 * Return a request value only when it is a scalar string. This keeps malformed
 * inputs such as ?q[]=value out of the SQL demonstration and PHP warnings.
 */
function request_string(array $source, string $key): string {
    $value = $source[$key] ?? '';
    return is_string($value) ? $value : '';
}

function lab_session_key(string $mode): string {
    return 'sqli_lab_'.$mode.'_user';
}

function lab_current_user(string $mode): ?array {
    $user = $_SESSION[lab_session_key($mode)] ?? null;

    if (!is_array($user) || !is_string($user['username'] ?? null) || !is_string($user['role'] ?? null)) {
        return null;
    }

    return ['username' => $user['username'], 'role' => $user['role']];
}

function lab_sign_in(string $mode, array $user): void {
    session_regenerate_id(true);
    $_SESSION[lab_session_key($mode)] = [
        'username' => (string)$user['username'],
        'role' => (string)$user['role'],
    ];
}

function lab_sign_out(string $mode): void {
    unset($_SESSION[lab_session_key($mode)]);
}

function csrf_token(): string {
    if (!isset($_SESSION['sqli_lab_csrf']) || !is_string($_SESSION['sqli_lab_csrf'])) {
        $_SESSION['sqli_lab_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['sqli_lab_csrf'];
}

function valid_csrf_token(string $token): bool {
    return isset($_SESSION['sqli_lab_csrf'])
        && is_string($_SESSION['sqli_lab_csrf'])
        && hash_equals($_SESSION['sqli_lab_csrf'], $token);
}

function lab_pdo(string $username, string $password): PDO {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // This lab demonstrates a single injected SELECT only. Do not allow a
    // semicolon in the vulnerable query to turn into additional statements.
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    }

    $db = new PDO(
        'mysql:host=localhost;dbname=sqli_demo;charset=utf8mb4',
        $username,
        $password,
        $options
    );

    // Keep the time-based exercise short where the MySQL-family server supports
    // a per-session SELECT time limit. The MariaDB and MySQL variable names differ.
    foreach (['SET SESSION MAX_EXECUTION_TIME = 2000', 'SET SESSION max_statement_time = 2'] as $statement) {
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            // The unsupported variable is expected on the other database variant.
        }
    }

    return $db;
}

function lab_db(): PDO {
    return lab_pdo('sqli_lab', 'LabOnly_ChangeMe_2026!');
}

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
