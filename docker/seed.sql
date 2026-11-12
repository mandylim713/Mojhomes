-- Mojhometouch seed data (auto-generated 2026-11-12 10:25:06)
PRAGMA journal_mode=WAL;

-- Table: admins
CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_login DATETIME NULL);
INSERT OR IGNORE INTO "admins" ("id", "name", "email", "password_hash", "created_at", "last_login") VALUES ('1', 'Administrator', 'admin@mojhometouch.com', '$2y$10$qIn/pETP4IJzm7e5tR4aAu.fqS3vr7WXMa8HUH2ELh/8Jb7IyZu6m', '2026-11-04 08:13:05', NULL);

-- Table: categories
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE, description TEXT, image VARCHAR(255) NULL, sort INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1);
INSERT OR IGNORE INTO "categories" ("id", "name", "slug", "description", "image", "sort", "active") VALUES ('1', 'Moj Paints', 'moj-paints', 'Our own high quality acrylic paint — emulsion, satin and matt.', 'assets/img/products/moj-banner.jpg', '0', '1');
INSERT OR IGNORE INTO "categories" ("id", "name", "slug", "description", "image", "sort", "active") VALUES ('2', 'Wall Panels & Marble', 'wall-panels-marble', 'WPC fluted panels and PVC marble sheets for feature walls.', 'assets/img/wpc-fluted-panels.jpg', '1', '1');
INSERT OR IGNORE INTO "categories" ("id", "name", "slug", "description", "image", "sort", "active") VALUES ('3', 'Décor & Accessories', 'decor-accessories', 'Mirrors, clocks, wall art and ornaments to finish the look.', 'assets/img/gold-mirror-clock.jpg', '2', '1');
INSERT OR IGNORE INTO "categories" ("id", "name", "slug", "description", "image", "sort", "active") VALUES ('4', 'Textured & Decorative Finishes', 'textured-decorative-finishes', 'Architectural exterior & interior textures — Gravitex, Trowel finishes, and vintage Travertino relief putty.', 'assets/img/products/gravitex-paint.jpg', '1', '1');

-- Table: delivery_zones
CREATE TABLE delivery_zones (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(150) NOT NULL, fee INT NOT NULL DEFAULT 0, eta VARCHAR(120) NULL, is_pickup INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, sort INT NOT NULL DEFAULT 0);
INSERT OR IGNORE INTO "delivery_zones" ("id", "name", "fee", "eta", "is_pickup", "active", "sort") VALUES ('1', 'Pickup — Nsukka showroom', '0', 'Ready within 24 hours', '1', '1', '0');
INSERT OR IGNORE INTO "delivery_zones" ("id", "name", "fee", "eta", "is_pickup", "active", "sort") VALUES ('2', 'Nsukka & environs', '2000', 'Same or next day', '0', '1', '1');
INSERT OR IGNORE INTO "delivery_zones" ("id", "name", "fee", "eta", "is_pickup", "active", "sort") VALUES ('3', 'Other towns in Enugu State', '5000', '1–3 working days', '0', '1', '2');
INSERT OR IGNORE INTO "delivery_zones" ("id", "name", "fee", "eta", "is_pickup", "active", "sort") VALUES ('4', 'South-East states', '10000', '2–5 working days', '0', '1', '3');
INSERT OR IGNORE INTO "delivery_zones" ("id", "name", "fee", "eta", "is_pickup", "active", "sort") VALUES ('5', 'Rest of Nigeria', '15000', '3–7 working days', '0', '1', '4');

-- Table: enquiries
CREATE TABLE enquiries (id INTEGER PRIMARY KEY AUTOINCREMENT, type VARCHAR(30) NOT NULL, name VARCHAR(150) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL, service VARCHAR(150) NULL, property VARCHAR(120) NULL, location VARCHAR(190) NULL, start_when VARCHAR(120) NULL, details TEXT, status VARCHAR(20) NOT NULL DEFAULT 'new', created_at DATETIME NOT NULL);

