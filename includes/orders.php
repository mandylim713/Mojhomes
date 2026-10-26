<?php
const ORDER_STATUSES = [
    'pending'    => 'Pending',
    'confirmed'  => 'Confirmed',
    'processing' => 'Processing',
    'ready'      => 'Ready for Pickup',
    'shipped'    => 'Out for Delivery',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
];

const PAYMENT_STATUSES = [
    'unpaid'   => 'Unpaid',
    'review'   => 'Awaiting Confirmation',
    'paid'     => 'Paid',
    'failed'   => 'Failed',
    'refunded' => 'Refunded',
];

const STATUS_MESSAGES = [
    'pending'    => 'Order received — our team will confirm it shortly.',
    'confirmed'  => 'Your order has been confirmed.',
    'processing' => 'Your order is being prepared.',
    'ready'      => 'Your order is ready for pickup at our Nsukka showroom. Please bring your order number.',
    'shipped'    => 'Your order is out for delivery.',
    'delivered'  => 'Your order has been delivered. Thank you for choosing Mojhometouch!',
    'cancelled'  => 'Your order has been cancelled.',
];

function payment_methods_available(): array
{
    $m = [];
    if (flw_enabled()) $m['flutterwave'] = ['Pay Online', 'Card, bank transfer or USSD — secured by Flutterwave', 'card'];
    if (setting('pay_bank') === '1' && setting('bank_account_number') !== '') $m['bank'] = ['Bank Transfer', 'Transfer to our company account, then upload your receipt', 'bank'];
    if (setting('pay_pod') === '1') $m['pod'] = ['Pay on Delivery / Pickup', 'Pay when your order is delivered or collected', 'cash'];
    return $m;
}

function payment_method_label(string $k): string
{
    return ['flutterwave' => 'Online (Flutterwave)', 'bank' => 'Bank Transfer', 'pod' => 'Pay on Delivery / Pickup'][$k] ?? ucfirst($k);
}

