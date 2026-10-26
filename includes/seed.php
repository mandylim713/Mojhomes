<?php
/**
 * First-run data. Runs once, when the database is empty.
 * Prices and delivery fees below are SAMPLES — edit them in Admin.
 */
function db_seed(PDO $pdo): void
{
    $now = date('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $ins = function (string $table, array $row) use ($pdo): int {
            $cols = array_keys($row);
            $st = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')');
            $st->execute(array_values($row));
            return (int) $pdo->lastInsertId();
        };

        // ---- Settings
        $settings = [
            'business_name'       => 'Mojhometouch NIG LTD',
            'tagline'             => 'Perfection Only',
            'phone'               => '0703 104 1249',
            'whatsapp'            => '2347031041249',
            'email'               => '',
            'notify_email'        => '',
            'address'             => 'Nsukka, Enugu State, Nigeria',
            'map_query'           => 'Nsukka, Enugu, Nigeria',
            'hours'               => 'Mon – Sat, 8:00am – 6:00pm',
            'announcement'        => 'Moj Paints now available to order online — delivery across Nigeria',
            'bank_name'           => '',
            'bank_account_name'   => 'Mojhometouch NIG LTD',
            'bank_account_number' => '',
            'pay_bank'            => '1',
            'pay_pod'             => '1',
            'pay_flutterwave'     => '0',
            'flw_public_key'      => '',
            'flw_secret_key'      => '',
            'flw_secret_hash'     => '',
            'low_stock'           => '5',
            'facebook'            => '',
            'instagram'           => '',
            'tiktok'              => '',
            'sample_prices'       => '1',
            'admin_default_pw'    => (CONFIG['admin_password'] ?? 'Moj@Admin2026') === 'Moj@Admin2026' ? '1' : '0',
            'db_uid'              => bin2hex(random_bytes(8)),
        ];
        foreach ($settings as $k => $v) $ins('settings', ['name' => $k, 'value' => $v]);

        // ---- Admin
        $ins('admins', [
            'name' => 'Administrator',
            'email' => strtolower((string) (CONFIG['admin_email'] ?? 'admin@mojhometouch.com')),
            'password_hash' => password_hash((string) (CONFIG['admin_password'] ?? 'Moj@Admin2026'), PASSWORD_DEFAULT),
            'created_at' => $now,
        ]);

        // ---- Categories
        $cats = [
            ['Moj Paints', 'moj-paints', 'Our own high quality acrylic paint — emulsion, satin and matt.', 'assets/img/products/moj-banner.jpg'],
            ['Wall Panels & Marble', 'wall-panels-marble', 'WPC fluted panels and PVC marble sheets for feature walls.', 'assets/img/wpc-fluted-panels.jpg'],
            ['Décor & Accessories', 'decor-accessories', 'Mirrors, clocks, wall art and ornaments to finish the look.', 'assets/img/gold-mirror-clock.jpg'],
        ];
        $catId = [];
        foreach ($cats as $i => [$n, $s, $d, $img]) {
            $catId[$s] = $ins('categories', ['name' => $n, 'slug' => $s, 'description' => $d, 'image' => $img, 'sort' => $i, 'active' => 1]);
        }

        // ---- Products
        $paintBody = function (string $finish, string $use): string {
            return "<p>Moj {$finish} is part of our own Moj Paints range — a high quality acrylic paint produced by Mojhometouch and used by our crews on every project.</p>"
                . "<ul><li><strong>Best for:</strong> {$use}</li><li>Smooth application by brush, roller or spray</li><li>Rich, even colour with dependable coverage</li><li>Water-based, low odour, quick drying</li></ul>"
                . "<p><strong>Colours:</strong> tell us your preferred colour when ordering (e.g. off-white, ash grey, cream). Not sure? Leave it blank and our team will call to advise.</p>"
                . "<p><strong>Bulk &amp; contractor orders:</strong> contact us for project pricing.</p>";
        };
        $products = [
            ['moj-paints', 'Moj Emulsion Paint', 'Smooth, versatile acrylic emulsion for interior and exterior walls.', $paintBody('Emulsion', 'interior and exterior walls, ceilings, everyday finishing'), 'assets/img/products/moj-emulsion.jpg', 'Preferred colour', 1, null, [['4 Litres', 8500], ['20 Litres', 38000]], 'per bucket'],
            ['moj-paints', 'Moj Satin Paint', 'A soft sheen that wipes clean — ideal for high-traffic rooms.', $paintBody('Satin', 'living rooms, kitchens, hallways, kids’ rooms and offices'), 'assets/img/products/moj-satin.jpg', 'Preferred colour', 1, null, [['4 Litres', 11500], ['20 Litres', 48000]], 'per bucket'],
            ['moj-paints', 'Moj Matt Paint', 'A flat, rich finish that hides surface imperfections.', $paintBody('Matt', 'ceilings, bedrooms and walls with minor surface flaws'), 'assets/img/products/moj-matt.jpg', 'Preferred colour', 0, null, [['4 Litres', 7500], ['20 Litres', 32000]], 'per bucket'],
            ['wall-panels-marble', 'WPC Fluted Wall Panel', 'Durable fluted panels for elegant feature walls and TV areas.', '<p>Wood-plastic composite (WPC) fluted panels give any wall a premium, architectural look. Moisture resistant, termite proof and easy to clean.</p><ul><li>Available in wood, gold, grey, black, marble and solid colours</li><li>Ideal for TV walls, headboards, receptions and offices</li><li>Supply only, or supply and professional installation</li></ul><p>Tell us the colour you want when ordering — or send a photo of your space on WhatsApp for advice.</p>', 'assets/img/wpc-fluted-panels.jpg', 'Colour / finish', 1, null, [], 'per panel', 12500],
            ['wall-panels-marble', 'PVC Marble Sheet', 'High-gloss marble-effect sheets for luxurious walls.', '<p>Get the look of real marble at a fraction of the cost and weight. Our PVC marble sheets are waterproof, easy to install and easy to maintain.</p><ul><li>Designs include black &amp; gold, white &amp; grey, dark grey and more</li><li>Perfect for living rooms, bathrooms, reception walls and feature columns</li><li>Supply only, or supply and installation</li></ul>', 'assets/img/marble-sheets.jpg', 'Design (e.g. black & gold)', 0, null, [], 'per sheet', 18000],
            ['decor-accessories', 'Gold Mirror Wall Clock', 'Ornate gold frame with a mirrored surround — a true statement piece.', '<p>A classic statement clock with an ornate gold frame and mirrored surround. Perfect for living rooms, entryways and offices.</p><ul><li>Large, easy-to-read numerals</li><li>Quiet quartz movement</li><li>Comes boxed</li></ul>', 'assets/img/gold-mirror-clock.jpg', null, 1, 6, [], 'each', 45000],
            ['decor-accessories', 'Eiffel Tower Ornament', 'Bronze-finish metal tower ornament for shelves and consoles.', '<p>A detailed metal Eiffel Tower ornament with a bronze finish — a timeless accent for shelves, consoles, study tables and display niches.</p>', 'assets/img/eiffel-tower-decor.jpg', null, 0, 10, [], 'each', 15000],
            ['decor-accessories', 'LED Moon Wall Art', 'Sculpted lunar relief with a warm LED glow, framed in black.', '<p>A handcrafted, sculpted moon relief with warm LED edge lighting in a black round frame. Stunning in lounges, offices and receptions. Made to order — sizes vary.</p><p>Contact us for sizes, price and delivery time.</p>', 'assets/img/moon-wall-art.jpg', null, 1, null, [], 'each', 0],
        ];
        foreach ($products as $i => $p) {
            [$cat, $name, $short, $desc, $img, $optLabel, $featured, $stock, $variants, $unit] = $p;
            $price = $p[10] ?? ($variants ? $variants[0][1] : 0);
            $pid = $ins('products', [
                'category_id' => $catId[$cat], 'name' => $name, 'slug' => slugify($name), 'short_desc' => $short,
                'description' => $desc, 'price' => $price, 'stock' => $stock, 'unit' => $unit, 'image' => $img, 'images' => '[]',
                'option_label' => $optLabel, 'featured' => $featured, 'active' => 1, 'sort' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($variants as $j => [$vn, $vp]) {
                $ins('product_variants', ['product_id' => $pid, 'name' => $vn, 'price' => $vp, 'stock' => null, 'sort' => $j]);
            }
        }

        // ---- Delivery zones
        $zones = [
            ['Pickup — Nsukka showroom', 0, 'Ready within 24 hours', 1],
            ['Nsukka & environs', 2000, 'Same or next day', 0],
            ['Other towns in Enugu State', 5000, '1–3 working days', 0],
            ['South-East states', 10000, '2–5 working days', 0],
            ['Rest of Nigeria', 15000, '3–7 working days', 0],
        ];
        foreach ($zones as $i => [$n, $f, $eta, $pick]) {
            $ins('delivery_zones', ['name' => $n, 'fee' => $f, 'eta' => $eta, 'is_pickup' => $pick, 'active' => 1, 'sort' => $i]);
        }

        // ---- Gallery
        $gal = [
            ['Backlit feature wall with sculpted niches', 'painting', 'assets/img/feature-wall-painting.jpg'],
            ['Curved POP ceiling with LED lighting', 'ceilings', 'assets/img/pop-ceiling-sitting-room.jpg'],
            ['Wood-grain door finishing', 'doors', 'assets/img/door-finishing-wood.jpg'],
            ['Custom curtains with box pelmets', 'curtains', 'assets/img/curtains-sitting-room.jpg'],
            ['Feature wall finishing in progress', 'painting', 'assets/img/feature-wall-detail.jpg'],
            ['Panelled door finish', 'doors', 'assets/img/door-finishing-white.jpg'],
            ['Layered POP ceiling with cove lighting', 'ceilings', 'assets/img/pop-ceiling-lighting.jpg'],
            ['Fluted panels with mirror wall', 'panels', 'assets/img/fluted-mirror-wall.jpg'],
            ['Tray ceiling with mirror feature wall', 'ceilings', 'assets/img/luxury-living-room.jpg'],
            ['WPC fluted panel colours', 'panels', 'assets/img/wpc-fluted-panels.jpg'],
            ['PVC marble sheet designs', 'panels', 'assets/img/marble-sheets.jpg'],
            ['LED moon wall art', 'decor', 'assets/img/moon-wall-art.jpg'],
            ['Gold mirror wall clock', 'decor', 'assets/img/gold-mirror-clock.jpg'],
            ['Eiffel Tower ornament', 'decor', 'assets/img/eiffel-tower-decor.jpg'],
        ];
        foreach ($gal as $i => [$t, $c, $img]) {
            $ins('gallery', ['title' => $t, 'category' => $c, 'image' => $img, 'sort' => $i, 'active' => 1, 'created_at' => $now]);
        }

        // ---- Pages
        foreach (seed_pages() as $slug => [$title, $intro, $body]) {
            $ins('pages', ['slug' => $slug, 'title' => $title, 'intro' => $intro, 'body' => $body, 'updated_at' => $now]);
        }

        $ins('settings', ['name' => 'schema_version', 'value' => '1']);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function seed_pages(): array
{
    return [
        'how-to-order' => ['How to Order', 'Our simple ordering process — from cart to your doorstep.', <<<HTML
<h2>Ordering products online</h2>
<ol>
<li><strong>Choose your products.</strong> Browse the <a href="shop.php">shop</a>, pick a size or option and add items to your cart. For paints, tell us your preferred colour.</li>
<li><strong>Checkout.</strong> Enter your name, phone number and delivery details, then choose delivery or pickup at our Nsukka showroom.</li>
<li><strong>Pay.</strong> Pay securely online (card, bank transfer or USSD) when available, transfer to our company account and upload your receipt, or pay on delivery/pickup where offered.</li>
<li><strong>Confirmation.</strong> You get an order number (e.g. MOJ241025-AB12) and a link to your order page. Our team confirms your order and payment, usually within a few working hours.</li>
<li><strong>Processing &amp; delivery.</strong> We prepare your order and deliver it, or let you know when it is ready for pickup. You can follow every step on the <a href="track.php">Track Order</a> page.</li>
</ol>
<h2>Order statuses explained</h2>
<ul>
<li><strong>Pending</strong> — we have received your order and will confirm it shortly.</li>
<li><strong>Confirmed</strong> — your order and payment (where required) are confirmed.</li>
<li><strong>Processing</strong> — your items are being prepared or tinted.</li>
<li><strong>Ready for Pickup</strong> — collect from our showroom with your order number.</li>
<li><strong>Out for Delivery</strong> — your order is on its way.</li>
<li><strong>Delivered</strong> — completed. Thank you for choosing Mojhometouch!</li>
</ul>
<h2>Services, projects &amp; bulk orders</h2>
<p>For painting, POP, screeding, curtains, furniture, epoxy or other projects, <a href="contact.php#quote">request a quote</a>. We will contact you to arrange a site visit and send an itemised quotation. Contractors and builders can request bulk pricing for Moj Paints.</p>
HTML],
        'delivery-returns' => ['Delivery & Returns', 'How we deliver, how long it takes and what happens if something is wrong.', <<<HTML
<h2>Delivery</h2>
<ul>
<li>We deliver within Nsukka, across Enugu State and to other states in Nigeria. Delivery fees are shown at checkout based on your location.</li>
<li>Delivery times are estimates and start from when your order is confirmed. Times may vary for large orders, custom colours and remote locations.</li>
<li>Please make sure someone is available on the phone number provided to receive the delivery.</li>
<li>You may also choose free pickup at our Nsukka showroom. Please bring your order number.</li>
</ul>
<h2>Checking your order</h2>
<p>Please inspect your items on delivery or pickup. Report any damaged, missing or incorrect items within <strong>48 hours</strong> with your order number and photos, by phone or WhatsApp.</p>
<h2>Returns &amp; exchanges</h2>
<ul>
<li>Damaged or incorrect items reported within 48 hours will be replaced or refunded.</li>
<li>Unopened, unused products in original condition may be returned within 7 days of delivery. Delivery costs for change-of-mind returns are paid by the customer.</li>
<li>Paints tinted or mixed to a custom colour, opened products and made-to-order items cannot be returned unless faulty.</li>
</ul>
<h2>Refunds</h2>
<p>Approved refunds are paid back to the original payment method or by bank transfer within 7 working days.</p>
<h2>Cancellations</h2>
<p>You can cancel an order before it is processed or dispatched by contacting us with your order number.</p>
HTML],
        'terms' => ['Terms & Conditions', 'The terms that apply when you use this website or buy from us.', <<<HTML
<p>These terms apply to purchases and services from Mojhometouch NIG LTD (“we”, “us”), a company registered in Nigeria and based in Nsukka, Enugu State. By placing an order you agree to them.</p>
<h2>Orders &amp; pricing</h2>
<ul>
<li>All prices are in Nigerian Naira (₦). We work hard to keep prices and stock accurate; if an error occurs we will contact you before processing your order.</li>
<li>An order is accepted when we confirm it. We may decline or cancel an order (with a full refund of any payment) if an item is unavailable or a pricing error occurred.</li>
<li>Colours on screen may differ slightly from actual paint and product colours.</li>
</ul>
<h2>Payment</h2>
<ul>
<li>Online payments are processed securely by our payment partner; we never see or store your card details.</li>
<li>For bank transfers, your order is confirmed once payment is received in our account.</li>
<li>Pay on delivery, where offered, is payable in full when your order is delivered or collected.</li>
</ul>
<h2>Services &amp; projects</h2>
<p>Painting, finishing and installation projects are carried out under a separate quotation that sets out the scope, price, timeline and payment schedule. The quotation applies in addition to these terms.</p>
<h2>Delivery, returns &amp; refunds</h2>
<p>See our <a href="page.php?p=delivery-returns">Delivery &amp; Returns</a> policy.</p>
<h2>Contact</h2>
<p>Questions? Call or WhatsApp us on the number shown on this website.</p>
HTML],
        'privacy' => ['Privacy Policy', 'How we collect, use and protect your information.', <<<HTML
<p>Mojhometouch NIG LTD respects your privacy and handles personal data in line with the Nigeria Data Protection Act 2023.</p>
<h2>What we collect</h2>
<ul>
<li>Details you give us when you order or request a quote: name, phone number, email, delivery address and project details.</li>
<li>Order and payment status information (we do not store card details — online payments are handled by our payment partner).</li>
<li>Basic technical data needed to run the website, such as session cookies that keep your cart.</li>
</ul>
<h2>How we use it</h2>
<ul>
<li>To process, deliver and support your orders and projects.</li>
<li>To contact you about your order, quote or enquiry by phone, WhatsApp or email.</li>
<li>To keep records required for accounting and legal purposes.</li>
</ul>
<p>We do not sell your personal information. We share it only with service providers who help us operate (for example delivery partners and payment processors), and only as needed.</p>
<h2>Your rights</h2>
<p>You can ask us to access, correct or delete your personal data by contacting us. We keep order records only as long as needed for the purposes above.</p>
HTML],
    ];
}
