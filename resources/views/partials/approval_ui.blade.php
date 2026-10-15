{{--
    ============================================================================
    Popup hasil approval laporan — sederhana & ringan (SDM & Manager)
    ----------------------------------------------------------------------------
    Prinsip:
      - TANPA blur pada background (tidak mengubah tampilan tabel di belakang).
      - Popup kecil (~340px), sudut 18px, border tipis, bayangan lembut.
      - Mengenai aksi: TIDAK ada modal konfirmasi. Tombol Setujui langsung diproses;
        tombol Tolak membuka input alasan (karena wajib) lalu popup yang sama
        berubah menjadi hasil — sehingga SATU popup per aksi.
      - Popup hasil HANYA muncul setelah server sukses; gagal -> popup error ringkas.
    Mandiri (CSS + JS custom) — tanpa dependency baru. Butuh BoxIcons (sudah ada).
    ============================================================================
--}}
<style>
    .lg-overlay {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, .16);
        opacity: 0;
        visibility: hidden;
        transition: opacity .18s ease, visibility .18s ease;
    }
    .lg-overlay.is-open { opacity: 1; visibility: visible; }

    .lg-overlay[data-theme="approved"] {
        --lg-accent: #0d9488;
        --lg-accent-2: #10b981;
        --lg-ring: rgba(13, 148, 136, .18);
    }
    .lg-overlay[data-theme="rejected"],
    .lg-overlay[data-theme="error"] {
        --lg-accent: #dc2626;
        --lg-accent-2: #ef4444;
        --lg-ring: rgba(220, 38, 38, .16);
    }

    .lg-modal {
        position: relative;
        width: min(92vw, 340px);
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
        padding: 1.25rem 1.15rem 1.05rem;
        border-radius: 18px;
        text-align: center;
        background: rgba(255, 255, 255, .97);
        border: 1px solid rgba(226, 232, 240, .9);
        box-shadow: 0 18px 40px -18px rgba(15, 23, 42, .40), 0 6px 16px -10px rgba(15, 23, 42, .18);
        transform: translateY(6px) scale(.98);
        opacity: 0;
        transition: transform .2s ease, opacity .2s ease;
    }
    .lg-overlay.is-open .lg-modal { transform: translateY(0) scale(1); opacity: 1; }

    .lg-icon {
        width: 44px;
        height: 44px;
        margin: 0 auto .7rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, var(--lg-accent-2), var(--lg-accent));
    }
    .lg-icon i { font-size: 1.3rem; line-height: 1; }
    .lg-icon svg { width: 1.4rem; height: 1.4rem; display: block; }

    .lg-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 .3rem;
    }
    .lg-desc {
        font-size: .82rem;
        line-height: 1.5;
        color: #64748b;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .lg-textarea {
        display: block;
        width: 100%;
        min-height: 72px;
        margin-top: .8rem;
        padding: .6rem .7rem;
        border-radius: 12px;
        border: 1px solid #dbe2ec;
        background: #fff;
        color: #0f172a;
        font-size: .82rem;
        line-height: 1.45;
        resize: vertical;
    }
    .lg-textarea::placeholder { color: #9aa7bd; }
    .lg-textarea:focus {
        outline: none;
        border-color: var(--lg-accent);
        box-shadow: 0 0 0 3px var(--lg-ring);
    }

    .lg-error {
        display: none;
        margin-top: .5rem;
        font-size: .76rem;
        font-weight: 600;
        color: #dc2626;
        text-align: left;
    }
    .lg-error.is-show { display: block; }

    .lg-actions {
        display: flex;
        justify-content: center;
        gap: .5rem;
        margin-top: 1rem;
    }
    .lg-btn {
        border: none;
        border-radius: 999px;
        padding: .55rem 1.25rem;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        transition: background .16s ease, box-shadow .16s ease, transform .12s ease;
    }
    .lg-btn:active { transform: translateY(1px); }
    .lg-btn:disabled { opacity: .7; cursor: default; }
    .lg-btn--ok {
        color: #fff;
        background: linear-gradient(135deg, var(--lg-accent-2), var(--lg-accent));
        box-shadow: 0 8px 18px -10px var(--lg-accent);
    }
    .lg-btn--ghost {
        color: #475569;
        background: #eef2f7;
    }
    .lg-btn--ghost:hover { background: #e2e8f0; }

    .lg-spin {
        display: inline-block;
        width: .8rem;
        height: .8rem;
        vertical-align: -1px;
        border: 2px solid rgba(255, 255, 255, .55);
        border-top-color: #fff;
        border-radius: 50%;
        animation: lgSpin .6s linear infinite;
    }
    @keyframes lgSpin { to { transform: rotate(360deg); } }

    @media (max-width: 420px) {
        .lg-modal { width: min(94vw, 320px); padding: 1.1rem 1rem .95rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lg-overlay, .lg-modal { transition: none !important; }
        .lg-modal { transform: none; }
    }
</style>

<script>
(function () {
    "use strict";

    var ICONS = {
        check: '<i class="bx bx-check"></i>',
        warning: '<svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" aria-hidden="true"><path d="M11.13 3.5a1 1 0 0 1 1.74 0l9.53 16.5a1 1 0 0 1-.87 1.5H2.47a1 1 0 0 1-.87-1.5L11.13 3.5Z M12 8.5a1 1 0 0 0-1 1v4.4a1 1 0 1 0 2 0V9.5a1 1 0 0 0-1-1Z M12 15.7a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z"/></svg>'
    };
    var RESULT = {
        approved: { theme: "approved", icon: "check",   title: "Laporan Disetujui!" },
        rejected: { theme: "rejected", icon: "warning", title: "Laporan Ditolak!" },
        error:    { theme: "error",    icon: "warning", title: "Gagal" },
        deleted:  { theme: "approved", icon: "check",   title: "Berhasil Dihapus!" }
    };

    // Peta aksi untuk form .js-approval-form (Laporan, Izin/Sakit, Hapus, Master Data).
    // reason  : tampilkan input alasan sebelum kirim (Laporan)
    // confirm : tampilkan popup konfirmasi Batal/Hapus sebelum kirim (hapus)
    // reload  : muat ulang halaman setelah user menutup popup hasil (aksi massal)
    var ACTIONS = {
        approve:         { state: "approved", reason: false, confirm: false },
        reject:          { state: "rejected", reason: true,  confirm: false },
        "reject-direct": { state: "rejected", reason: false, confirm: false },
        "approve-all":   { state: "approved", reason: false, confirm: true, reload: true, loadingText: "Menyetujui..." },
        delete:          { state: "deleted",  reason: false, confirm: true,  loadingText: "Menghapus..." },
        "delete-bulk":   { state: "deleted",  reason: false, confirm: true,  reload: true, loadingText: "Menghapus..." }
    };

    function esc(s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    var els = null;
    var st = { open: false, mode: "result", resolver: null };

    function build() {
        if (els) return;
        var root = document.createElement("div");
        root.className = "lg-overlay";
        root.setAttribute("aria-hidden", "true");
        root.innerHTML =
            '<div class="lg-modal" role="dialog" aria-modal="true">' +
                '<div class="lg-icon" data-lg-icon></div>' +
                '<h3 class="lg-title" data-lg-title></h3>' +
                '<p class="lg-desc" data-lg-desc></p>' +
                '<textarea class="lg-textarea" data-lg-textarea maxlength="500" rows="3" placeholder="Tulis alasan penolakan..."></textarea>' +
                '<div class="lg-error" data-lg-error></div>' +
                '<div class="lg-actions">' +
                    '<button type="button" class="lg-btn lg-btn--ghost" data-lg-cancel>Batal</button>' +
                    '<button type="button" class="lg-btn lg-btn--ok" data-lg-ok>OK</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(root);

        els = {
            root: root,
            icon: root.querySelector("[data-lg-icon]"),
            title: root.querySelector("[data-lg-title]"),
            desc: root.querySelector("[data-lg-desc]"),
            textarea: root.querySelector("[data-lg-textarea]"),
            error: root.querySelector("[data-lg-error]"),
            cancel: root.querySelector("[data-lg-cancel]"),
            ok: root.querySelector("[data-lg-ok]")
        };

        els.cancel.addEventListener("click", onCancel);
        els.ok.addEventListener("click", onOk);
        root.addEventListener("mousedown", function (e) { if (e.target === root) onCancel(); });
        document.addEventListener("keydown", function (e) { if (st.open && e.key === "Escape") onCancel(); });
    }

    function openModal(theme, icon, title, desc, opts) {
        build();
        opts = opts || {};
        els.root.setAttribute("data-theme", theme);
        els.icon.innerHTML = ICONS[icon] || ICONS.check;
        els.title.textContent = title || "";
        els.desc.textContent = desc || "";
        els.desc.style.display = desc ? "" : "none";

        st.mode = opts.mode || "result";
        st.lockLabel = opts.loadingText || "Memproses...";
        st.reload = false;
        var withReason = !!opts.withReason;
        els.textarea.style.display = withReason ? "" : "none";
        els.textarea.value = "";
        els.error.textContent = "";
        els.error.classList.remove("is-show");
        els.cancel.style.display = opts.showCancel ? "" : "none";
        els.cancel.disabled = false;
        if (opts.cancelText) els.cancel.textContent = opts.cancelText;
        els.ok.disabled = false;
        els.ok.innerHTML = opts.okText || "OK";

        els.root.classList.add("is-open");
        els.root.setAttribute("aria-hidden", "false");

        return new Promise(function (resolve) {
            st.resolver = resolve;
            st.open = true;
            window.setTimeout(function () {
                try { (withReason ? els.textarea : els.ok).focus(); } catch (e) {}
            }, 60);
        });
    }

    function hide() {
        st.open = false;
        els.root.classList.remove("is-open");
        els.root.setAttribute("aria-hidden", "true");
    }

    function settle(payload) {
        var r = st.resolver;
        st.resolver = null;
        if (r) r(payload);
    }

    function onCancel() {
        if (!st.open || st.mode === "locked") return;
        hide();
        settle({ confirmed: false });
    }

    function onOk() {
        if (!st.open || st.mode === "locked") return;
        if (st.mode === "reason") {
            var v = (els.textarea.value || "").trim();
            if (!v) {
                els.error.textContent = "Alasan penolakan wajib diisi.";
                els.error.classList.add("is-show");
                els.textarea.focus();
                return;
            }
            st.mode = "locked";
            els.ok.disabled = true;
            els.cancel.disabled = true;
            els.ok.innerHTML = '<span class="lg-spin"></span> Menolak...';
            settle({ confirmed: true, reason: v });
            return;
        }
        if (st.mode === "confirm") {
            st.mode = "locked";
            els.ok.disabled = true;
            els.cancel.disabled = true;
            els.ok.innerHTML = '<span class="lg-spin"></span> ' + (st.lockLabel || "Memproses...");
            settle({ confirmed: true });
            return;
        }
        if (st.reload) { window.location.reload(); return; }
        hide();
        settle({ confirmed: true });
    }

    function renderResult(state, message, opts) {
        build();
        opts = opts || {};
        var cfg = RESULT[state] || RESULT.error;
        els.root.setAttribute("data-theme", cfg.theme);
        els.icon.innerHTML = ICONS[cfg.icon] || ICONS.check;
        els.title.textContent = opts.title || cfg.title;
        els.desc.textContent = message || "";
        els.desc.style.display = message ? "" : "none";
        els.textarea.style.display = "none";
        els.error.textContent = "";
        els.error.classList.remove("is-show");
        els.cancel.style.display = "none";
        els.cancel.disabled = false;
        els.ok.disabled = false;
        els.ok.innerHTML = "OK";
        st.mode = "result";
        st.reload = !!opts.reload;
        st.open = true;
        st.resolver = null;
        els.root.classList.add("is-open");
        els.root.setAttribute("aria-hidden", "false");
        setTimeout(function () { try { els.ok.focus(); } catch (e) {} }, 40);
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute("content");
        var input = document.querySelector('form.js-approval-form input[name="_token"]');
        return input ? input.value : "";
    }

    function postForm(form, reason) {
        var fd = new FormData(form);
        if (reason != null) fd.set("keterangan_tolak", reason);
        return fetch(form.action, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf(),
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: fd,
            credentials: "same-origin"
        })
        .then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                return { ok: r.ok, data: data };
            });
        })
        .then(function (res) {
            if (res.ok && res.data && res.data.success) return res.data;
            throw new Error((res.data && res.data.message) || "Terjadi kesalahan. Silakan coba lagi.");
        });
    }

    function setLoading(btn, label) {
        if (!btn) return;
        if (btn.dataset._orig == null) btn.dataset._orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="lg-spin"></span> ' + (label || "Memproses...");
    }
    function restore(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset._orig != null) { btn.innerHTML = btn.dataset._orig; btn.dataset._orig = ""; }
    }

    function afterSuccess(form) {
        try {
            var deltas = JSON.parse(form.dataset.counters || "{}");
            Object.keys(deltas).forEach(function (key) {
                var el = document.querySelector('[data-counter="' + key + '"]');
                if (!el) return;
                var val = parseInt((el.textContent || "0").replace(/[^\d-]/g, ""), 10) || 0;
                el.textContent = String(val + deltas[key]);
            });
        } catch (e) {}

        var row = form.closest("tr");
        var tbody = form.closest("tbody");
        if (row) {
            var table = form.closest("table");
            row.style.transition = "opacity .25s ease";
            row.style.opacity = "0";
            window.setTimeout(function () {
                // Jika tabel dikelola DataTables, gunakan API agar indeks/paging tetap sinkron.
                try {
                    if (table && window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable
                        && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().row(window.jQuery(row)).remove().draw(false);
                    } else {
                        row.remove();
                    }
                } catch (e) {
                    row.remove();
                }
                checkEmpty(tbody);
            }, 250);
        }
    }

    function checkEmpty(tbody) {
        if (!tbody || !tbody.querySelector) return;
        if (tbody.querySelector("tr[data-lg-row]") || tbody.querySelector("tr[data-laporan-row]") || tbody.querySelector("tr.pkl-empty")) return;
        var colspan = tbody.dataset.emptyColspan || "8";
        var text = tbody.dataset.emptyText || "Tidak ada data.";
        var tr = document.createElement("tr");
        tr.className = "pkl-empty";
        tr.innerHTML = '<td colspan="' + esc(colspan) + '" class="text-center text-muted py-4">' + esc(text) + '</td>';
        tbody.appendChild(tr);
    }

    function submitAction(form, cfg, reason) {
        var btn = form.querySelector('button[type="submit"]');
        setLoading(btn, cfg.loadingText || "Memproses...");
        postForm(form, reason).then(function (data) {
            var msg = (data && data.message) || form.dataset.message || "Berhasil.";
            renderResult(cfg.state, msg, {
                title: form.dataset.title || "",
                reload: cfg.reload
            });
            afterSuccess(form);
        }).catch(function (err) {
            restore(btn);
            form.dataset.busy = "0";
            renderResult("error", (err && err.message) || "Terjadi kesalahan.");
        });
    }

    function runAction(form) {
        var cfg = ACTIONS[form.dataset.action] || ACTIONS.approve;

        var proceed = function () {
            if (cfg.reason) {
                LiquidGlass.reason({
                    title: form.dataset.reasonTitle || undefined,
                    desc: form.dataset.reasonDesc || undefined,
                    okText: form.dataset.reasonOk || undefined
                }).then(function (res) {
                    if (!res || !res.confirmed) { form.dataset.busy = "0"; return; }
                    submitAction(form, cfg, res.reason);
                });
                return;
            }

            if (cfg.confirm) {
                LiquidGlass.confirm({
                    title: form.dataset.confirmTitle || "Konfirmasi",
                    message: form.dataset.confirmMessage || "Lanjutkan tindakan ini?",
                    okText: form.dataset.confirmOk || "Ya",
                    theme: form.dataset.confirmTheme || "rejected",
                    icon: form.dataset.confirmIcon || "warning",
                    loadingText: cfg.loadingText
                }).then(function (res) {
                    if (!res || !res.confirmed) { form.dataset.busy = "0"; return; }
                    submitAction(form, cfg, null);
                });
                return;
            }

            submitAction(form, cfg, null);
        };

        proceed();
    }

    var LiquidGlass = {
        result: function (opts) {
            opts = opts || {};
            var cfg = RESULT[opts.state] || RESULT.approved;
            return openModal(cfg.theme, cfg.icon, opts.title || cfg.title, opts.message || "", {
                mode: "result", showCancel: false, okText: "OK"
            });
        },
        reason: function (opts) {
            opts = opts || {};
            return openModal("rejected", "warning", opts.title || "Alasan Penolakan",
                opts.desc || "Masukkan alasan penolakan untuk laporan peserta ini.", {
                mode: "reason", withReason: true, showCancel: true,
                cancelText: "Batal", okText: opts.okText || "Tolak"
            });
        },
        confirm: function (opts) {
            opts = opts || {};
            return openModal(opts.theme || "rejected", opts.icon || "warning",
                opts.title || "Konfirmasi", opts.message || "Lanjutkan tindakan ini?", {
                mode: "confirm", showCancel: true,
                cancelText: opts.cancelText || "Batal",
                okText: opts.okText || "Ya",
                loadingText: opts.loadingText || "Memproses..."
            });
        },
        renderResult: renderResult
    };
    window.LiquidGlass = LiquidGlass;

    document.addEventListener("submit", function (e) {
        var form = e.target;
        if (!form || !form.classList || !form.classList.contains("js-approval-form")) return;
        e.preventDefault();
        if (form.dataset.busy === "1") return;
        form.dataset.busy = "1";
        runAction(form);
    });

    @if (session('approval_result'))
    document.addEventListener("DOMContentLoaded", function () {
        if (!window.LiquidGlass) return;
        var s = @json(session('approval_result'));
        var nama = @json(session('approval_nama'));
        var msg = @json(session('approval_message'));
        var text = msg || (s === "approved"
            ? "Laporan " + (nama ? nama + " " : "") + "berhasil disetujui."
            : s === "rejected"
                ? "Laporan " + (nama ? nama + " " : "") + "berhasil ditolak."
                : "Terjadi kesalahan.");
        window.LiquidGlass.result({ state: s, message: text });
    });
    @endif
})();
</script>
