/* Mojhometouch — site scripts */
(function () {
  "use strict";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const body = document.body;
  const ICON_OK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>';
  const ICON_ERR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>';
  const ICON_X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';

  /* ---------- Mobile nav ---------- */
  const burger = $(".burger"), nav = $("nav.main");
  if (burger && nav) {
    burger.addEventListener("click", () => {
      const open = nav.classList.toggle("open");
      burger.setAttribute("aria-expanded", open);
    });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape" && nav.classList.contains("open")) { nav.classList.remove("open"); burger.setAttribute("aria-expanded", false); burger.focus(); } });
  }

  /* ---------- Header shadow ---------- */
  const hdr = $("header.site");
  const onScroll = () => hdr && hdr.classList.toggle("scrolled", scrollY > 10);
  addEventListener("scroll", onScroll, { passive: true }); onScroll();

  /* ---------- Reveal ---------- */
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); } }), { threshold: 0.08, rootMargin: "0px 0px -40px 0px" });
    $$(".rv").forEach((el) => io.observe(el));
  } else $$(".rv").forEach((el) => el.classList.add("in"));

  /* ---------- Toasts ---------- */
  const toasts = $(".toasts");
  const dismiss = (t) => { t.classList.add("out"); setTimeout(() => t.remove(), 350); };
  const wireToast = (t) => {
    const x = $(".x", t); if (x) x.addEventListener("click", () => dismiss(t));
    setTimeout(() => t.isConnected && dismiss(t), t.classList.contains("error") ? 9000 : 6000);
  };
  $$(".toast").forEach(wireToast);
  window.mojToast = (msg, type = "success", link) => {
    if (!toasts) return;
    const t = document.createElement("div");
    t.className = "toast" + (type === "error" ? " error" : "");
    t.setAttribute("role", type === "error" ? "alert" : "status");
    const tx = document.createElement("div"); tx.className = "tx"; tx.textContent = msg;
    if (link) { const a = document.createElement("a"); a.href = link.href; a.textContent = link.label; tx.append(" ", a); }
    t.innerHTML = type === "error" ? ICON_ERR : ICON_OK;
    t.append(tx);
    const x = document.createElement("button"); x.className = "x"; x.setAttribute("aria-label", "Dismiss"); x.innerHTML = ICON_X; t.append(x);
    toasts.prepend(t); wireToast(t);
  };

  /* ---------- Cart count ---------- */
  const setCount = (n) => {
    $$(".cart-count").forEach((c) => { c.textContent = n; c.dataset.n = n; c.classList.remove("bump"); void c.offsetWidth; c.classList.add("bump"); });
    $$(".cart-btn").forEach((b) => b.setAttribute("aria-label", "Cart (" + n + " items)"));
  };

  /* ---------- Add to cart (AJAX, falls back to normal POST) ---------- */
  $$("form.js-add").forEach((f) => {
    f.addEventListener("submit", async (e) => {
      const buyNow = e.submitter && e.submitter.name === "buy_now";
      if (buyNow || !window.fetch) return; // buy now → normal POST → checkout
      e.preventDefault();
      const btn = e.submitter || $("button[type=submit]", f);
      btn && btn.classList.add("is-loading");
      try {
        const res = await fetch(f.action, { method: "POST", body: new FormData(f), headers: { "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" });
        const j = await res.json();
        if (j.ok) { setCount(j.count); mojToast(j.message, "success", { href: j.cart_url, label: "View cart" }); }
        else mojToast(j.message || "Could not add to cart.", "error");
      } catch (err) { f.submit(); }
      finally { btn && btn.classList.remove("is-loading"); }
    });
  });

  /* ---------- Quantity steppers ---------- */
  $$("[data-qty]").forEach((w) => {
    const input = $("input[type=number]", w);
    const autoSubmit = w.tagName === "FORM" && w.classList.contains("js-qty");
    let timer;
    const commit = () => { if (autoSubmit) { clearTimeout(timer); timer = setTimeout(() => w.submit(), 450); } };
    $$("[data-step]", w).forEach((b) => b.addEventListener("click", () => {
      const min = parseInt(input.min || "1", 10), max = parseInt(input.max || "999", 10);
      let v = (parseInt(input.value, 10) || 0) + parseInt(b.dataset.step, 10);
      v = Math.max(min, Math.min(max, v)); input.value = v; commit();
    }));
    input.addEventListener("change", commit);
  });

  /* ---------- Product page ---------- */
  const main = $("#pdMain");
  $$(".pd-thumbs button").forEach((b) => b.addEventListener("click", () => {
    $$(".pd-thumbs button").forEach((x) => x.classList.remove("on")); b.classList.add("on");
    main.style.opacity = 0; setTimeout(() => { main.src = b.dataset.src; main.style.opacity = 1; }, 150);
  }));
  const priceOut = $("#pdPrice"), stockNote = $("#stockNote");
  $$("input[name=variant_id]").forEach((r) => r.addEventListener("change", () => {
    if (priceOut) priceOut.textContent = r.dataset.price;
    if (stockNote) {
      const s = r.dataset.stock, low = parseInt(stockNote.dataset.low || "5", 10);
      stockNote.className = "stock-note";
      if (s !== "" && parseInt(s, 10) <= low) { stockNote.classList.add("low"); stockNote.textContent = "Only " + s + " left in stock"; }
      else stockNote.textContent = "In stock — ready to deliver";
      const q = $("#buyForm input[name=qty]"); if (q) q.max = s !== "" ? s : 999;
    }
  }));

  /* ---------- Checkout ---------- */
  const co = $("#checkout");
  if (co) {
    const sub = parseInt($("[data-subtotal]").dataset.subtotal, 10);
    const fmt = (n) => "₦" + n.toLocaleString("en-NG");
    const addr = $("#addrFields"), pick = $("#pickupNote");
    const update = () => {
      const z = $("input[name=zone]:checked", co);
      if (!z) return;
      const fee = parseInt(z.dataset.fee, 10);
      $("#feeOut").textContent = fee ? fmt(fee) : "Free";
      $("#totalOut").textContent = fmt(sub + fee);
      const isPick = z.dataset.pickup === "1";
      if (addr) addr.style.display = isPick ? "none" : "";
      if (pick) pick.style.display = isPick ? "" : "none";
      const pm = $("input[name=payment]:checked", co);
      const hint = $("#emailHint");
      if (hint) hint.innerHTML = pm && pm.value === "flutterwave" ? '<em>*</em> (required for online payment)' : "(for your receipt)";
    };
    $$("input[name=zone], input[name=payment]", co).forEach((i) => i.addEventListener("change", update));
    update();
    co.addEventListener("submit", () => { const b = $("#placeBtn"); if (b) { b.classList.add("is-loading"); b.lastChild.textContent = " Placing order…"; } });
  }

  /* ---------- Clear field errors as the user fixes them ---------- */
  $$(".field.err input, .field.err select, .field.err textarea").forEach((i) => i.addEventListener("input", () => {
    const f = i.closest(".field"); f.classList.remove("err"); const m = $(".msg", f); if (m) m.remove();
  }, { once: true }));
  $$("input[name=agree]").forEach((c) => c.addEventListener("change", () => { const m = c.closest("label").nextElementSibling; if (c.checked && m && m.classList.contains("msg")) m.remove(); }));

  /* ---------- Copy buttons ---------- */
  $$("[data-copy]").forEach((b) => b.addEventListener("click", async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = "COPIED"; b.classList.add("done"); setTimeout(() => { b.textContent = "COPY"; b.classList.remove("done"); }, 1800); } catch (e) {}
  }));

  /* ---------- File inputs ---------- */
  $$(".file-drop input[type=file]").forEach((i) => i.addEventListener("change", () => {
    const fn = $(".fn", i.closest(".file-drop")); if (fn && i.files[0]) fn.textContent = i.files[0].name;
  }));

  /* ---------- Gallery filter + lightbox ---------- */
  const gal = $(".masonry");
  if (gal) {
    const figs = $$("figure", gal);
    $$(".filters button").forEach((b) => b.addEventListener("click", () => {
      $$(".filters button").forEach((x) => x.classList.remove("on")); b.classList.add("on");
      figs.forEach((f) => f.classList.toggle("hide", b.dataset.f !== "all" && f.dataset.cat !== b.dataset.f));
    }));
    const lb = $(".lightbox"), lbImg = $("img", lb), lbCap = $("p", lb);
    let i = 0;
    const vis = () => figs.filter((f) => !f.classList.contains("hide"));
    const show = (n) => { const v = vis(); if (!v.length) return; i = (n + v.length) % v.length; const im = $("img", v[i]); lbImg.src = im.src; lbImg.alt = im.alt; lbCap.textContent = im.alt; };
    const close = () => lb.classList.remove("open");
    figs.forEach((f) => { f.tabIndex = 0; const open = () => { show(vis().indexOf(f)); lb.classList.add("open"); $(".x", lb).focus(); }; f.addEventListener("click", open); f.addEventListener("keydown", (e) => { if (e.key === "Enter") open(); }); });
    $(".x", lb).onclick = close;
    $(".pv", lb).onclick = (e) => { e.stopPropagation(); show(i - 1); };
    $(".nx", lb).onclick = (e) => { e.stopPropagation(); show(i + 1); };
    lb.addEventListener("click", (e) => { if (e.target === lb) close(); });
    document.addEventListener("keydown", (e) => { if (!lb.classList.contains("open")) return; if (e.key === "Escape") close(); if (e.key === "ArrowLeft") show(i - 1); if (e.key === "ArrowRight") show(i + 1); });
  }
})();
