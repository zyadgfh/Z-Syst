/* ==========================================================================
   pos-keys.js — keyboard-first POS for Z-Syst
   Targets data-attribute hooks so it stays decoupled from Blade markup:
     [data-pos-focus]  field to focus for a new sale   → F2
     form[data-pos-pay] the payment/submit form        → F9 (submit)
     form[data-pos-hold] hold/park current sale        → F8 (submit)
     [data-pos-new]    "new sale" reset button         → Ctrl+Enter
     [data-pos-search] global product search field     → /  or Ctrl+K
   Shows a one-time hint chip the first time a shortcut fires.
   ========================================================================== */
(function () {
    'use strict';

    const HINT_KEY = 'ap-pos-keys-hint';

    function showHint(text) {
        if (localStorage.getItem(HINT_KEY)) return;
        const chip = document.createElement('div');
        chip.className = 'ap-pos-hint';
        chip.textContent = text;
        document.body.appendChild(chip);
        requestAnimationFrame(() => chip.classList.add('show'));
        setTimeout(() => {
            chip.classList.remove('show');
            setTimeout(() => chip.remove(), 400);
        }, 2600);
        try { localStorage.setItem(HINT_KEY, '1'); } catch {}
    }

    function first(sel) { return document.querySelector(sel); }

    function focusSale() {
        const el = first('[data-pos-focus]') || first('input[name="barcode"], input[type="search"], input[type="text"]');
        if (el) { el.focus(); el.select && el.select(); showHint('F2 — حقل البيع الجديد'); }
    }

    function submitForm(sel, label) {
        const form = first(sel);
        if (form) {
            showHint(label);
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        }
    }

    document.addEventListener('keydown', (e) => {
        const tag = (e.target && e.target.tagName || '').toLowerCase();
        const typing = tag === 'input' || tag === 'textarea' || tag === 'select';
        const inField = typing && !e.target.closest('[data-pos-focus]');

        switch (e.key) {
            case 'F2':
                e.preventDefault();
                focusSale();
                break;
            case 'F9':
                e.preventDefault();
                submitForm('form[data-pos-pay]', 'F9 — الدفع وإتمام البيع');
                break;
            case 'F8':
                e.preventDefault();
                submitForm('form[data-pos-hold]', 'F8 — تعليق الفاتورة');
                break;
            case 'Enter':
                if (e.ctrlKey) {
                    e.preventDefault();
                    const btn = first('[data-pos-new]');
                    if (btn) { btn.click(); showHint('Ctrl+Enter — فاتورة جديدة'); }
                }
                break;
            case '/':
                if (!inField && !typing) {
                    e.preventDefault();
                    const s = first('[data-pos-search]') || first('input[type="search"]');
                    if (s) { s.focus(); s.select && s.select(); showHint('/ — البحث عن منتج'); }
                }
                break;
            case 'k':
            case 'K':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    const s = first('[data-pos-search]') || first('input[type="search"]');
                    if (s) { s.focus(); s.select && s.select(); }
                }
                break;
        }
    });

    // Live barcode capture: any [data-pos-focus] field auto-submits its form
    // when a scanned value ends with Enter AND looks like a full barcode.
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        const el = e.target;
        if (!el.matches || !el.matches('[data-pos-autoscan]')) return;
        const val = (el.value || '').trim();
        if (val.length >= 4) el.form && el.form.requestSubmit && el.form.requestSubmit();
    });
})();
