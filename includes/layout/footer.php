</main>
<footer class="site"><div class="wrap">
  <div class="foot-grid">
    <div class="foot-brand">
      <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Mojhometouch logo" width="78" height="64" loading="lazy">
      <p>Mojhometouch NIG LTD is a registered Nigerian company for classic painting, interior finishing and interior design — homes, offices and institutions.</p>
      <div class="tagline"><?= e(setting('tagline', 'Perfection Only')) ?>.</div>
      <?php $soc = array_filter(['FB' => setting('facebook'), 'IG' => setting('instagram'), 'TT' => setting('tiktok')]); if ($soc): ?>
      <div class="socials"><?php foreach ($soc as $k => $u): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="<?= e($k) ?>"><?= e($k) ?></a><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div><h4>Company</h4><ul>
      <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
      <li><a href="<?= e(url('services.php')) ?>">Services</a></li>
      <li><a href="<?= e(url('projects.php')) ?>">Our Projects</a></li>
      <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
      <li><a href="<?= e(url('contact.php#quote')) ?>">Request a Quote</a></li>
    </ul></div>
    <div><h4>Shop</h4><ul>
      <li><a href="<?= e(url('moj-paints.php')) ?>">Moj Paints</a></li>
      <li><a href="<?= e(url('shop.php')) ?>">All Products</a></li>
      <li><a href="<?= e(url('cart.php')) ?>">Your Cart</a></li>
      <li><a href="<?= e(url('track.php')) ?>">Track an Order</a></li>
    </ul></div>
    <div><h4>Help</h4><ul>
      <li><a href="<?= e(url('page.php?p=how-to-order')) ?>">How to Order</a></li>
      <li><a href="<?= e(url('page.php?p=delivery-returns')) ?>">Delivery &amp; Returns</a></li>
      <li><a href="<?= e(url('page.php?p=terms')) ?>">Terms &amp; Conditions</a></li>
      <li><a href="<?= e(url('page.php?p=privacy')) ?>">Privacy Policy</a></li>
    </ul></div>
    <div><h4>Reach Us</h4><ul>
      <li><?= e(setting('address')) ?></li>
      <li><a href="<?= e(tel_href()) ?>"><?= e(setting('phone')) ?></a></li>
      <li><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
      <?php if (setting('email')): ?><li><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li><?php endif; ?>
      <li><?= e(setting('hours')) ?></li>
    </ul></div>
  </div>
  <div class="foot-bottom">
    <span>© <?= date('Y') ?> <?= e(setting('business_name')) ?>. All rights reserved.</span>
    <span>Powered by <a href="https://digitalweboracleict.com" target="_blank" rel="noopener">DWO</a></span>
  </div>
</div></footer>
<a class="wa-float no-print" href="<?= e(wa_link('Hello Mojhometouch, I\'d like to make an enquiry.')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?= wa_icon() ?></a>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
