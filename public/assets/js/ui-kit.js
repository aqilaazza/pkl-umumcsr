/* =====================================================================
   ui-kit.js — enhancement layer untuk komponen UI SDM.
   Mengekspos window.UI:
     UI.drawer.open({url, title, type})
     UI.bindConfirm()        -> [data-ui-confirm]
     UI.bindLoading()        -> spinner otomatis
     UI.bindSearchForms()    -> form[data-live-search] (submit manual)
     UI.bindClientFilter()   -> [data-ui-filter] + [data-ui-filter-apply]
     UI.bindDataTableSearch()-> DataTables: cari saat Enter/Cari
     UI.bindDateRange()      -> [data-daterange]
   Semua opt-in per halaman; tidak menyentuh logika controller/DB.
   ===================================================================== */
(function () {
    "use strict";

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function attr(el, name, fallback) {
        var v = el.getAttribute(name);
        return (v === null || v === "") ? (fallback || "") : v;
    }

    /* ---------------------------------------------------------------- */
    /* Drawer preview berkas                                            */
    /* ---------------------------------------------------------------- */
    var drawerEls = null;

    function buildDrawer() {
        if (drawerEls) return;
        var backdrop = document.createElement("div");
        backdrop.className = "ui-drawer-backdrop";
        backdrop.setAttribute("data-ui-drawer-backdrop", "");

        var panel = document.createElement("aside");
        panel.className = "ui-drawer";
        panel.setAttribute("role", "dialog");
        panel.setAttribute("aria-modal", "true");
        panel.setAttribute("aria-hidden", "true");
        panel.innerHTML =
            '<div class="ui-drawer__head">' +
                '<h3 class="ui-drawer__title" data-ui-drawer-title>Preview</h3>' +
                '<button type="button" class="ui-drawer__close" data-ui-drawer-close aria-label="Tutup">&times;</button>' +
            '</div>' +
            '<div class="ui-drawer__body" data-ui-drawer-body></div>' +
            '<div class="ui-drawer__foot">' +
                '<a class="btn btn-sm btn-outline-primary" data-ui-drawer-open target="_blank" rel="noopener">' +
                    '<i class="bx bx-link-external me-1"></i>Buka di tab baru</a>' +
            '</div>';

        document.body.appendChild(backdrop);
        document.body.appendChild(panel);

        drawerEls = {
            backdrop: backdrop,
            panel: panel,
            title: $("[data-ui-drawer-title]", panel),
            body: $("[data-ui-drawer-body]", panel),
            link: $("[data-ui-drawer-open]", panel)
        };

        backdrop.addEventListener("click", closeDrawer);
        $("[data-ui-drawer-close]", panel).addEventListener("click", closeDrawer);
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && panel.classList.contains("is-open")) closeDrawer();
        });
    }

    function extType(url) {
        var clean = String(url).split("?")[0].split("#")[0];
        var m = /\.([a-z0-9]+)$/i.exec(clean);
        return m ? m[1].toLowerCase() : "";
    }

    function openDrawer(opts) {
        opts = opts || {};
        if (!opts.url) return;
        buildDrawer();

        var type = (opts.type || extType(opts.url) || "").toLowerCase();
        drawerEls.title.textContent = opts.title || "Preview";
        drawerEls.link.setAttribute("href", opts.url);

        var body = drawerEls.body;
        body.innerHTML = "";

        if (type === "pdf") {
            var frame = document.createElement("iframe");
            frame.setAttribute("src", opts.url);
            frame.setAttribute("title", opts.title || "Preview PDF");
            body.appendChild(frame);
        } else if (["png", "jpg", "jpeg", "gif", "webp", "bmp", "svg"].indexOf(type) !== -1) {
            var img = document.createElement("img");
            img.setAttribute("src", opts.url);
            img.setAttribute("alt", opts.title || "Preview");
            body.appendChild(img);
        } else {
            var fallback = document.createElement("div");
            fallback.className = "ui-drawer__fallback";
            fallback.innerHTML =
                '<i class="bx bx-file"></i>' +
                '<p class="mb-0">Preview tidak tersedia untuk tipe berkas ini.</p>';
            body.appendChild(fallback);
        }

        drawerEls.backdrop.classList.add("is-open");
        drawerEls.panel.classList.add("is-open");
        drawerEls.panel.setAttribute("aria-hidden", "false");
        document.documentElement.style.overflow = "hidden";
    }

    function closeDrawer() {
        if (!drawerEls) return;
        drawerEls.backdrop.classList.remove("is-open");
        drawerEls.panel.classList.remove("is-open");
        drawerEls.panel.setAttribute("aria-hidden", "true");
        document.documentElement.style.overflow = "";
        window.setTimeout(function () {
            if (drawerEls && !drawerEls.panel.classList.contains("is-open")) {
                drawerEls.body.innerHTML = "";
            }
        }, 240);
    }

    /* ---------------------------------------------------------------- */
    /* Confirm popover untuk aksi non-form                              */
    /* ---------------------------------------------------------------- */
    function runAfterConfirm(el) {
        var targetSel = attr(el, "data-confirm-form");
        var form = null;
        if (targetSel) {
            form = $(targetSel.charAt(0) === "#" ? targetSel : "#" + targetSel);
        } else if (el.tagName === "BUTTON" && el.form) {
            form = el.form;
        }
        if (form) {
            if (form.requestSubmit) form.requestSubmit(); else form.submit();
            return;
        }
        var href = attr(el, "data-confirm-href") || el.getAttribute("href");
        if (href && href !== "#" && href.indexOf("javascript:") !== 0) {
            if (el.getAttribute("target") === "_blank") window.open(href, "_blank");
            else window.location.href = href;
        }
    }

    function bindConfirm() {
        document.addEventListener("click", function (e) {
            var el = e.target.closest("[data-ui-confirm]");
            if (!el) return;
            if (el.disabled) return;
            e.preventDefault();

            var message = attr(el, "data-ui-confirm", "Lanjutkan tindakan ini?");
            var opts = {
                title: attr(el, "data-confirm-title", "Konfirmasi"),
                message: message,
                okText: attr(el, "data-confirm-ok", "Ya"),
                theme: attr(el, "data-confirm-theme", "rejected"),
                icon: attr(el, "data-confirm-icon", "warning")
            };

            var lg = window.LiquidGlass;
            var proceed = lg && typeof lg.confirm === "function"
                ? lg.confirm(opts)
                : Promise.resolve({ confirmed: window.confirm(message) });

            proceed.then(function (res) {
                if (res && res.confirmed) runAfterConfirm(el);
            });
        });
    }

    /* ---------------------------------------------------------------- */
    /* Loading otomatis                                                 */
    /* ---------------------------------------------------------------- */
    function setLoading(btn, on) {
        if (!btn) return;
        if (on) {
            if (btn.dataset._uiOrig == null) btn.dataset._uiOrig = btn.innerHTML;
            btn.dataset._uiWasDisabled = btn.disabled ? "1" : "0";
            btn.classList.add("is-loading");
            btn.disabled = true;
            btn.innerHTML = '<span class="ui-spin"></span>' +
                (btn.dataset.uiLoadingText ? ' ' + btn.dataset.uiLoadingText : "");
        } else {
            btn.classList.remove("is-loading");
            if (btn.dataset._uiWasDisabled !== "1") btn.disabled = false;
            if (btn.dataset._uiOrig != null) {
                btn.innerHTML = btn.dataset._uiOrig;
                btn.dataset._uiOrig = "";
            }
        }
    }

    /* Deteksi aksi unduh/ekspor: file di-stream sebagai attachment
       sehingga halaman TIDAK reload. Tanpa auto-reset, spinner akan
       macet selamanya di tombol. */
    var DOWNLOAD_RE = /export|download|excel|xls|csv|\bpdf\b|cetak|print|sertifikat|backup|unduh|rekap/i;

    function isDownloadTrigger(el) {
        if (!el) return false;
        if (el.hasAttribute("data-download")) return true;
        if (el.hasAttribute("download")) return true;

        if (el.tagName === "A") {
            if (el.getAttribute("target") === "_blank") return true;
            if (DOWNLOAD_RE.test(el.getAttribute("href") || "")) return true;
        }

        var form = el.form || (el.closest ? el.closest("form") : null);
        if (form) {
            if (form.hasAttribute("data-download")) return true;
            if (DOWNLOAD_RE.test(form.getAttribute("action") || "")) return true;
        }
        return false;
    }

    /* Tampilkan spinner sebentar lalu pulihkan otomatis (unduhan tidak
       memicu navigasi/pageshow). */
    function flashLoading(el, ms) {
        setLoading(el, true);
        window.setTimeout(function () { setLoading(el, false); }, ms || 2500);
    }

    function bindLoading() {
        document.addEventListener("submit", function (e) {
            var form = e.target;
            if (!form || form.classList.contains("js-approval-form")) return;
            if (form.hasAttribute("data-ui-no-loading")) return;
            var btn = form.querySelector('button[type="submit"]:not([form]), input[type="submit"]');
            if (!btn) btn = e.submitter || null;
            if (isDownloadTrigger(form) || isDownloadTrigger(btn)) flashLoading(btn, 2500);
            else setLoading(btn, true);
        });

        document.addEventListener("click", function (e) {
            var el = e.target.closest("[data-ui-loading]");
            if (!el) return;
            if (isDownloadTrigger(el)) flashLoading(el, 2500);
            else setLoading(el, true);
        });

        window.addEventListener("pageshow", function () {
            $$(".is-loading").forEach(function (el) {
                if (el.tagName === "BUTTON" || el.tagName === "INPUT") setLoading(el, false);
                else el.classList.remove("is-loading");
            });
        });
    }

    /* ---------------------------------------------------------------- */
    /* Penandaan tombol aksi (ikon-tunggal vs ikon + teks)              */
    /* ---------------------------------------------------------------- */
    function markActionButton(btn) {
        if (!btn || !btn.classList || !btn.classList.contains("btn")) return;
        var hasText = (btn.textContent || "").replace(/\s+/g, "") !== "";
        if (hasText) {
            btn.classList.add("ui-btn-pill");
            btn.classList.remove("ui-btn-icon-only");
        } else {
            btn.classList.add("ui-btn-icon-only");
            btn.classList.remove("ui-btn-pill");
        }
    }

    function markAllActionButtons(root) {
        $$(".ui-actions .btn, .ui-table td .btn.btn-sm", root || document)
            .forEach(markActionButton);
    }

    function bindActionButtons() {
        markAllActionButtons(document);

        if (window.MutationObserver) {
            var scheduled = false;
            var mo = new MutationObserver(function () {
                if (scheduled) return;
                scheduled = true;
                window.requestAnimationFrame(function () {
                    scheduled = false;
                    markAllActionButtons(document);
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });
        }
    }

    /* ---------------------------------------------------------------- */
    /* Form pencarian (submit manual: Enter atau tombol Filter/Cari)    */
    /* Tidak ada pencarian otomatis saat mengetik/mengubah input.       */
    /* ---------------------------------------------------------------- */
    var FOCUS_KEY = "uiSearchFocus";

    function bindSearchForms() {
        $$("form[data-live-search]").forEach(function (form) {
            if (form.dataset.uiLiveBound) return;
            form.dataset.uiLiveBound = "1";

            // Simpan nama field yang difokus agar bisa dipulihkan setelah reload.
            $$("input, select", form).forEach(function (field) {
                if (!field.name) return;
                field.addEventListener("focus", function () {
                    try { sessionStorage.setItem(FOCUS_KEY, field.name); } catch (e) {}
                });
            });

            form.addEventListener("submit", function () {
                var box = form.querySelector(".ui-search");
                if (box) box.setAttribute("data-loading", "1");
            });
        });

        // Kembalikan fokus ke kolom pencarian setelah reload.
        var focusName = null;
        try { focusName = sessionStorage.getItem(FOCUS_KEY); } catch (e) {}
        if (focusName) {
            try { sessionStorage.removeItem(FOCUS_KEY); } catch (e) {}
            var field = document.querySelector('form[data-live-search] [name="' + focusName + '"]');
            if (field) {
                field.focus();
                try {
                    var len = field.value ? field.value.length : 0;
                    if (field.setSelectionRange) field.setSelectionRange(len, len);
                } catch (e) {}
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /* Date range picker                                                */
    /* ---------------------------------------------------------------- */
    function fmtDate(d) {
        var m = String(d.getMonth() + 1).padStart(2, "0");
        var day = String(d.getDate()).padStart(2, "0");
        return d.getFullYear() + "-" + m + "-" + day;
    }

    function presetRange(key) {
        var now = new Date();
        var from = new Date(now), to = new Date(now);
        if (key === "today") { /* both = today */ }
        else if (key === "7d") { from.setDate(now.getDate() - 6); }
        else if (key === "30d") { from.setDate(now.getDate() - 29); }
        else if (key === "month") { from = new Date(now.getFullYear(), now.getMonth(), 1); }
        return { from: fmtDate(from), to: fmtDate(to) };
    }

    function bindDateRange() {
        $$("[data-daterange]").forEach(function (box) {
            if (box.dataset.uiDrBound) return;
            box.dataset.uiDrBound = "1";

            var from = box.querySelector('input[name="$from"], input[data-role="from"], input[type=date]');
            var dates = $$("input[type=date]", box);
            var to = box.querySelector('input[name="$to"], input[data-role="to"]') ||
                dates[dates.length > 1 ? 1 : 0];

            if ($$(".ui-daterange-presets", box).length === 0) {
                var presets = document.createElement("span");
                presets.className = "ui-daterange-presets";
                [
                    ["today", "Hari ini"],
                    ["7d", "7 hari"],
                    ["30d", "30 hari"],
                    ["month", "Bulan ini"]
                ].forEach(function (p) {
                    var b = document.createElement("button");
                    b.type = "button";
                    b.className = "ui-preset";
                    b.textContent = p[1];
                    b.dataset.preset = p[0];
                    presets.appendChild(b);
                });
                box.appendChild(presets);
            }

            box.addEventListener("click", function (e) {
                var btn = e.target.closest(".ui-preset");
                if (!btn) return;
                var r = presetRange(btn.dataset.preset);
                if (from) from.value = r.from;
                if (to) to.value = r.to;
                var form = box.closest("form[data-live-search]");
                if (form) {
                    if (form.requestSubmit) form.requestSubmit(); else form.submit();
                }
            });
        });
    }

    /* ---------------------------------------------------------------- */
    /* Auto-bind drawer ke tautan preview                               */
    /* ---------------------------------------------------------------- */
    function bindPreviewLinks() {
        document.addEventListener("click", function (e) {
            var link = e.target.closest("[data-ui-preview]");
            if (!link) return;
            var url = link.getAttribute("href");
            if (!url) return;
            e.preventDefault();
            openDrawer({
                url: url,
                title: attr(link, "data-preview-title", link.getAttribute("title") || "Preview"),
                type: attr(link, "data-preview-type", "")
            });
        });
    }

    /* ---------------------------------------------------------------- */
    /* Filter client-side untuk daftar non-paginasi (master data).      */
    /* Dijalankan hanya saat Enter ditekan atau tombol Cari diklik.     */
    /* ---------------------------------------------------------------- */
    function filterTable(selector, query) {
        var table = $(selector);
        if (!table || !table.tBodies || !table.tBodies[0]) return;
        var tbody = table.tBodies[0];
        var rows = $$("tr", tbody).filter(function (tr) {
            return !tr.classList.contains("pkl-empty");
        });
        var empty = tbody.querySelector(".pkl-empty");

        var q = (query || "").trim().toLowerCase();
        var shown = 0;
        rows.forEach(function (tr) {
            var hit = q === "" || tr.textContent.toLowerCase().indexOf(q) !== -1;
            tr.style.display = hit ? "" : "none";
            if (hit) shown++;
        });
        if (empty) empty.style.display = shown === 0 ? "" : "none";
    }

    function bindClientFilter() {
        $$("[data-ui-filter]").forEach(function (inp) {
            if (inp.dataset.uiFilterBound) return;
            inp.dataset.uiFilterBound = "1";
            inp.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    filterTable(inp.dataset.uiFilter, inp.value);
                }
            });
        });

        // Tombol Cari pasangan input (data-ui-filter-apply="#tabelX").
        $$("[data-ui-filter-apply]").forEach(function (btn) {
            if (btn.dataset.uiFilterBtnBound) return;
            btn.dataset.uiFilterBtnBound = "1";
            btn.addEventListener("click", function () {
                var sel = btn.dataset.uiFilterApply;
                var inp = $('[data-ui-filter="' + sel + '"]');
                filterTable(sel, inp ? inp.value : "");
            });
        });
    }

    /* ---------------------------------------------------------------- */
    /* DataTables: pencarian hanya saat Enter atau tombol Cari diklik   */
    /* (matikan pencarian realtime bawaan DataTables).                  */
    /* ---------------------------------------------------------------- */
    function enhanceDataTableSearch(settings) {
        var jq = window.jQuery;
        if (!jq || !jq.fn || !jq.fn.dataTable) return;

        var api = new jq.fn.dataTable.Api(settings);
        var wrapper = jq(settings.nTableWrapper || api.table().container());
        var input = wrapper.find('input[type="search"]');
        if (!input.length || input.data("uiEnterBound")) return;
        input.data("uiEnterBound", true);

        // Hapus handler realtime bawaan DataTables (namespace .DT).
        input.off("keyup.DT input.DT change.DT keydown.DT");
        input.on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                api.search(this.value).draw();
            }
        });

        if (!wrapper.find(".ui-dt-search-btn").length) {
            var btn = jq('<button type="button" class="btn btn-sm btn-primary ui-dt-search-btn ms-1">' +
                '<i class="bx bx-search"></i> Cari</button>');
            btn.on("click", function () {
                api.search(input.val()).draw();
            });
            input.after(btn);
        }
    }

    function bindDataTableSearch() {
        var jq = window.jQuery;
        if (!jq || !jq.fn || !jq.fn.dataTable) return;

        // Tangani tabel yang sudah ter-inisialisasi.
        jq.fn.dataTable.tables({ api: true }).each(function () {
            enhanceDataTableSearch(this.settings()[0]);
        });
        // Tangani tabel yang diinisialisasi setelah baris ini.
        jq(document).on("init.dt", function (e, settings) {
            enhanceDataTableSearch(settings);
        });
    }

    // Pasang segera (jQuery sudah dimuat sebelum ui-kit.js) agar listener
    // init.dt terdaftar sebelum DataTables diinisialisasi oleh view.
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
        window.jQuery(document).on("init.dt", function (e, settings) {
            enhanceDataTableSearch(settings);
        });
    }

    /* ---------------------------------------------------------------- */
    function init() {
        bindConfirm();
        bindLoading();
        bindActionButtons();
        bindSearchForms();
        bindDateRange();
        bindPreviewLinks();
        bindClientFilter();
        bindDataTableSearch();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    window.UI = {
        drawer: { open: openDrawer, close: closeDrawer },
        bindConfirm: bindConfirm,
        bindLoading: bindLoading,
        bindSearchForms: bindSearchForms,
        bindDateRange: bindDateRange,
        bindClientFilter: bindClientFilter,
        bindDataTableSearch: bindDataTableSearch,
        markActionButtons: markAllActionButtons,
        init: init
    };
})();