-- Table: gallery
CREATE TABLE gallery (id INTEGER PRIMARY KEY AUTOINCREMENT, title VARCHAR(190) NOT NULL, category VARCHAR(60) NOT NULL, image VARCHAR(255) NOT NULL, sort INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL);
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('1', 'Backlit feature wall with sculpted niches', 'painting', 'assets/img/feature-wall-painting.jpg', '0', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('2', 'Curved POP ceiling with LED lighting', 'ceilings', 'assets/img/pop-ceiling-sitting-room.jpg', '1', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('3', 'Wood-grain door finishing', 'doors', 'assets/img/door-finishing-wood.jpg', '2', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('4', 'Custom curtains with box pelmets', 'curtains', 'assets/img/curtains-sitting-room.jpg', '3', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('5', 'Feature wall finishing in progress', 'painting', 'assets/img/feature-wall-detail.jpg', '4', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('6', 'Panelled door finish', 'doors', 'assets/img/door-finishing-white.jpg', '5', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('7', 'Layered POP ceiling with cove lighting', 'ceilings', 'assets/img/pop-ceiling-lighting.jpg', '6', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('8', 'Fluted panels with mirror wall', 'panels', 'assets/img/fluted-mirror-wall.jpg', '7', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('9', 'Tray ceiling with mirror feature wall', 'ceilings', 'assets/img/luxury-living-room.jpg', '8', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('10', 'WPC fluted panel colours', 'panels', 'assets/img/wpc-fluted-panels.jpg', '9', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('11', 'PVC marble sheet designs', 'panels', 'assets/img/marble-sheets.jpg', '10', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('12', 'LED moon wall art', 'decor', 'assets/img/moon-wall-art.jpg', '11', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('13', 'Gold mirror wall clock', 'decor', 'assets/img/gold-mirror-clock.jpg', '12', '1', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "gallery" ("id", "title", "category", "image", "sort", "active", "created_at") VALUES ('14', 'Eiffel Tower ornament', 'decor', 'assets/img/eiffel-tower-decor.jpg', '13', '1', '2026-11-04 08:13:05');

-- Table: login_attempts
CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, ip VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL);

-- Table: order_events
CREATE TABLE order_events (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL, status VARCHAR(30) NULL, note TEXT, actor VARCHAR(120) NULL, visible INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE);

-- Table: order_items
CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL, product_id INTEGER NULL, variant_id INTEGER NULL, name VARCHAR(190) NOT NULL, variant_name VARCHAR(120) NULL, option_label VARCHAR(120) NULL, option_value VARCHAR(190) NULL, image VARCHAR(255) NULL, unit_price INT NOT NULL, qty INT NOT NULL, line_total INT NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE);

-- Table: orders
CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, ref VARCHAR(30) NOT NULL UNIQUE, token VARCHAR(64) NOT NULL, customer_name VARCHAR(150) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL, address TEXT, city VARCHAR(120) NULL, state VARCHAR(120) NULL, zone_id INTEGER NULL, zone_name VARCHAR(150) NULL, is_pickup INT NOT NULL DEFAULT 0, delivery_fee INT NOT NULL DEFAULT 0, subtotal INT NOT NULL DEFAULT 0, total INT NOT NULL DEFAULT 0, payment_method VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid', status VARCHAR(30) NOT NULL DEFAULT 'pending', notes TEXT, admin_notes TEXT, payment_ref VARCHAR(120) NULL, proof_file VARCHAR(255) NULL, paid_at DATETIME NULL, stock_restored INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL);

-- Table: pages
CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, slug VARCHAR(80) NOT NULL UNIQUE, title VARCHAR(190) NOT NULL, intro VARCHAR(255) NULL, body TEXT, updated_at DATETIME NOT NULL);
INSERT OR IGNORE INTO "pages" ("id", "slug", "title", "intro", "body", "updated_at") VALUES ('1', 'how-to-order', 'How to Order', 'Our simple ordering process — from cart to your doorstep.', '<h2>Ordering products online</h2>
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
<p>For painting, POP, screeding, curtains, furniture, epoxy or other projects, <a href="contact.php#quote">request a quote</a>. We will contact you to arrange a site visit and send an itemised quotation. Contractors and builders can request bulk pricing for Moj Paints.</p>', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "pages" ("id", "slug", "title", "intro", "body", "updated_at") VALUES ('2', 'delivery-returns', 'Delivery & Returns', 'How we deliver, how long it takes and what happens if something is wrong.', '<h2>Delivery</h2>
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
<p>You can cancel an order before it is processed or dispatched by contacting us with your order number.</p>', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "pages" ("id", "slug", "title", "intro", "body", "updated_at") VALUES ('3', 'terms', 'Terms & Conditions', 'The terms that apply when you use this website or buy from us.', '<p>These terms apply to purchases and services from Mojhometouch NIG LTD (“we”, “us”), a company registered in Nigeria and based in Nsukka, Enugu State. By placing an order you agree to them.</p>
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
<p>Questions? Call or WhatsApp us on the number shown on this website.</p>', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "pages" ("id", "slug", "title", "intro", "body", "updated_at") VALUES ('4', 'privacy', 'Privacy Policy', 'How we collect, use and protect your information.', '<p>Mojhometouch NIG LTD respects your privacy and handles personal data in line with the Nigeria Data Protection Act 2023.</p>
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
<p>You can ask us to access, correct or delete your personal data by contacting us. We keep order records only as long as needed for the purposes above.</p>', '2026-11-04 08:13:05');

-- Table: product_variants
CREATE TABLE product_variants (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER NOT NULL, name VARCHAR(120) NOT NULL, price INT NOT NULL DEFAULT 0, stock INT NULL, sort INT NOT NULL DEFAULT 0, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE);

-- Table: products
CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NULL, name VARCHAR(190) NOT NULL, slug VARCHAR(200) NOT NULL UNIQUE, short_desc VARCHAR(255) NULL, description TEXT, price INT NOT NULL DEFAULT 0, compare_price INT NULL, sku VARCHAR(80) NULL, stock INT NULL, unit VARCHAR(60) NULL, image VARCHAR(255) NULL, images TEXT, option_label VARCHAR(120) NULL, featured INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1, sort INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL);
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('4', '2', 'WPC Fluted Wall Panel', 'wpc-fluted-wall-panel', 'Durable fluted panels for elegant feature walls and TV areas.', '<p>Wood-plastic composite (WPC) fluted panels give any wall a premium, architectural look. Moisture resistant, termite proof and easy to clean.</p><ul><li>Available in wood, gold, grey, black, marble and solid colours</li><li>Ideal for TV walls, headboards, receptions and offices</li><li>Supply only, or supply and professional installation</li></ul><p>Tell us the colour you want when ordering — or send a photo of your space on WhatsApp for advice.</p>', '12500', NULL, NULL, NULL, 'per panel', 'assets/img/wpc-fluted-panels.jpg', '[]', 'Colour / finish', '1', '1', '3', '2026-11-04 08:13:05', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('5', '2', 'PVC Marble Sheet', 'pvc-marble-sheet', 'High-gloss marble-effect sheets for luxurious walls.', '<p>Get the look of real marble at a fraction of the cost and weight. Our PVC marble sheets are waterproof, easy to install and easy to maintain.</p><ul><li>Designs include black &amp; gold, white &amp; grey, dark grey and more</li><li>Perfect for living rooms, bathrooms, reception walls and feature columns</li><li>Supply only, or supply and installation</li></ul>', '18000', NULL, NULL, NULL, 'per sheet', 'assets/img/marble-sheets.jpg', '[]', 'Design (e.g. black & gold)', '0', '1', '4', '2026-11-04 08:13:05', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('6', '3', 'Gold Mirror Wall Clock', 'gold-mirror-wall-clock', 'Ornate gold frame with a mirrored surround — a true statement piece.', '<p>A classic statement clock with an ornate gold frame and mirrored surround. Perfect for living rooms, entryways and offices.</p><ul><li>Large, easy-to-read numerals</li><li>Quiet quartz movement</li><li>Comes boxed</li></ul>', '45000', NULL, NULL, '6', 'each', 'assets/img/gold-mirror-clock.jpg', '[]', NULL, '1', '1', '5', '2026-11-04 08:13:05', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('7', '3', 'Eiffel Tower Ornament', 'eiffel-tower-ornament', 'Bronze-finish metal tower ornament for shelves and consoles.', '<p>A detailed metal Eiffel Tower ornament with a bronze finish — a timeless accent for shelves, consoles, study tables and display niches.</p>', '15000', NULL, NULL, '10', 'each', 'assets/img/eiffel-tower-decor.jpg', '[]', NULL, '0', '1', '6', '2026-11-04 08:13:05', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('8', '3', 'LED Moon Wall Art', 'led-moon-wall-art', 'Sculpted lunar relief with a warm LED glow, framed in black.', '<p>A handcrafted, sculpted moon relief with warm LED edge lighting in a black round frame. Stunning in lounges, offices and receptions. Made to order — sizes vary.</p><p>Contact us for sizes, price and delivery time.</p>', '0', NULL, NULL, NULL, 'each', 'assets/img/moon-wall-art.jpg', '[]', NULL, '1', '1', '7', '2026-11-04 08:13:05', '2026-11-04 08:13:05');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('9', '4', 'Gravitex Paint', 'gravitex-paint', 'High-build exterior textured finish with weather-proof protection and rich stone aggregate pattern.', '<p><strong>Gravitex Paint</strong> is a premium, heavy-duty textured wall coating specially engineered for exterior surfaces. Formulated with tough acrylic resins and graded mineral aggregates, it provides a distinctive textured relief that withstands harsh weather, tropical sun, and rain.</p><ul><li><strong>Application:</strong> Exterior building walls, pillars, perimeter fences, and architectural accents</li><li><strong>Protection:</strong> Highly durable, water-resistant, crack-bridging, and anti-fungal</li><li><strong>Finish:</strong> Heavy textured stone relief with grooved or dragged patterns</li><li><strong>Coverage:</strong> High film build for maximum substrate protection</li></ul><p><strong>Colours:</strong> Available in all standard and custom shades. Tell us your preferred colour when ordering or request expert colour advice on WhatsApp.</p>', '35000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/gravitex-paint.jpg', '["assets\/img\/products\/exterior-textured-wall.jpg"]', 'Preferred colour', '1', '1', '10', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('10', '4', 'Trowel Paint', 'trowel-paint', 'Artisanal textured plaster for durable, high-end interior & exterior decorative feature walls.', '<p><strong>Trowel Paint</strong> delivers a rich, artisanal textured effect applied with a stainless steel trowel. Ideal for homeowners, architects, and interior designers who want depth, texture, and character beyond plain flat paint.</p><ul><li><strong>Use:</strong> Both interior feature walls and exterior facade finishing</li><li><strong>Texture:</strong> Multi-tonal sand and stone texture that captures natural and artificial light</li><li><strong>Durability:</strong> Highly resilient, impact-resistant, and easily wipeable</li><li><strong>Application:</strong> Trowel applied with custom swirl, drag, or float techniques</li></ul><p><strong>Colours:</strong> Warm earth tones, greys, charcoals, creams, and custom tinted palettes available.</p>', '35000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/trowel-paint.jpg', '["assets\/img\/products\/exterior-textured-wall.jpg"]', 'Texture / Colour', '1', '1', '11', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('11', '4', 'MOJ Travertino Putty', 'moj-travertino-putty', 'Specialized decorative relief putty for vintage patterns, antique stencils, and textured feature walls.', '<p><strong>MOJ Travertino Putty</strong> is our high-grade Italian-style mineral plaster formulated for bespoke, vintage wall designs. It enables decorative artisans to create stone-veined effects, embossed mandala patterns, distressed antique finishes, and metallic-glazed luxury feature walls.</p><ul><li><strong>Perfect For:</strong> Living room TV accent walls, luxury headboard walls, hotel lobbies, and boutique lounges</li><li><strong>Design Potential:</strong> Supports stencil relief, travertine stone veining, distressed vintage wash, and metallic glazing</li><li><strong>Workability:</strong> Smooth, pliable consistency that holds sharp decorative impressions without cracking</li><li><strong>Finish:</strong> Can be sealed with clear protective coat or highlighted with metallic waxes</li></ul><p><em>Supply only or supply + professional decorative application by Mojhometouch crews.</em></p>', '30000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/moj-travertino-putty.jpg', '["assets\/img\/products\/decorative-metallic-finish.jpg"]', 'Design pattern / Shade', '1', '1', '12', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('12', '1', 'Moj Matt Paint', 'moj-matt-paint', 'Ultra-flat, non-reflective acrylic finish with rich colour depth that conceals surface imperfections.', '<p><strong>Moj Matt Paint</strong> produces an elegant, velvety flat finish that diffuses light evenly across the wall. It is the premier choice for master bedrooms, ceilings, and open living spaces where a sophisticated, glare-free aesthetic is desired.</p><ul><li><strong>Finish:</strong> Non-reflective deep matt</li><li><strong>Best For:</strong> Ceilings, bedrooms, living rooms, and walls with slight surface irregularities</li><li><strong>Qualities:</strong> Quick drying, low odour, water-based, and rich opacity</li></ul><p>Tell us your preferred colour choice upon checkout.</p>', '45000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/moj-matt.jpg', '[]', 'Preferred colour', '1', '1', '20', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('13', '1', 'Moj Satin Paint', 'moj-satin-paint', 'Silky, scrub-resistant washable sheen paint for high-traffic rooms, hallways, and modern interiors.', '<p><strong>Moj Satin Paint</strong> provides an exquisite soft sheen that is tough, highly washable, and stain-resistant. Formulated with premium 100% acrylic binders, it endures daily living while retaining its radiant beauty for years.</p><ul><li><strong>Finish:</strong> Elegant mid-sheen satin finish</li><li><strong>Best For:</strong> Living rooms, hallways, kitchens, dining areas, children’s rooms, and busy offices</li><li><strong>Durability:</strong> Easily wipes clean with mild soap and water without fading</li><li><strong>Application:</strong> Smooth glide with brush, roller, or airless sprayer</li></ul><p>Specify your desired colour shade when placing your order.</p>', '70000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/moj-satin.jpg', '[]', 'Preferred colour', '1', '1', '21', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('14', '1', 'Moj Emulsion Economy', 'moj-emulsion-economy', 'Cost-effective, dependable water-based emulsion paint for budget projects and rental refurbishments.', '<p><strong>Moj Emulsion Economy</strong> is an affordable, dependable water-based emulsion designed to deliver neat, consistent coverage on everyday plaster and concrete surfaces without breaking the bank.</p><ul><li><strong>Best For:</strong> Rental properties, student accommodation, store rooms, and budget renovation projects</li><li><strong>Features:</strong> Easy application, fast drying, low odour</li><li><strong>Substrates:</strong> Concrete, cement plaster, and brickwork</li></ul>', '15000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/moj-emulsion.jpg', '[]', 'Preferred colour', '0', '1', '22', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('15', '1', 'Moj Emulsion High Acrylic Standard', 'moj-emulsion-high-acrylic-standard', 'High-opacity, pure acrylic emulsion with superior coverage and lasting colour fidelity.', '<p><strong>Moj Emulsion High Acrylic Standard</strong> is our top-tier emulsion formulated with high-grade acrylic polymers. It provides outstanding hiding power, smooth leveling, and long-lasting colour retention on both interior and sheltered exterior walls.</p><ul><li><strong>Best For:</strong> Residential homes, commercial spaces, and exterior walls</li><li><strong>Coverage:</strong> High opacity — covers in fewer coats</li><li><strong>Performance:</strong> Excellent adhesion, resists flaking and chalking</li></ul><p>Available in off-white, brilliant white, cream, ash grey, and custom colours.</p>', '22000', NULL, NULL, NULL, 'per bucket', 'assets/img/products/moj-emulsion.jpg', '[]', 'Preferred colour', '1', '1', '23', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('16', '1', 'Moj Skimming Putty', 'moj-skimming-putty', 'Ultra-fine skimming compound for perfectly flat, pinhole-free walls prior to painting.', '<p><strong>Moj Skimming Putty</strong> is a ready-to-use / easy-mix fine wall skim designed to eliminate surface porosity, hairline cracks, and trowel marks. It creates an ultra-smooth porcelain-like substrate so your paint applies with flawless uniformity.</p><ul><li><strong>Use:</strong> Interior plaster skimming, drywall joint finishing, and ceiling smoothing</li><li><strong>Workability:</strong> Creamy consistency, easy to feather-edge, minimal sanding required</li><li><strong>Result:</strong> Smooth, seamless surface that enhances paint sheen and longevity</li></ul>', '18000', NULL, NULL, NULL, 'per bucket', 'assets/img/moj-paints.jpg', '[]', 'Pack / Specification', '0', '1', '24', '2026-11-04 08:23:11', '2026-11-04 08:23:11');
INSERT OR IGNORE INTO "products" ("id", "category_id", "name", "slug", "short_desc", "description", "price", "compare_price", "sku", "stock", "unit", "image", "images", "option_label", "featured", "active", "sort", "created_at", "updated_at") VALUES ('17', '1', 'MOJ POP Screeding Paint', 'moj-pop-screeding-paint', 'Specially formulated primer & screeding paint for POP ceilings and fresh plasterboards.', '<p><strong>MOJ POP Screeding Paint</strong> is custom formulated to seal and prepare Plaster of Paris (POP) designs, cornices, ceiling boards, and screeded surfaces. It bonds tightly to fresh plaster, preventing moisture suction and peeling.</p><ul><li><strong>Best For:</strong> POP ceilings, decorative mouldings, cornices, and freshly screeded walls</li><li><strong>Function:</strong> Excellent sealer and undercoat; evens out surface absorption</li><li><strong>Coverage:</strong> High spreading rate, bright white base that makes final colours pop</li></ul>', '7000', NULL, NULL, NULL, 'per bucket', 'assets/img/moj-paints.jpg', '[]', 'Colour / Batch', '0', '1', '25', '2026-11-04 08:23:11', '2026-11-04 08:23:11');

-- Table: settings
CREATE TABLE settings (name VARCHAR(100) NOT NULL PRIMARY KEY, value TEXT);
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('business_name', 'Mojhometouch NIG LTD');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('tagline', 'Perfection Only');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('phone', '0703 104 1249');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('whatsapp', '2347031041249');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('email', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('notify_email', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('address', 'Nsukka, Enugu State, Nigeria');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('map_query', 'Nsukka, Enugu, Nigeria');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('hours', 'Mon – Sat, 8:00am – 6:00pm');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('announcement', 'Moj Paints now available to order online — delivery across Nigeria');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('bank_name', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('bank_account_name', 'Mojhometouch NIG LTD');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('bank_account_number', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('pay_bank', '1');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('pay_pod', '1');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('pay_flutterwave', '0');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('flw_public_key', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('flw_secret_key', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('flw_secret_hash', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('low_stock', '5');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('facebook', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('instagram', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('tiktok', '');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('sample_prices', '0');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('admin_default_pw', '1');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('db_uid', '5e0d3f106b3cba5e');
INSERT OR IGNORE INTO "settings" ("name", "value") VALUES ('schema_version', '1');

