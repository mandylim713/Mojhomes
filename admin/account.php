<?php
require_once __DIR__ . '/_init.php';
$me = require_admin();

if (is_post()) {
    csrf_check();
    $act = pin('action');
    try {
        if ($act === 'profile') {
            $full = q_one('SELECT * FROM admins WHERE id = ?', [$me['id']]);
            if (!password_verify(pin('current'), $full['password_hash'])) throw new RuntimeException('Your current password is incorrect.');
            $name = mb_substr(pin('name'), 0, 120);
            $email = strtolower(pin('email'));
            if ($name === '' || !valid_email($email)) throw new RuntimeException('Please enter your name and a valid email.');
            if (q_val('SELECT COUNT(*) FROM admins WHERE LOWER(email) = ? AND id <> ?', [$email, $me['id']])) throw new RuntimeException('Another admin already uses that email.');
            $d = ['name' => $name, 'email' => $email];
            if (pin('new') !== '') {
                if (mb_strlen(pin('new')) < 8) throw new RuntimeException('New password must be at least 8 characters.');
                if (pin('new') !== pin('confirm')) throw new RuntimeException('The new passwords do not match.');
                $d['password_hash'] = password_hash(pin('new'), PASSWORD_DEFAULT);
                set_setting('admin_default_pw', '0');
                session_regenerate_id(true);
            }
            update_row('admins', (int) $me['id'], $d);
            flash('success', isset($d['password_hash']) ? 'Profile and password updated.' : 'Profile updated.');
        } elseif ($act === 'add') {
            $name = mb_substr(pin('name'), 0, 120);
            $email = strtolower(pin('email'));
            if ($name === '' || !valid_email($email)) throw new RuntimeException('Enter a name and valid email for the new admin.');
            if (mb_strlen(pin('password')) < 8) throw new RuntimeException('Password must be at least 8 characters.');
            if (q_val('SELECT COUNT(*) FROM admins WHERE LOWER(email) = ?', [$email])) throw new RuntimeException('That email already has an admin account.');
            insert('admins', ['name' => $name, 'email' => $email, 'password_hash' => password_hash(pin('password'), PASSWORD_DEFAULT), 'created_at' => now()]);
            flash('success', $name . ' can now sign in.');
        } elseif ($act === 'remove') {
            $id = (int) pin('id');
            if ($id === (int) $me['id']) throw new RuntimeException('You cannot remove your own account.');
            q('DELETE FROM admins WHERE id = ?', [$id]);
            flash('success', 'Admin removed.');
        }
    } catch (RuntimeException $e) { flash('error', $e->getMessage()); }
    redirect(url('admin/account.php'));
}
$admins = q_all('SELECT id, name, email, created_at, last_login FROM admins ORDER BY id');
admin_header('Admins & Account', 'account');
?>
<?php if (setting('admin_default_pw') === '1'): ?><div class="alert alert-warn"><?= ic('alert') ?><div><b>Change the default password now.</b> Enter the current password, then a new one below.</div></div><?php endif; ?>
<div class="cols">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="card-h"><h2>Your account</h2></div>
    <div class="form-grid">
      <label class="f"><span>Name</span><input name="name" value="<?= e($me['name']) ?>" required></label>
      <label class="f"><span>Email (login)</span><input name="email" type="email" value="<?= e($me['email']) ?>" required autocomplete="username"></label>
      <label class="f"><span>New password <small>leave blank to keep</small></span><input name="new" type="password" autocomplete="new-password" minlength="8"></label>
      <label class="f"><span>Confirm new password</span><input name="confirm" type="password" autocomplete="new-password"></label>
      <label class="f full"><span>Current password *</span><input name="current" type="password" required autocomplete="current-password"></label>
    </div>
    <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
  </form>
  <div>
    <div class="card">
      <div class="card-h"><h2>Admin users</h2></div>
      <ul class="mini-list">
        <?php foreach ($admins as $a): ?><li><span><b><?= e($a['name']) ?></b><small><?= e($a['email']) ?> · last sign-in <?= $a['last_login'] ? fdate($a['last_login'], 'j M, g:ia') : 'never' ?></small></span>
          <?php if ((int) $a['id'] !== (int) $me['id']): ?><form method="post" onsubmit="return confirm('Remove this admin?')"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button class="btn btn-sm btn-line danger" type="submit"><?= ic('trash') ?></button></form><?php else: ?><span class="badge">You</span><?php endif; ?></li><?php endforeach; ?>
      </ul>
    </div>
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="add">
      <div class="card-h"><h2>Add an admin</h2></div>
      <div class="form-grid">
        <label class="f"><span>Name</span><input name="name" required></label>
        <label class="f"><span>Email</span><input name="email" type="email" required></label>
        <label class="f full"><span>Temporary password <small>min 8 characters</small></span><input name="password" type="text" minlength="8" required autocomplete="off"></label>
      </div>
      <button class="btn btn-dark btn-sm" type="submit"><?= ic('plus') ?> Add admin</button>
    </form>
  </div>
</div>
<?php admin_footer();
