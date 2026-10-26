/* Mojhometouch admin */
(function () {
  "use strict";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // clickable table rows
  $$("tr[data-href]").forEach((tr) => tr.addEventListener("click", (e) => {
    if (e.target.closest("a,button,input,select,form,label")) return;
    location.href = tr.dataset.href;
  }));

  // toggles (category edit rows)
  $$("[data-toggle]").forEach((b) => b.addEventListener("click", () => { const t = $(b.dataset.toggle); if (t) t.hidden = !t.hidden; }));

  // variants table
  const add = $("#addVar"), tpl = $("#vrow"), tb = $("#vtbl tbody");
  if (add && tpl && tb) add.addEventListener("click", () => { tb.append(tpl.content.cloneNode(true)); $("tr:last-child input[name='v_name[]']", tb).focus(); });
  document.addEventListener("click", (e) => { const b = e.target.closest("[data-rmrow]"); if (b) b.closest("tr").remove(); });

  // image preview
  $$("input[type=file][data-preview]").forEach((i) => i.addEventListener("change", () => {
    const img = $(i.dataset.preview); if (img && i.files[0]) img.src = URL.createObjectURL(i.files[0]);
  }));
  $$("input[type=file][data-count]").forEach((i) => i.addEventListener("change", () => {
    const out = i.closest("label").parentElement.querySelector("[data-count-out]"); if (out) out.textContent = i.files.length ? i.files.length + " image(s) selected" : "";
  }));

  // confirm before cancelling an order
  $$("[data-confirm-cancel]").forEach((b) => b.closest("form").addEventListener("submit", (e) => {
    const s = $("#statusSel"); if (s && s.value === "cancelled" && !confirm("Cancel this order? Stock will be returned and the order will be locked.")) e.preventDefault();
  }));

  // close sidebar on outside click (mobile)
  document.addEventListener("click", (e) => { const s = $("#side"); if (s && s.classList.contains("open") && !e.target.closest("#side,.side-toggle")) s.classList.remove("open"); });
})();
