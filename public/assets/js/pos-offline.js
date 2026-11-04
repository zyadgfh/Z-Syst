/* ==========================================================================
   pos-offline.js — offline sale queue for Z-Syst POS
   - Intercepts POST /api/v1/sales while offline: stores the payload in
     IndexedDB and resolves with a synthetic 202 so the POS keeps flowing.
   - Flushes the queue automatically on 'online' (oldest first), replaying
     the exact Authorization header captured with each sale.
   - Exposes window.PosOffline.count()/flush() and dispatches:
       'pos-offline:queued' | 'pos-offline:synced' | 'pos-offline:error'
   ========================================================================== */
(function () {
    'use strict';

    const DB_NAME = 'zsyst';
    const STORE = 'pending_sales';
    const SALES_URL = '/api/v1/sales';

    function openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = () => {
                const db = req.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    function tx(db, mode, fn) {
        return new Promise((resolve, reject) => {
            const t = db.transaction(STORE, mode);
            const out = fn(t.objectStore(STORE));
            t.oncomplete = () => resolve(out && out.result !== undefined ? out.result : out);
            t.onerror = () => reject(t.error);
        });
    }

    const emit = (name, detail) =>
        window.dispatchEvent(new CustomEvent(name, { detail }));

    async function enqueue(payload, headers) {
        const db = await openDb();
        await tx(db, 'readwrite', (s) => s.add({
            payload, headers, created_at: Date.now(), tries: 0,
        }));
        emit('pos-offline:queued', { size: await count() });
    }

    async function count() {
        const db = await openDb();
        return tx(db, 'readonly', (s) => s.count());
    }

    async function all() {
        const db = await openDb();
        return tx(db, 'readonly', (s) => s.getAll());
    }

    async function remove(id) {
        const db = await openDb();
        return tx(db, 'readwrite', (s) => s.delete(id));
    }

    let flushing = false;
    async function flush() {
        if (flushing || !navigator.onLine) return;
        flushing = true;
        try {
            const items = await all();
            for (const item of items) {
                try {
                    const res = await fetch(SALES_URL, {
                        method: 'POST',
                        headers: Object.assign(
                            { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            item.headers || {}
                        ),
                        body: JSON.stringify(item.payload),
                    });
                    if (res.ok) {
                        await remove(item.id);
                        emit('pos-offline:synced', { remaining: await count(), sale: item.payload });
                    } else if (res.status === 401 || res.status === 422) {
                        // Unrecoverable for this item: keep it for manual review.
                        emit('pos-offline:error', { id: item.id, status: res.status });
                        break;
                    }
                } catch (e) {
                    break; // still offline — stop and retry later
                }
            }
        } finally {
            flushing = false;
        }
    }

    // ---- fetch interceptor: queue sales POSTs while offline ----------------
    const originalFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        const method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
        const isSale = method === 'POST' && url.includes(SALES_URL);

        if (isSale && !navigator.onLine) {
            const headers = {};
            const src = (init && init.headers) || (input && input.headers) || {};
            if (src instanceof Headers) {
                src.forEach((v, k) => { headers[k] = v; });
            } else {
                Object.assign(headers, src);
            }
            const payload = JSON.parse((init && init.body) || '{}');
            await enqueue(payload, headers);
            return new Response(JSON.stringify({
                message: 'Queued offline — will sync automatically.',
                queued: true, pending: await count(),
            }), { status: 202, headers: { 'Content-Type': 'application/json' } });
        }
        return originalFetch(input, init);
    };

    window.addEventListener('online', flush);
    window.PosOffline = { count, flush, all };

    // Late sync attempt on load (page restored while back online).
    if (navigator.onLine) setTimeout(flush, 1500);
})();
