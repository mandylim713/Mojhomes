<?php
function flw_enabled(): bool
{
    return setting('pay_flutterwave') === '1' && setting('flw_secret_key') !== '';
}

function flw_api(string $method, string $path, ?array $body = null): array
{
    $base = rtrim((string) (CONFIG['flutterwave_api'] ?? 'https://api.flutterwave.com/v3'), '/');
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 40,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . setting('flw_secret_key'),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false) {
        error_log('Flutterwave unreachable: ' . $err);
        throw new RuntimeException('We could not reach the payment gateway. Please try again.');
    }
    $j = json_decode((string) $res, true);
    if (!is_array($j)) {
        error_log("Flutterwave invalid response ($code): $res");
        throw new RuntimeException('The payment gateway returned an unexpected response. Please try again.');
    }
    return $j;
}

/** Returns the hosted checkout link for an order. */
function flw_checkout_link(array $o): string
{
    if (!valid_email((string) $o['email'])) throw new RuntimeException('An email address is required for online payment.');
    $txref = $o['ref'] . '-' . strtoupper(base_convert((string) time(), 10, 36));
    $r = flw_api('POST', '/payments', [
        'tx_ref'          => $txref,
        'amount'          => (int) $o['total'],
        'currency'        => 'NGN',
        'redirect_url'    => abs_url('pay/callback.php'),
        'payment_options' => 'card,banktransfer,ussd',
        'customer'        => ['email' => $o['email'], 'phonenumber' => $o['phone'], 'name' => $o['customer_name']],
        'customizations'  => ['title' => setting('business_name', 'Mojhometouch'), 'description' => 'Order ' . $o['ref'], 'logo' => abs_url('assets/img/logo.png')],
        'meta'            => ['order_ref' => $o['ref']],
    ]);
    if (($r['status'] ?? '') !== 'success' || empty($r['data']['link'])) {
        error_log('Flutterwave init failed: ' . json_encode($r));
        throw new RuntimeException('We could not start the online payment. Please try again or choose another payment method.');
    }
    q('UPDATE orders SET payment_ref = ?, updated_at = ? WHERE id = ?', [$txref, now(), $o['id']]);
    return (string) $r['data']['link'];
}

function flw_order_ref(string $txref): string
{
    return preg_match('~^(MOJ\d{6}-[A-Z0-9]{4})~', $txref, $m) ? $m[1] : '';
}

/**
 * Verify a transaction with Flutterwave (never trust redirect/webhook data alone).
 * @return array{0:bool,1:?array,2:string}
 */
function flw_confirm(string $transactionId): array
{
    if (!preg_match('~^\d{1,20}$~', $transactionId)) return [false, null, 'Invalid transaction reference.'];
    $r = flw_api('GET', '/transactions/' . $transactionId . '/verify');
    $d = $r['data'] ?? [];
    $o = ($ref = flw_order_ref((string) ($d['tx_ref'] ?? ''))) ? order_by_ref($ref) : null;
    if (!$o) return [false, null, 'We could not match this payment to an order. Please contact us.'];
    if ($o['payment_status'] === 'paid') return [true, $o, 'Payment confirmed.'];
    $ok = ($r['status'] ?? '') === 'success'
        && ($d['status'] ?? '') === 'successful'
        && strtoupper((string) ($d['currency'] ?? '')) === 'NGN'
        && (float) ($d['amount'] ?? 0) >= (float) $o['total'];
    if ($ok) {
        order_set_payment($o, 'paid', 'Flutterwave', 'Payment of ' . money($d['amount']) . ' received online.', (string) ($d['flw_ref'] ?? $transactionId));
        return [true, order_get((int) $o['id']), 'Payment successful — thank you!'];
    }
    if (($d['status'] ?? '') === 'failed') order_event((int) $o['id'], null, 'An online payment attempt failed.', 'Flutterwave');
    return [false, $o, 'Your payment was not completed. You can try again or choose another method.'];
}
