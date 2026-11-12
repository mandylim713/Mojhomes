<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$prods = q_all("SELECT p.name, p.price, p.unit, p.image, c.name as cat FROM products p JOIN categories c ON c.id = p.category_id ORDER BY c.sort, p.sort, p.id");
foreach ($prods as $p) {
    echo "• [" . $p['cat'] . "] " . $p['name'] . " — ₦" . number_format((float) $p['price']) . " (" . $p['unit'] . ") | Img: " . $p['image'] . PHP_EOL;
}

