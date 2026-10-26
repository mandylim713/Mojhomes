<?php
/**
 * Flutterwave webhook. In your Flutterwave dashboard set:
 *   URL:         https://YOUR-DOMAIN/pay/webhook.php
 *   Secret hash: the same value as "Webhook secret hash" in Admin → Settings
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$hash = setting('flw_secret_hash');
$sig = $_SERVER['HTTP_VERIF_HASH'] ?? '';
if ($hash === '' || !is_string($sig) || !hash_equals($hash, $sig)) {
    http_response_code(401);
    exit('unauthorised');
}
$body = json_decode((string) file_get_contents('php://input'), true);
$id = (string) ($body['data']['id'] ?? ($body['id'] ?? ''));
if ($id !== '') {
    try {
        flw_confirm($id);
    } catch (Throwable $e) {
        error_log('Webhook verify failed: ' . $e->getMessage());
        http_response_code(500);
        exit('retry');
    }
}
http_response_code(200);
echo 'ok';
