<?php
require_once __DIR__ . '/_init.php';
unset($_SESSION['admin_id'], $_SESSION['admin_seen']);
session_regenerate_id(true);
flash('success', 'You have signed out.');
redirect(url('admin/login.php'));
