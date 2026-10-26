<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$o = order_by_ref(qin('ref'));
if (!$o || !hash_equals($o['token'], qin('t'))) not_found();
if ($o['payment_status'] === 'paid' || $o['status'] === 'cancelled') redirect(order_url($o));
try {
    if (!flw_enabled()) throw new RuntimeException('Online payment is not available right now.');
    if (throttled('paystart', 8, 600)) throw new RuntimeException('Too many payment attempts. Please wait a few minutes.');
    redirect(flw_checkout_link($o));
} catch (RuntimeException $e) {
    flash('error', $e->getMessage());
    redirect(order_url($o));
}
