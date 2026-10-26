<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$urls = ['', 'about.php', 'services.php', 'moj-paints.php', 'shop.php', 'projects.php', 'contact.php', 'track.php'];
foreach (q_all('SELECT slug FROM pages') as $p) $urls[] = 'page.php?p=' . $p['slug'];
foreach (q_all('SELECT slug FROM categories WHERE active = 1') as $c) $urls[] = 'shop.php?cat=' . $c['slug'];
$prods = q_all('SELECT slug, updated_at FROM products WHERE active = 1');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) echo '  <url><loc>' . e(abs_url($u)) . "</loc></url>\n";
foreach ($prods as $p) echo '  <url><loc>' . e(abs_url('product.php?slug=' . $p['slug'])) . '</loc><lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . "</lastmod></url>\n";
echo '</urlset>';