function delivery_zones(bool $activeOnly = true): array
{
    return q_all('SELECT * FROM delivery_zones' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort, id');
}

function order_new_ref(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $s = '';
        for ($i = 0; $i < 4; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
        $ref = 'MOJ' . date('ymd') . '-' . $s;
    } while (q_val('SELECT COUNT(*) FROM orders WHERE ref = ?', [$ref]));
    return $ref;
}

/**
 * Create an order from fresh cart lines. Stock is reserved inside a transaction.
 * @throws RuntimeException with a customer-friendly message
 */
function order_create(array $c, array $cart, array $zone, string $method): array
{
    $db = db();
    $db->beginTransaction();
    try {
        foreach ($cart['lines'] as $l) {
            [$tbl, $id] = $l['variant_id'] ? ['product_variants', $l['variant_id']] : ['products', $l['product_id']];
            $row = q_one("SELECT stock FROM $tbl WHERE id = ?", [$id]);
            if (!$row) throw new RuntimeException($l['name'] . ' is no longer available.');
            if ($row['stock'] !== null) {
                $st = q("UPDATE $tbl SET stock = stock - ? WHERE id = ? AND stock >= ?", [$l['qty'], $id, $l['qty']]);
                if ($st->rowCount() < 1) throw new RuntimeException('Sorry, there is not enough stock left for ' . $l['name'] . '. Please update your cart.');
            }
        }
        $fee = (int) $zone['fee'];
        $now = now();
        $oid = insert('orders', [
            'ref' => order_new_ref(),
            'token' => bin2hex(random_bytes(16)),
            'customer_name' => $c['name'], 'phone' => $c['phone'], 'email' => $c['email'] ?: null,
            'address' => $c['address'] ?: null, 'city' => $c['city'] ?: null, 'state' => $c['state'] ?: null,
            'zone_id' => (int) $zone['id'], 'zone_name' => $zone['name'], 'is_pickup' => (int) $zone['is_pickup'],
            'delivery_fee' => $fee, 'subtotal' => (int) $cart['subtotal'], 'total' => (int) $cart['subtotal'] + $fee,
            'payment_method' => $method, 'payment_status' => 'unpaid', 'status' => 'pending',
            'notes' => $c['notes'] ?: null, 'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ($cart['lines'] as $l) {
            insert('order_items', [
                'order_id' => $oid, 'product_id' => $l['product_id'], 'variant_id' => $l['variant_id'] ?: null,
                'name' => $l['name'], 'variant_name' => $l['variant_name'], 'option_label' => $l['option_label'],
                'option_value' => $l['option'] !== '' ? $l['option'] : null, 'image' => $l['image'],
                'unit_price' => $l['unit_price'], 'qty' => $l['qty'], 'line_total' => $l['line_total'],
            ]);
        }
        order_event($oid, 'pending', 'Order placed.', 'Customer');
        $db->commit();
        @file_put_contents(
            ROOT . '/storage/logs/orders.txt',
            sprintf("[%s] REF: %s | CUSTOMER: %s (%s) | TOTAL: ₦%s | METHOD: %s\n", $now, $oRef = order_get($oid)['ref'] ?? $oid, $c['name'], $c['phone'], number_format((int) $cart['subtotal'] + $fee), $method),
            FILE_APPEND | LOCK_EX
        );
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    return order_get($oid);
}

function order_get(int $id): ?array
{
    return q_one('SELECT * FROM orders WHERE id = ?', [$id]);
}

function order_by_ref(string $ref): ?array
{
    return q_one('SELECT * FROM orders WHERE ref = ?', [strtoupper(trim($ref))]);
}

function order_items(int $id): array
{
    return q_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$id]);
}

function order_events(int $id, bool $visibleOnly = false): array
{
    return q_all('SELECT * FROM order_events WHERE order_id = ?' . ($visibleOnly ? ' AND visible = 1' : '') . ' ORDER BY id', [$id]);
}

function order_event(int $id, ?string $status, string $note, string $actor = 'System', bool $visible = true): void
{
    insert('order_events', ['order_id' => $id, 'status' => $status, 'note' => $note, 'actor' => $actor, 'visible' => $visible ? 1 : 0, 'created_at' => now()]);
}

function order_url(array $o, bool $abs = false): string
{
    $p = 'order.php?ref=' . rawurlencode($o['ref']) . '&t=' . $o['token'];
    return $abs ? abs_url($p) : url($p);
}

function order_restock(array $o): void
{
    if ((int) $o['stock_restored']) return;
    foreach (order_items((int) $o['id']) as $it) {
        if ($it['variant_id']) q('UPDATE product_variants SET stock = stock + ? WHERE id = ? AND stock IS NOT NULL', [$it['qty'], $it['variant_id']]);
        elseif ($it['product_id']) q('UPDATE products SET stock = stock + ? WHERE id = ? AND stock IS NOT NULL', [$it['qty'], $it['product_id']]);
    }
    q('UPDATE orders SET stock_restored = 1 WHERE id = ?', [$o['id']]);
}

function order_set_status(array $o, string $status, string $note, string $actor, bool $notify = true): void
{
    if (!isset(ORDER_STATUSES[$status])) throw new RuntimeException('Unknown status.');
    if ($o['status'] === 'cancelled') throw new RuntimeException('This order was cancelled and can no longer be changed.');
    if ($o['status'] === $status && $note === '') return;
    q('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $o['id']]);
    if ($status === 'cancelled') order_restock($o);
    $msg = $note !== '' ? $note : STATUS_MESSAGES[$status];
    order_event((int) $o['id'], $status, $msg, $actor);
    if ($notify) notify_status(order_get((int) $o['id']), $msg);
}

function order_set_payment(array $o, string $pstatus, string $actor, string $note = '', ?string $payRef = null): void
{
    if (!isset(PAYMENT_STATUSES[$pstatus])) throw new RuntimeException('Unknown payment status.');
    $paidAt = $pstatus === 'paid' ? ($o['paid_at'] ?: now()) : ($pstatus === 'refunded' ? $o['paid_at'] : null);
    q('UPDATE orders SET payment_status = ?, paid_at = ?, payment_ref = COALESCE(?, payment_ref), updated_at = ? WHERE id = ?',
        [$pstatus, $paidAt, $payRef, now(), $o['id']]);
    $default = [
        'paid' => 'Payment received — thank you!', 'review' => 'Payment receipt received — we are confirming it.',
        'failed' => 'Payment could not be confirmed. Please contact us.', 'refunded' => 'Your payment has been refunded.',
        'unpaid' => 'Payment status reset to unpaid.',
    ][$pstatus];
    order_event((int) $o['id'], null, $note !== '' ? $note : $default, $actor);
    $fresh = order_get((int) $o['id']);
    if ($pstatus === 'paid' && $fresh['status'] === 'pending') {
        order_set_status($fresh, 'confirmed', 'Payment received and your order is confirmed.', $actor);
    } elseif ($pstatus === 'paid' || $pstatus === 'refunded' || $pstatus === 'failed') {
        notify_status($fresh, $note !== '' ? $note : $default);
    }
}

/** Progress steps for the customer tracker. */
function order_steps(array $o): array
{
    $last = $o['is_pickup'] ? ['ready', 'Ready for Pickup'] : ['shipped', 'Out for Delivery'];
    $steps = [['pending', 'Order Placed'], ['confirmed', 'Confirmed'], ['processing', 'Processing'], $last, ['delivered', $o['is_pickup'] ? 'Collected' : 'Delivered']];
    $rank = ['pending' => 0, 'confirmed' => 1, 'processing' => 2, 'ready' => 3, 'shipped' => 3, 'delivered' => 4];
    $cur = $rank[$o['status']] ?? 0;
    $out = [];
    foreach ($steps as $i => [$k, $label]) $out[] = ['label' => $label, 'done' => $i < $cur || ($i === $cur && $o['status'] === 'delivered'), 'current' => $i === $cur && $o['status'] !== 'delivered'];
    return $out;
}

function badge_status(string $s): string
{
    return '<span class="badge st-' . e($s) . '">' . e(ORDER_STATUSES[$s] ?? $s) . '</span>';
}

function badge_pay(string $s): string
{
    return '<span class="badge pay-' . e($s) . '">' . e(PAYMENT_STATUSES[$s] ?? $s) . '</span>';
}
