<?php
require_once __DIR__ . '/_init.php';
require_admin();

$fields = [
    'business' => ['business_name', 'tagline', 'phone', 'whatsapp', 'email', 'notify_email', 'address', 'map_query', 'hours', 'announcement'],
    'social' => ['facebook', 'instagram', 'tiktok'],
    'payments' => ['bank_name', 'bank_account_name', 'bank_account_number', 'flw_public_key', 'flw_secret_key', 'flw_secret_hash'],
    'store' => ['low_stock'],
    'email' => ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure', 'smtp_from'],
];
$checks = ['pay_bank', 'pay_pod', 'pay_flutterwave'];
$secret = ['flw_secret_key', 'flw_secret_hash', 'smtp_pass'];

if (is_post()) {
    csrf_check();
    $errs = [];
    foreach (array_merge(...array_values($fields)) as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        $v = mb_substr(pin($k), 0, 500);
        if (in_array($k, $secret, true) && $v === '••••••••') continue; // unchanged
        if (in_array($k, ['email', 'notify_email'], true) && $v !== '' && !valid_email($v)) { $errs[] = 'Please enter a valid email for ' . str_replace('_', ' ', $k) . '.'; continue; }
        if (in_array($k, ['facebook', 'instagram', 'tiktok'], true) && $v !== '' && !preg_match('~^https?://~', $v)) $v = 'https://' . ltrim($v, '/');
        if ($k === 'whatsapp') $v = intl_phone($v);
        if ($k === 'low_stock') $v = (string) max(0, (int) $v);
        set_setting($k, $v);
    }
    foreach ($checks as $k) if (isset($_POST['_has_' . $k])) set_setting($k, pin($k) === '1' ? '1' : '0');
    if (setting('pay_flutterwave') === '1' && setting('flw_secret_key') === '') $errs[] = 'Online payment was switched on but no Flutterwave secret key is set — it stays hidden at checkout until you add one.';
    if (pin('test_mail') === '1') {
        $to = setting('notify_email') ?: setting('email');
        if (!$to) $errs[] = 'Add an order notification email first, then send a test.';
        elseif (send_mail($to, 'Test email from your website', '<p>Email sending works. New orders and quote requests will arrive at this address.</p>')) flash('success', 'Test email sent to ' . $to . '.');
        else $errs[] = 'Test email failed — check the SMTP details. See storage/logs/mail.log for the reason.';
    }
    foreach ($errs as $er) flash('error', $er);
    flash('success', 'Settings saved.');
    redirect(url('admin/settings.php') . '#' . pin('_section', 'business'));
}

$s = fn($k) => e(setting($k));
$sec = fn($k) => setting($k) !== '' ? '••••••••' : '';
$box = fn($k, $label) => '<input type="hidden" name="_has_' . $k . '" value="1"><label class="chk"><input type="checkbox" name="' . $k . '" value="1"' . (setting($k) === '1' ? ' checked' : '') . '> ' . $label . '</label>';
admin_header('Settings', 'settings');
?>
<div class="settings">
<form method="post" class="card" id="business"><?= csrf_field() ?><input type="hidden" name="_section" value="business">
  <div class="card-h"><h2>Business details</h2></div>
  <div class="form-grid">
    <label class="f"><span>Business name</span><input name="business_name" value="<?= $s('business_name') ?>"></label>
    <label class="f"><span>Tagline</span><input name="tagline" value="<?= $s('tagline') ?>"></label>
    <label class="f"><span>Phone (shown on site)</span><input name="phone" value="<?= $s('phone') ?>"></label>
    <label class="f"><span>WhatsApp number <small>international, e.g. 2347031041249</small></span><input name="whatsapp" value="<?= $s('whatsapp') ?>"></label>
    <label class="f"><span>Public email <small>shown on site, used as reply-to</small></span><input name="email" type="email" value="<?= $s('email') ?>"></label>
    <label class="f"><span>Order notification email <small>where new orders &amp; quotes are sent</small></span><input name="notify_email" type="email" value="<?= $s('notify_email') ?>"></label>
    <label class="f"><span>Address</span><input name="address" value="<?= $s('address') ?>"></label>
    <label class="f"><span>Map search <small>for the contact page map</small></span><input name="map_query" value="<?= $s('map_query') ?>"></label>
    <label class="f"><span>Opening hours</span><input name="hours" value="<?= $s('hours') ?>"></label>
    <label class="f"><span>Announcement bar <small>blank to hide</small></span><input name="announcement" value="<?= $s('announcement') ?>"></label>
  </div>
  <button class="btn btn-primary btn-sm" type="submit">Save business details</button>
</form>

