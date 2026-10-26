<?php
require_once __DIR__ . '/_init.php';
require_admin();
$o = order_get((int) qin('id'));
$file = $o && $o['proof_file'] ? ROOT . '/storage/proofs/' . basename($o['proof_file']) : '';
if (!$file || !is_file($file)) { http_response_code(404); exit('Receipt not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) { http_response_code(415); exit; }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="receipt-' . $o['ref'] . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
readfile($file);
