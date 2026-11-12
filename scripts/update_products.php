<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

echo "Updating database with new products..." . PHP_EOL;

$now = date('Y-m-d H:i:s');

// 1. Ensure "Textured & Decorative Finishes" category exists
$texCat = q_one("SELECT * FROM categories WHERE slug = 'textured-decorative-finishes'");
if (!$texCat) {
    $texCatId = insert('categories', [
        'name' => 'Textured & Decorative Finishes',
        'slug' => 'textured-decorative-finishes',
        'description' => 'Architectural exterior & interior textures — Gravitex, Trowel finishes, and vintage Travertino relief putty.',
        'image' => 'assets/img/products/gravitex-paint.jpg',
        'sort' => 1,
        'active' => 1,
    ]);
    echo "Created Category: Textured & Decorative Finishes (ID: $texCatId)" . PHP_EOL;
} else {
    $texCatId = (int) $texCat['id'];
    q("UPDATE categories SET image = 'assets/img/products/gravitex-paint.jpg', active = 1 WHERE id = ?", [$texCatId]);
    echo "Found Category: Textured & Decorative Finishes (ID: $texCatId)" . PHP_EOL;
}

$paintsCat = q_one("SELECT id FROM categories WHERE slug = 'moj-paints'");
$paintsCatId = $paintsCat ? (int) $paintsCat['id'] : 1;