<form method="post" class="card" id="payments"><?= csrf_field() ?><input type="hidden" name="_section" value="payments">
  <div class="card-h"><h2>Payments</h2></div>
  <h3 class="sub">Bank transfer</h3>
  <?= $box('pay_bank', 'Offer bank transfer at checkout (customer uploads receipt, you confirm)') ?>
  <div class="form-grid">
    <label class="f"><span>Bank name</span><input name="bank_name" value="<?= $s('bank_name') ?>" placeholder="e.g. Zenith Bank"></label>
    <label class="f"><span>Account name</span><input name="bank_account_name" value="<?= $s('bank_account_name') ?>"></label>
    <label class="f"><span>Account number</span><input name="bank_account_number" value="<?= $s('bank_account_number') ?>" inputmode="numeric" maxlength="20"></label>
  </div>
  <p class="hint">Bank transfer only appears at checkout once an account number is saved.</p>

  <h3 class="sub">Pay on delivery / pickup</h3>
  <?= $box('pay_pod', 'Offer pay on delivery / pickup') ?>

  <h3 class="sub">Flutterwave (card, transfer, USSD)</h3>
  <?= $box('pay_flutterwave', 'Offer online payment with Flutterwave') ?>
  <div class="form-grid">
    <label class="f"><span>Public key</span><input name="flw_public_key" value="<?= $s('flw_public_key') ?>" placeholder="FLWPUBK-…"></label>
    <label class="f"><span>Secret key</span><input name="flw_secret_key" value="<?= $sec('flw_secret_key') ?>" placeholder="FLWSECK-…" autocomplete="off"></label>
    <label class="f"><span>Webhook secret hash <small>any long random text — paste the same in Flutterwave</small></span><input name="flw_secret_hash" value="<?= $sec('flw_secret_hash') ?>" autocomplete="off"></label>
    <div class="f"><span>Webhook URL <small>paste into Flutterwave → Settings → Webhooks</small></span><code class="copybox"><?= e(abs_url('pay/webhook.php')) ?></code></div>
  </div>
  <p class="hint">Get keys from your Flutterwave dashboard → Settings → API Keys. Use live keys on the live site. Payments are always verified with Flutterwave before an order is marked paid.</p>
  <button class="btn btn-primary btn-sm" type="submit">Save payment settings</button>
</form>

<form method="post" class="card" id="email"><?= csrf_field() ?><input type="hidden" name="_section" value="email">
  <div class="card-h"><h2>Email sending (SMTP)</h2><?= !empty(CONFIG['smtp']['host']) ? '<span class="badge st-delivered">Set by server environment</span>' : '' ?></div>
  <p class="hint">Needed on Render (it has no mail server). Use your domain email's SMTP details (cPanel → Email Accounts → Connect Devices), Zoho, Brevo, or Gmail with an app password. Leave blank on cPanel to use the server's built-in mail.</p>
  <div class="form-grid" style="margin-top:12px">
    <label class="f"><span>SMTP host</span><input name="smtp_host" value="<?= $s('smtp_host') ?>" placeholder="e.g. mail.yourdomain.com"></label>
    <label class="f"><span>Port</span><input name="smtp_port" value="<?= $s('smtp_port') ?>" placeholder="587" inputmode="numeric"></label>
    <label class="f"><span>Username</span><input name="smtp_user" value="<?= $s('smtp_user') ?>" autocomplete="off"></label>
    <label class="f"><span>Password</span><input name="smtp_pass" type="password" value="<?= $sec('smtp_pass') ?>" autocomplete="new-password"></label>
    <label class="f"><span>Security</span><select name="smtp_secure"><?php foreach (['tls' => 'TLS / STARTTLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>"<?= setting('smtp_secure', 'tls') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <label class="f"><span>From address</span><input name="smtp_from" type="email" value="<?= $s('smtp_from') ?>" placeholder="orders@yourdomain.com"></label>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-primary btn-sm" type="submit">Save email settings</button><button class="btn btn-line btn-sm" type="submit" name="test_mail" value="1">Save &amp; send test email</button></div>
</form>

<form method="post" class="card" id="social"><?= csrf_field() ?><input type="hidden" name="_section" value="social">
  <div class="card-h"><h2>Social links &amp; store</h2></div>
  <div class="form-grid">
    <label class="f"><span>Facebook URL</span><input name="facebook" value="<?= $s('facebook') ?>"></label>
    <label class="f"><span>Instagram URL</span><input name="instagram" value="<?= $s('instagram') ?>"></label>
    <label class="f"><span>TikTok URL</span><input name="tiktok" value="<?= $s('tiktok') ?>"></label>
    <label class="f"><span>Low-stock warning at</span><input name="low_stock" value="<?= $s('low_stock') ?>" inputmode="numeric"></label>
  </div>
  <button class="btn btn-primary btn-sm" type="submit">Save</button>
</form>

<div class="card">
  <div class="card-h"><h2>System</h2></div>
  <div class="kv">
    <div><span>Database</span><b><?= is_mysql() ? 'MySQL' : 'SQLite (storage/mojhometouch.sqlite)' ?></b></div>
    <div><span>Site address</span><b><?= e(abs_url()) ?></b></div>
    <div><span>Sitemap</span><b><a class="link" href="<?= e(url('sitemap.php')) ?>" target="_blank"><?= e(abs_url('sitemap.php')) ?></a></b></div>
    <div><span>PHP</span><b><?= PHP_VERSION ?></b></div>
    <div><span>Uploads folder writable</span><b><?= is_writable(ROOT . '/uploads') ? 'Yes' : '<span class="neg">No — set folder permission to 755/775</span>' ?></b></div>
    <div><span>Storage folder writable</span><b><?= is_writable(ROOT . '/storage') ? 'Yes' : '<span class="neg">No — set folder permission to 755/775</span>' ?></b></div>
  </div>
  <?php if (!is_mysql()): ?><p class="hint" style="margin-top:12px">Back up regularly by downloading <code>storage/mojhometouch.sqlite</code> from cPanel File Manager.</p><?php endif; ?>
</div>
</div>
<?php admin_footer();
