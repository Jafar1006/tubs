/* =====================================================================
   Gedung Inn — JavaScript halaman publik
   - Compare tray (localStorage, maksimal 3 gedung)
   - Filter katalog tanpa reload (fetch ke api/katalog.php)
   - Drawer filter mobile, galeri foto, estimasi biaya, toast
   ===================================================================== */
(function () {
  "use strict";

  const GI = window.GI || {};
  const MAX_COMPARE = GI.maxCompare || 3;
  const STORAGE_KEY = "gi_compare";

  const ICON = {
    check: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
    compare: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 5v14M12 3v18M19 7v10M2 8h6m1 8h6m1-6h6"/></svg>',
    arrow: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>'
  };

  const rupiah = (n) => "Rp " + new Intl.NumberFormat("id-ID").format(Math.round(n));

  /* -------------------------------------------------------------------
     Toast
     ------------------------------------------------------------------- */
  function toast(message, kind) {
    const box = document.getElementById("toasts");
    if (!box) return;
    const el = document.createElement("div");
    el.className = "toast";
    el.innerHTML = (kind === "error" ? ICON.check : ICON.check) + "<span>" + message + "</span>";
    box.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }

  document.querySelectorAll(".toast[data-autohide]").forEach((el) => {
    setTimeout(() => el.remove(), 3000);
  });

  /* -------------------------------------------------------------------
     Compare state
     ------------------------------------------------------------------- */
  function readCompare() {
    try {
      const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
      if (!Array.isArray(raw)) return [];
      return raw.map(Number).filter((n) => Number.isInteger(n) && n > 0).slice(0, MAX_COMPARE);
    } catch (err) {
      return [];
    }
  }

  function writeCompare(ids) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.slice(0, MAX_COMPARE)));
    syncCompareUI();
  }

  function toggleCompare(id) {
    id = Number(id);
    const ids = readCompare();
    if (ids.includes(id)) {
      writeCompare(ids.filter((n) => n !== id));
      return;
    }
    if (ids.length >= MAX_COMPARE) {
      toast("Maksimal 3 gedung. Hapus salah satu dulu.");
      return;
    }
    writeCompare(ids.concat(id));
  }

  function syncCompareUI() {
    const ids = readCompare();
    document.querySelectorAll("[data-compare-id]").forEach((btn) => {
      const id = Number(btn.dataset.compareId);
      const on = ids.includes(id);
      btn.classList.toggle("selected", on);
      btn.textContent = on ? "✓ Dibandingkan" : "+ Bandingkan";
    });
    const counter = document.getElementById("compare-count");
    if (counter) counter.textContent = String(ids.length);
    renderTray(ids);
  }

  function traySlotHTML(slot) {
    return (
      '<div class="tray-slot">' +
      '<img src="' + slot.foto + '" alt="" width="38" height="38">' +
      "<span>" + slot.nama + "</span>" +
      '<button type="button" aria-label="Hapus ' + slot.nama + '" data-tray-remove="' + slot.id + '">×</button>' +
      "</div>"
    );
  }

  const TRAY_BLANK = '<div class="tray-slot blank"><span>＋</span>Pilih gedung</div>';

  async function renderTray(ids) {
    const host = document.getElementById("compare-tray");
    if (!host) return;

    if (!ids.length) {
      host.innerHTML = "";
      return;
    }

    let slots = [];
    try {
      const res = await fetch(GI.trayApi + "?ids=" + ids.join(","), { headers: { Accept: "application/json" } });
      const data = await res.json();
      slots = data.slots || [];
    } catch (err) {
      slots = [];
    }

    let html = "";
    for (let i = 0; i < MAX_COMPARE; i++) {
      html += slots[i] ? traySlotHTML(slots[i]) : TRAY_BLANK;
    }

    host.innerHTML =
      '<div class="compare-tray">' +
      '<div class="container tray-inner">' +
      '<div class="tray-label">' + ICON.compare +
      "<strong>Bandingkan gedung</strong>" +
      "<small>" + slots.length + " dari " + MAX_COMPARE + " gedung dipilih</small></div>" +
      '<div class="tray-slots">' + html + "</div>" +
      '<div class="tray-actions">' +
      '<button type="button" class="text-btn" data-tray-clear>Hapus semua</button>' +
      '<button type="button" class="btn yellow" data-tray-go ' + (slots.length < 2 ? "disabled" : "") + ">" +
      "Bandingkan sekarang" + ICON.arrow + "</button>" +
      "</div></div></div>";

    host.querySelectorAll("[data-tray-remove]").forEach((btn) => {
      btn.addEventListener("click", () => toggleCompare(btn.dataset.trayRemove));
    });
    const clear = host.querySelector("[data-tray-clear]");
    if (clear) clear.addEventListener("click", () => writeCompare([]));
    const go = host.querySelector("[data-tray-go]");
    if (go) {
      go.addEventListener("click", () => {
        if (go.disabled) return;
        window.location.href = GI.bandingkan + "?ids=" + readCompare().join(",");
      });
    }
  }

  document.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-compare-id]");
    if (btn) {
      event.preventDefault();
      toggleCompare(btn.dataset.compareId);
    }
  });

  // Halaman Bandingkan: tombol hapus per kolom
  document.querySelectorAll("[data-remove-compare]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = Number(btn.dataset.removeCompare);
      const ids = readCompare().filter((n) => n !== id);
      writeCompare(ids);
      window.location.href = GI.bandingkan + (ids.length ? "?ids=" + ids.join(",") : "");
    });
  });

  /* -------------------------------------------------------------------
     Filter katalog (AJAX)
     ------------------------------------------------------------------- */
  const filterForms = Array.from(document.querySelectorAll("form[data-filter-form]"));
  const resultsBox = document.getElementById("venue-results");

  function skeletonHTML() {
    let cards = "";
    for (let i = 0; i < 6; i++) {
      cards += '<div class="skeleton-card"><div></div><span></span><span></span><span></span></div>';
    }
    return '<div class="venue-grid" aria-label="Memuat gedung">' + cards + "</div>";
  }

  function formParams(form) {
    return new URLSearchParams(new FormData(form));
  }

  function syncOtherForms(source) {
    const params = formParams(source);
    filterForms.forEach((form) => {
      if (form === source) return;
      form.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
        cb.checked = params.getAll(cb.name).includes(cb.value);
      });
      form.querySelectorAll('input[type="range"], input[type="number"]').forEach((el) => {
        if (params.has(el.name)) el.value = params.get(el.name);
      });
      form.querySelectorAll("[data-q-input]").forEach((el) => {
        if (params.has("q")) el.value = params.get("q");
      });
      form.querySelectorAll("[data-sort-input]").forEach((el) => {
        if (params.has("sort")) el.value = params.get("sort");
      });
      form.querySelectorAll("[data-chip-input]").forEach((input) => {
        const on = params.getAll(input.name).includes(input.value);
        input.disabled = !on;
        const chip = input.previousElementSibling;
        if (chip && chip.matches("[data-chip]")) {
          chip.classList.toggle("chosen", on);
          chip.setAttribute("aria-pressed", on ? "true" : "false");
        }
      });
      syncRangeLabels(form);
    });
  }

  function syncRangeLabels(form) {
    const min = form.querySelector('input[type="range"][name="min"]');
    const max = form.querySelector('input[type="range"][name="max"]');
    if (!min || !max) return;
    const minOut = form.querySelector("[data-price-min]");
    const maxOut = form.querySelector("[data-price-max]");
    if (minOut) minOut.textContent = rupiah(min.value);
    if (maxOut) maxOut.textContent = rupiah(max.value);
  }

  let requestId = 0;
  let timer = null;

  async function applyFilters(form, opts) {
    if (!form || !resultsBox) return;
    const options = opts || {};
    syncOtherForms(form);

    const params = formParams(form);
    const current = ++requestId;

    resultsBox.innerHTML = skeletonHTML();

    try {
      const res = await fetch(GI.katalogApi + "?" + params.toString(), {
        headers: { Accept: "application/json", "X-Requested-With": "fetch" }
      });
      const data = await res.json();
      if (current !== requestId) return; // ada request lebih baru

      resultsBox.innerHTML = data.html;

      const count = document.getElementById("result-count");
      if (count) count.textContent = data.count + " gedung";
      const end = document.getElementById("result-count-end");
      if (end) end.textContent = String(data.count);
      const apply = document.getElementById("apply-filters");
      if (apply) apply.textContent = "Tampilkan " + data.count + " gedung";
      const active = document.getElementById("active-filter-count");
      if (active) {
        active.textContent = data.active + " filter";
        active.hidden = data.active === 0;
      }
      const mobile = document.getElementById("mobile-filter-count");
      if (mobile) mobile.textContent = data.active ? " (" + data.active + ")" : "";

      if (options.push !== false && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.search = params.toString();
        window.history.replaceState({}, "", url);
      }
      syncCompareUI();
    } catch (err) {
      resultsBox.innerHTML =
        '<div class="empty"><h2>Tidak dapat memuat data</h2><p>Periksa koneksi lalu muat ulang halaman.</p></div>';
    }
  }

  filterForms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      applyFilters(form);
    });

    form.addEventListener("change", (event) => {
      const target = event.target;
      if (target.matches('input[type="range"], input[type="number"]')) {
        clampRanges(form);
        syncRangeLabels(form);
      }
      if (target.matches("[data-chip-input]")) return;
      applyFilters(form);
    });

    form.addEventListener("input", (event) => {
      const target = event.target;
      if (target.matches('input[type="range"]')) {
        clampRanges(form);
        syncRangeLabels(form);
        clearTimeout(timer);
        timer = setTimeout(() => applyFilters(form), 250);
      }
    });

    form.querySelectorAll("[data-chip]").forEach((chip) => {
      chip.addEventListener("click", () => {
        const input = chip.nextElementSibling;
        if (!input || !input.matches("[data-chip-input]")) return;
        input.disabled = !input.disabled;
        chip.classList.toggle("chosen", !input.disabled);
        chip.setAttribute("aria-pressed", input.disabled ? "false" : "true");
        applyFilters(form);
      });
    });

    syncRangeLabels(form);
  });

  function clampRanges(form) {
    const min = form.querySelector('input[type="range"][name="min"]');
    const max = form.querySelector('input[type="range"][name="max"]');
    if (!min || !max) return;
    if (Number(min.value) > Number(max.value)) {
      if (document.activeElement === min) {
        max.value = min.value;
      } else {
        min.value = max.value;
      }
    }
  }

  // Hero search
  const heroSearch = document.getElementById("hero-search");
  if (heroSearch) {
    heroSearch.addEventListener("submit", (event) => {
      event.preventDefault();
      const q = (heroSearch.querySelector('input[name="q"]') || {}).value || "";
      filterForms.forEach((form) => {
        const hidden = form.querySelector("[data-q-input]");
        if (hidden) hidden.value = q;
      });
      const primary = document.getElementById("filters-f");
      if (primary) applyFilters(primary);
      const catalog = document.getElementById("catalog");
      if (catalog) catalog.scrollIntoView({ behavior: "smooth" });
    });

    const heroInput = heroSearch.querySelector('input[name="q"]');
    if (heroInput) {
      heroInput.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          filterForms.forEach((form) => {
            const hidden = form.querySelector("[data-q-input]");
            if (hidden) hidden.value = heroInput.value;
          });
          const primary = document.getElementById("filters-f");
          if (primary) applyFilters(primary);
        }, 400);
      });
    }
  }

  // Sort dropdown
  const sortSelect = document.getElementById("sort-select");
  if (sortSelect) {
    sortSelect.addEventListener("change", () => {
      filterForms.forEach((form) => {
        const hidden = form.querySelector("[data-sort-input]");
        if (hidden) hidden.value = sortSelect.value;
      });
      const primary = document.getElementById("filters-f");
      if (primary) applyFilters(primary);
    });
  }

  /* -------------------------------------------------------------------
     Drawer filter (mobile)
     ------------------------------------------------------------------- */
  const drawer = document.getElementById("filter-drawer");
  const openFilter = document.getElementById("open-filter");

  function openDrawer() {
    if (!drawer) return;
    drawer.hidden = false;
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    if (!drawer) return;
    drawer.hidden = true;
    document.body.style.overflow = "";
  }

  if (openFilter) openFilter.addEventListener("click", openDrawer);

  if (drawer) {
    drawer.querySelectorAll("[data-close-drawer]").forEach((btn) => {
      btn.addEventListener("click", closeDrawer);
    });
    drawer.addEventListener("mousedown", (event) => {
      if (event.target === drawer) closeDrawer();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !drawer.hidden) closeDrawer();
    });

    const apply = document.getElementById("apply-filters");
    if (apply) {
      apply.addEventListener("click", () => {
        const form = document.getElementById("filters-d");
        applyFilters(form);
        closeDrawer();
      });
    }
  }

  /* -------------------------------------------------------------------
     Halaman detail: galeri, estimasi biaya, tombol kembali
     ------------------------------------------------------------------- */
  const mainPhoto = document.getElementById("gallery-main");
  const thumbs = document.getElementById("gallery-thumbs");
  if (mainPhoto && thumbs) {
    thumbs.addEventListener("click", (event) => {
      const btn = event.target.closest("button[data-photo]");
      if (!btn) return;
      const next = btn.dataset.photo;
      const previous = mainPhoto.src;
      const alt = mainPhoto.alt;

      mainPhoto.src = next;
      mainPhoto.alt = btn.querySelector("img") ? btn.querySelector("img").alt : alt;

      const img = btn.querySelector("img");
      if (img) {
        img.src = previous;
        img.alt = alt;
      }
    });
  }

  const backBtn = document.getElementById("back-btn");
  if (backBtn) {
    backBtn.addEventListener("click", (event) => {
      const canGoBack = window.history.length > 1 && (!window.navigation || window.navigation.canGoBack !== false);
      if (canGoBack) {
        event.preventDefault();
        window.history.back();
      }
    });
  }

  const venueDataEl = document.getElementById("venue-data");
  const durationInput = document.getElementById("duration");
  const estimateBox = document.getElementById("estimate-lines");

  if (venueDataEl && durationInput && estimateBox) {
    const data = JSON.parse(venueDataEl.textContent);
    const warning = document.getElementById("duration-warning");

    const minus = document.getElementById("duration-minus");
    const plus = document.getElementById("duration-plus");

    if (minus) minus.addEventListener("click", () => setDuration(Number(durationInput.value) - 1));
    if (plus) plus.addEventListener("click", () => setDuration(Number(durationInput.value) + 1));
    durationInput.addEventListener("input", () => renderEstimate());
    durationInput.addEventListener("change", () => renderEstimate());

    document.querySelectorAll("[data-service]").forEach((cb) => {
      cb.addEventListener("change", renderEstimate);
    });

    function setDuration(value) {
      const next = Math.max(1, Math.min(24, Number(value) || 1));
      durationInput.value = String(next);
      renderEstimate();
    }

    function renderEstimate() {
      const dur = Math.max(1, Number(durationInput.value) || 1);
      let total = data.harga * dur;
      let lines = [{ label: "Sewa gedung × " + dur + " jam", value: data.harga * dur }];

      document.querySelectorAll("[data-service]").forEach((cb) => {
        if (!cb.checked) return;
        const index = Number(cb.dataset.service);
        const service = data.layanan[index];
        if (!service) return;
        const value = service.satuan === "per_jam" ? service.harga * dur : service.harga;
        total += value;
        lines.push({ label: service.nama, value: value });
      });

      if (warning) warning.hidden = dur >= data.minimal;

      let html = "";
      lines.forEach((line) => {
        html +=
          '<div class="line-item"><span>' + line.label + "</span><strong>" +
          rupiah(line.value) + "</strong></div>";
      });
      html +=
        '<div class="total"><span>Total estimasi</span><strong id="estimate-total">' +
        rupiah(total) + "</strong></div>";

      estimateBox.innerHTML = html;
    }

    renderEstimate();
  }

  /* -------------------------------------------------------------------
     Inisialisasi
     ------------------------------------------------------------------- */
  syncCompareUI();
})();
