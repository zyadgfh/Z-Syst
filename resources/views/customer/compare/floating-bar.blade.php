@php
    $compareIds = session('compare_ids', []);
@endphp

<div id="compare-bar" style="position: fixed; bottom: 0; {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 0; {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 0; background: rgba(255,255,255,0.92); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border-top: 1px solid rgba(0,0,0,0.08); padding: 12px 24px; display: {{ count($compareIds) > 0 ? 'flex' : 'none' }}; align-items: center; justify-content: center; gap: 16px; z-index: 9999; transform: translateY(100%); transition: transform 300ms cubic-bezier(0.32, 0.72, 0, 1); box-shadow: 0 -4px 20px rgba(0,0,0,0.08);"
     id="compare-floating-bar">
    <span style="font-size: 14px; font-weight: 500; color: #424245;">
        <span id="compare-count">{{ count($compareIds) }}</span>/4 منتجات محددة
    </span>

    <a href="{{ route('compare.index', ['ids' => $compareIds]) }}"
       id="compare-go-btn"
       style="padding: 10px 24px; background: #007aff; color: #fff; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; transition: transform 150ms ease; display: {{ count($compareIds) >= 2 ? 'inline-flex' : 'none' }}; align-items: center; gap: 6px;"
       onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
        مقارنة الآن
    </a>

    <button onclick="clearCompare()" style="padding: 8px 16px; background: transparent; color: #ff3b30; border: 1px solid #ff3b30; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 150ms ease;" onmouseenter="this.style.background='#fff0f0'" onmouseleave="this.style.background='transparent'">
        مسح الكل
    </button>
</div>

<script>
(function() {
    if (window._compareBarInit) return;
    window._compareBarInit = true;

    window.toggleCompare = function(btn, productId) {
        const isActive = btn.classList.contains('compare-active');

        if (isActive) {
            fetch('/compare/remove/' + productId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            }).then(r => r.json()).then(data => {
                btn.classList.remove('compare-active');
                btn.style.background = 'transparent';
                btn.style.borderColor = '#d2d2d7';
                btn.querySelector('svg').setAttribute('stroke', '#86868b');
                updateCompareBar(data.count);
            });
        } else {
            fetch('/compare/add/' + productId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            }).then(r => r.json()).then(data => {
                if (data.count <= 4) {
                    btn.classList.add('compare-active');
                    btn.style.background = '#e3f2fd';
                    btn.style.borderColor = '#007aff';
                    btn.querySelector('svg').setAttribute('stroke', '#007aff');
                }
                updateCompareBar(data.count);
                showToast(data.message);
            });
        }
    };

    function updateCompareBar(count) {
        const bar = document.getElementById('compare-floating-bar');
        const countEl = document.getElementById('compare-count');
        const goBtn = document.getElementById('compare-go-btn');

        if (bar) {
            bar.style.display = count > 0 ? 'flex' : 'none';
            if (count > 0) {
                requestAnimationFrame(() => bar.style.transform = 'translateY(0)');
            } else {
                bar.style.transform = 'translateY(100%)';
            }
        }
        if (countEl) countEl.textContent = count;
        if (goBtn) goBtn.style.display = count >= 2 ? 'inline-flex' : 'none';

        // Update URL for go button
        if (goBtn) {
            const ids = [];
            document.querySelectorAll('.compare-active').forEach(el => {
                ids.push(el.dataset.productId);
            });
            goBtn.href = '{{ url("compare") }}?ids[]=' + ids.join('&ids[]=');
        }
    }

    window.clearCompare = function() {
        fetch('/compare/clear', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        }).then(() => {
            document.querySelectorAll('.compare-active').forEach(el => {
                el.classList.remove('compare-active');
                el.style.background = 'transparent';
                el.style.borderColor = '#d2d2d7';
                el.querySelector('svg').setAttribute('stroke', '#86868b');
            });
            updateCompareBar(0);
        });
    };

    function showToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(10px); background: #1d1d1f; color: #fff; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 500; z-index: 99999; opacity: 0; transition: all 200ms ease; white-space: nowrap;';
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(-50%) translateY(0)'; });
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 200); }, 2000);
    }

    // Show bar on page load if items exist
    updateCompareBar({{ count($compareIds) }});
})();
</script>
