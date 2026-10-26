<?php
// Health check for Render / uptime monitors
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain');
header('Cache-Control: no-store');
try {
    q_val('SELECT 1');
    echo 'ok';
} catch (Throwable $e) {
    http_response_code(503);
    echo 'db error';
}
