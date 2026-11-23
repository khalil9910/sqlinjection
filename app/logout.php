<?php
require_once __DIR__.'/config.php';

if (!isset($mode) || !in_array($mode, ['vulnerable', 'secure'], true)) {
    http_response_code(404);
    exit('Not found');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!valid_csrf_token(request_string($_POST, 'csrf_token'))) {
    http_response_code(403);
    exit('Invalid logout request');
}

lab_sign_out($mode);
header('Location: index.php');
exit;