// Define the 9 products
$productsData = [
    // --- Group 1: Textured & Decorative Finishes ---
    [
        'category_id' => $texCatId,
        'name' => 'Gravitex Paint',
        'slug' => 'gravitex-paint',
        'short_desc' => 'High-build exterior textured finish with weather-proof protection and rich stone aggregate pattern.',
        'description' => '<p><strong>Gravitex Paint</strong> is a premium, heavy-duty textured wall coating specially engineered for exterior surfaces. Formulated with tough acrylic resins and graded mineral aggregates, it provides a distinctive textured relief that withstands harsh weather, tropical sun, and rain.</p>'
            . '<ul>'
            . '<li><strong>Application:</strong> Exterior building walls, pillars, perimeter fences, and architectural accents</li>'
            . '<li><strong>Protection:</strong> Highly durable, water-resistant, crack-bridging, and anti-fungal</li>'
            . '<li><strong>Finish:</strong> Heavy textured stone relief with grooved or dragged patterns</li>'
            . '<li><strong>Coverage:</strong> High film build for maximum substrate protection</li>'
            . '</ul>'
            . '<p><strong>Colours:</strong> Available in all standard and custom shades. Tell us your preferred colour when ordering or request expert colour advice on WhatsApp.</p>',
        'price' => 35000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/gravitex-paint.jpg',
        'images' => json_encode(['assets/img/products/exterior-textured-wall.jpg']),
        'option_label' => 'Preferred colour',
        'featured' => 1,
        'sort' => 10,
    ],
    [
        'category_id' => $texCatId,
        'name' => 'Trowel Paint',
        'slug' => 'trowel-paint',
        'short_desc' => 'Artisanal textured plaster for durable, high-end interior & exterior decorative feature walls.',
        'description' => '<p><strong>Trowel Paint</strong> delivers a rich, artisanal textured effect applied with a stainless steel trowel. Ideal for homeowners, architects, and interior designers who want depth, texture, and character beyond plain flat paint.</p>'
            . '<ul>'
            . '<li><strong>Use:</strong> Both interior feature walls and exterior facade finishing</li>'
            . '<li><strong>Texture:</strong> Multi-tonal sand and stone texture that captures natural and artificial light</li>'
            . '<li><strong>Durability:</strong> Highly resilient, impact-resistant, and easily wipeable</li>'
            . '<li><strong>Application:</strong> Trowel applied with custom swirl, drag, or float techniques</li>'
            . '</ul>'
            . '<p><strong>Colours:</strong> Warm earth tones, greys, charcoals, creams, and custom tinted palettes available.</p>',
        'price' => 35000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/trowel-paint.jpg',
        'images' => json_encode(['assets/img/products/exterior-textured-wall.jpg']),
        'option_label' => 'Texture / Colour',
        'featured' => 1,
        'sort' => 11,
    ],
    [
        'category_id' => $texCatId,
        'name' => 'MOJ Travertino Putty',
        'slug' => 'moj-travertino-putty',
        'short_desc' => 'Specialized decorative relief putty for vintage patterns, antique stencils, and textured feature walls.',
        'description' => '<p><strong>MOJ Travertino Putty</strong> is our high-grade Italian-style mineral plaster formulated for bespoke, vintage wall designs. It enables decorative artisans to create stone-veined effects, embossed mandala patterns, distressed antique finishes, and metallic-glazed luxury feature walls.</p>'
            . '<ul>'
            . '<li><strong>Perfect For:</strong> Living room TV accent walls, luxury headboard walls, hotel lobbies, and boutique lounges</li>'
            . '<li><strong>Design Potential:</strong> Supports stencil relief, travertine stone veining, distressed vintage wash, and metallic glazing</li>'
            . '<li><strong>Workability:</strong> Smooth, pliable consistency that holds sharp decorative impressions without cracking</li>'
            . '<li><strong>Finish:</strong> Can be sealed with clear protective coat or highlighted with metallic waxes</li>'
            . '</ul>'
            . '<p><em>Supply only or supply + professional decorative application by Mojhometouch crews.</em></p>',
        'price' => 30000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/moj-travertino-putty.jpg',
        'images' => json_encode(['assets/img/products/decorative-metallic-finish.jpg']),
        'option_label' => 'Design pattern / Shade',
        'featured' => 1,
        'sort' => 12,
    ],

    // --- Group 2: Standard Paint & Putty ---
    [
        'category_id' => $paintsCatId,
        'name' => 'Moj Matt Paint',
        'slug' => 'moj-matt-paint',
        'short_desc' => 'Ultra-flat, non-reflective acrylic finish with rich colour depth that conceals surface imperfections.',
        'description' => '<p><strong>Moj Matt Paint</strong> produces an elegant, velvety flat finish that diffuses light evenly across the wall. It is the premier choice for master bedrooms, ceilings, and open living spaces where a sophisticated, glare-free aesthetic is desired.</p>'
            . '<ul>'
            . '<li><strong>Finish:</strong> Non-reflective deep matt</li>'
            . '<li><strong>Best For:</strong> Ceilings, bedrooms, living rooms, and walls with slight surface irregularities</li>'
            . '<li><strong>Qualities:</strong> Quick drying, low odour, water-based, and rich opacity</li>'
            . '</ul>'
            . '<p>Tell us your preferred colour choice upon checkout.</p>',
        'price' => 45000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/moj-matt.jpg',
        'images' => '[]',
        'option_label' => 'Preferred colour',
        'featured' => 1,
        'sort' => 20,
    ],
    [
        'category_id' => $paintsCatId,
        'name' => 'Moj Satin Paint',
        'slug' => 'moj-satin-paint',
        'short_desc' => 'Silky, scrub-resistant washable sheen paint for high-traffic rooms, hallways, and modern interiors.',
        'description' => '<p><strong>Moj Satin Paint</strong> provides an exquisite soft sheen that is tough, highly washable, and stain-resistant. Formulated with premium 100% acrylic binders, it endures daily living while retaining its radiant beauty for years.</p>'
            . '<ul>'
            . '<li><strong>Finish:</strong> Elegant mid-sheen satin finish</li>'
            . '<li><strong>Best For:</strong> Living rooms, hallways, kitchens, dining areas, children’s rooms, and busy offices</li>'
            . '<li><strong>Durability:</strong> Easily wipes clean with mild soap and water without fading</li>'
            . '<li><strong>Application:</strong> Smooth glide with brush, roller, or airless sprayer</li>'
            . '</ul>'
            . '<p>Specify your desired colour shade when placing your order.</p>',
        'price' => 70000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/moj-satin.jpg',
        'images' => '[]',
        'option_label' => 'Preferred colour',
        'featured' => 1,
        'sort' => 21,
    ],
    [
        'category_id' => $paintsCatId,
        'name' => 'Moj Emulsion Economy',
        'slug' => 'moj-emulsion-economy',
        'short_desc' => 'Cost-effective, dependable water-based emulsion paint for budget projects and rental refurbishments.',
        'description' => '<p><strong>Moj Emulsion Economy</strong> is an affordable, dependable water-based emulsion designed to deliver neat, consistent coverage on everyday plaster and concrete surfaces without breaking the bank.</p>'
            . '<ul>'
            . '<li><strong>Best For:</strong> Rental properties, student accommodation, store rooms, and budget renovation projects</li>'
            . '<li><strong>Features:</strong> Easy application, fast drying, low odour</li>'
            . '<li><strong>Substrates:</strong> Concrete, cement plaster, and brickwork</li>'
            . '</ul>',
        'price' => 15000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/moj-emulsion.jpg',
        'images' => '[]',
        'option_label' => 'Preferred colour',
        'featured' => 0,
        'sort' => 22,
    ],
    [
        'category_id' => $paintsCatId,
        'name' => 'Moj Emulsion High Acrylic Standard',
        'slug' => 'moj-emulsion-high-acrylic-standard',
        'short_desc' => 'High-opacity, pure acrylic emulsion with superior coverage and lasting colour fidelity.',
        'description' => '<p><strong>Moj Emulsion High Acrylic Standard</strong> is our top-tier emulsion formulated with high-grade acrylic polymers. It provides outstanding hiding power, smooth leveling, and long-lasting colour retention on both interior and sheltered exterior walls.</p>'
            . '<ul>'
            . '<li><strong>Best For:</strong> Residential homes, commercial spaces, and exterior walls</li>'
            . '<li><strong>Coverage:</strong> High opacity — covers in fewer coats</li>'
            . '<li><strong>Performance:</strong> Excellent adhesion, resists flaking and chalking</li>'
            . '</ul>'
            . '<p>Available in off-white, brilliant white, cream, ash grey, and custom colours.</p>',
        'price' => 22000,
        'unit' => 'per bucket',
        'image' => 'assets/img/products/moj-emulsion.jpg',
        'images' => '[]',
        'option_label' => 'Preferred colour',
        'featured' => 1,
        'sort' => 23,
    ],
    [
        'category_id' => $paintsCatId,
        'name' => 'Moj Skimming Putty',
        'slug' => 'moj-skimming-putty',
        'short_desc' => 'Ultra-fine skimming compound for perfectly flat, pinhole-free walls prior to painting.',
        'description' => '<p><strong>Moj Skimming Putty</strong> is a ready-to-use / easy-mix fine wall skim designed to eliminate surface porosity, hairline cracks, and trowel marks. It creates an ultra-smooth porcelain-like substrate so your paint applies with flawless uniformity.</p>'
            . '<ul>'
            . '<li><strong>Use:</strong> Interior plaster skimming, drywall joint finishing, and ceiling smoothing</li>'
            . '<li><strong>Workability:</strong> Creamy consistency, easy to feather-edge, minimal sanding required</li>'
            . '<li><strong>Result:</strong> Smooth, seamless surface that enhances paint sheen and longevity</li>'
            . '</ul>',
        'price' => 18000,
        'unit' => 'per bucket',
        'image' => 'assets/img/moj-paints.jpg',
        'images' => '[]',
        'option_label' => 'Pack / Specification',
        'featured' => 0,
        'sort' => 24,
    ],
    [
        'category_id' => $paintsCatId,
        'name' => 'MOJ POP Screeding Paint',
        'slug' => 'moj-pop-screeding-paint',
        'short_desc' => 'Specially formulated primer & screeding paint for POP ceilings and fresh plasterboards.',
        'description' => '<p><strong>MOJ POP Screeding Paint</strong> is custom formulated to seal and prepare Plaster of Paris (POP) designs, cornices, ceiling boards, and screeded surfaces. It bonds tightly to fresh plaster, preventing moisture suction and peeling.</p>'
            . '<ul>'
            . '<li><strong>Best For:</strong> POP ceilings, decorative mouldings, cornices, and freshly screeded walls</li>'
            . '<li><strong>Function:</strong> Excellent sealer and undercoat; evens out surface absorption</li>'
            . '<li><strong>Coverage:</strong> High spreading rate, bright white base that makes final colours pop</li>'
            . '</ul>',
        'price' => 7000,
        'unit' => 'per bucket',
        'image' => 'assets/img/moj-paints.jpg',
        'images' => '[]',
        'option_label' => 'Colour / Batch',
        'featured' => 0,
        'sort' => 25,
    ],
];

