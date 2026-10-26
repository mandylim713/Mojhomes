<?php
/** Flutterwave redirects the customer here after payment. We verify with the API before marking paid. */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$status = qin('status');
$txref = qin('tx_ref');
$txid = qin('transaction_id');
$o = ($r = flw_order_ref($txref)) ? order_by_ref($r) : null;

if ($status === 'cancelled' || $txid === '') {
    if ($o) {
        flash('error', 'Payment was cancelled. You can try again whenever you are ready.');
        redirect(order_url($o));
    }
    redirect(url());
}
try {
    [$ok, $order, $msg] = flw_confirm($txid);
} catch (RuntimeException $e) {
    [$ok, $order, $msg] = [false, $o, $e->getMessage()];
}
$order = $order ?: $o;
flash($ok ? 'success' : 'error', $msg);
redirect($order ? order_url($order) : url());
