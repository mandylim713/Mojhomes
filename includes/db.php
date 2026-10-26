<?php
function is_mysql(): bool
{
    return (CONFIG['db_driver'] ?? 'sqlite') === 'mysql';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (is_mysql()) {
        $m = CONFIG['mysql'];
        $opts[PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $m['host'], (int) ($m['port'] ?? 3306), $m['name']);
        $pdo = new PDO($dsn, $m['user'], $m['pass'], $opts);
    } else {
        $path = CONFIG['sqlite_path'];
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 6000');
    }
    db_migrate($pdo);
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}

function q_one(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function q_val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update_row(string $table, int $id, array $data): void
{
    if (!$data) return;
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    q("UPDATE $table SET $set WHERE id = ?", [...array_values($data), $id]);
}

function schema_sql(bool $my): array
{
    $pk  = $my ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $ref = $my ? 'INT UNSIGNED' : 'INTEGER';
    $end = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    return [
        "CREATE TABLE IF NOT EXISTS settings (name VARCHAR(100) NOT NULL PRIMARY KEY, value TEXT)$end",
        "CREATE TABLE IF NOT EXISTS admins (id $pk, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_login DATETIME NULL)$end",
        "CREATE TABLE IF NOT EXISTS login_attempts (id $pk, ip VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL)$end",
        "CREATE TABLE IF NOT EXISTS categories (id $pk, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE, description TEXT, image VARCHAR(255) NULL, sort INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1)$end",
        "CREATE TABLE IF NOT EXISTS products (id $pk, category_id $ref NULL, name VARCHAR(190) NOT NULL, slug VARCHAR(200) NOT NULL UNIQUE, short_desc VARCHAR(255) NULL, description TEXT, price INT NOT NULL DEFAULT 0, compare_price INT NULL, sku VARCHAR(80) NULL, stock INT NULL, unit VARCHAR(60) NULL, image VARCHAR(255) NULL, images TEXT, option_label VARCHAR(120) NULL, featured INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, sort INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)$end",
        "CREATE TABLE IF NOT EXISTS product_variants (id $pk, product_id $ref NOT NULL, name VARCHAR(120) NOT NULL, price INT NOT NULL DEFAULT 0, stock INT NULL, sort INT NOT NULL DEFAULT 0, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)$end",
        "CREATE TABLE IF NOT EXISTS delivery_zones (id $pk, name VARCHAR(150) NOT NULL, fee INT NOT NULL DEFAULT 0, eta VARCHAR(120) NULL, is_pickup INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, sort INT NOT NULL DEFAULT 0)$end",
        "CREATE TABLE IF NOT EXISTS orders (id $pk, ref VARCHAR(30) NOT NULL UNIQUE, token VARCHAR(64) NOT NULL, customer_name VARCHAR(150) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL, address TEXT, city VARCHAR(120) NULL, state VARCHAR(120) NULL, zone_id $ref NULL, zone_name VARCHAR(150) NULL, is_pickup INT NOT NULL DEFAULT 0, delivery_fee INT NOT NULL DEFAULT 0, subtotal INT NOT NULL DEFAULT 0, total INT NOT NULL DEFAULT 0, payment_method VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid', status VARCHAR(30) NOT NULL DEFAULT 'pending', notes TEXT, admin_notes TEXT, payment_ref VARCHAR(120) NULL, proof_file VARCHAR(255) NULL, paid_at DATETIME NULL, stock_restored INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)$end",
        "CREATE TABLE IF NOT EXISTS order_items (id $pk, order_id $ref NOT NULL, product_id $ref NULL, variant_id $ref NULL, name VARCHAR(190) NOT NULL, variant_name VARCHAR(120) NULL, option_label VARCHAR(120) NULL, option_value VARCHAR(190) NULL, image VARCHAR(255) NULL, unit_price INT NOT NULL, qty INT NOT NULL, line_total INT NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)$end",
        "CREATE TABLE IF NOT EXISTS order_events (id $pk, order_id $ref NOT NULL, status VARCHAR(30) NULL, note TEXT, actor VARCHAR(120) NULL, visible INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)$end",
        "CREATE TABLE IF NOT EXISTS gallery (id $pk, title VARCHAR(190) NOT NULL, category VARCHAR(60) NOT NULL, image VARCHAR(255) NOT NULL, sort INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL)$end",
        "CREATE TABLE IF NOT EXISTS enquiries (id $pk, type VARCHAR(30) NOT NULL, name VARCHAR(150) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL, service VARCHAR(150) NULL, property VARCHAR(120) NULL, location VARCHAR(190) NULL, start_when VARCHAR(120) NULL, details TEXT, status VARCHAR(20) NOT NULL DEFAULT 'new', created_at DATETIME NOT NULL)$end",
        "CREATE TABLE IF NOT EXISTS pages (id $pk, slug VARCHAR(80) NOT NULL UNIQUE, title VARCHAR(190) NOT NULL, intro VARCHAR(255) NULL, body TEXT, updated_at DATETIME NOT NULL)$end",
        "CREATE INDEX idx_products_cat ON products (category_id)",
        "CREATE INDEX idx_variants_product ON product_variants (product_id)",
        "CREATE INDEX idx_items_order ON order_items (order_id)",
        "CREATE INDEX idx_events_order ON order_events (order_id)",
        "CREATE INDEX idx_orders_status ON orders (status)",
        "CREATE INDEX idx_orders_created ON orders (created_at)",
        "CREATE INDEX idx_login_ip ON login_attempts (ip)",
    ];
}

function db_migrate(PDO $pdo): void
{
    try {
        $v = $pdo->query("SELECT value FROM settings WHERE name = 'schema_version'")->fetchColumn();
    } catch (PDOException $e) {
        $v = false;
    }
    if ($v !== false) return;

    foreach (schema_sql(is_mysql()) as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (!str_starts_with($sql, 'CREATE INDEX')) throw $e; // index may already exist
        }
    }
    require_once __DIR__ . '/seed.php';
    db_seed($pdo);
}