// Clean up old sample emulsion/matt/satin seed if they exist
q("DELETE FROM product_variants WHERE product_id IN (SELECT id FROM products WHERE slug IN ('moj-emulsion-paint', 'moj-satin-paint', 'moj-matt-paint'))");
q("DELETE FROM products WHERE slug IN ('moj-emulsion-paint', 'moj-satin-paint', 'moj-matt-paint')");

foreach ($productsData as $pd) {
    $existing = q_one('SELECT id FROM products WHERE slug = ?', [$pd['slug']]);
    if ($existing) {
        $id = (int) $existing['id'];
        q('UPDATE products SET category_id = ?, name = ?, short_desc = ?, description = ?, price = ?, unit = ?, image = ?, images = ?, option_label = ?, featured = ?, sort = ?, active = 1, updated_at = ? WHERE id = ?', [
            $pd['category_id'],
            $pd['name'],
            $pd['short_desc'],
            $pd['description'],
            $pd['price'],
            $pd['unit'],
            $pd['image'],
            $pd['images'],
            $pd['option_label'],
            $pd['featured'],
            $pd['sort'],
            $now,
            $id,
        ]);
        echo "Updated: {$pd['name']} (₦" . number_format($pd['price']) . ")" . PHP_EOL;
    } else {
        $id = insert('products', [
            'category_id' => $pd['category_id'],
            'name' => $pd['name'],
            'slug' => $pd['slug'],
            'short_desc' => $pd['short_desc'],
            'description' => $pd['description'],
            'price' => $pd['price'],
            'unit' => $pd['unit'],
            'image' => $pd['image'],
            'images' => $pd['images'],
            'option_label' => $pd['option_label'],
            'featured' => $pd['featured'],
            'active' => 1,
            'sort' => $pd['sort'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo "Inserted: {$pd['name']} (₦" . number_format($pd['price']) . ") ID: $id" . PHP_EOL;
    }
}

// Mark sample prices as reviewed in settings
set_setting('sample_prices', '0');
echo "Sample prices flag cleared." . PHP_EOL;

echo "All products updated successfully!" . PHP_EOL;
