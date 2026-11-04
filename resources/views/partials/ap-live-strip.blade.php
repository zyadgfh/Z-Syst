{{-- Live "today in a line" strip — drop anywhere inside the dashboard.
    ap-live.js fills [data-ap-live] from GET /api/v1/live/summary (30s poll).
    Authenticated business users get the numbers without any wiring. --}}
<div class="ap-live-strip" role="status" aria-live="polite">
    <span class="ap-live-dot" data-ap-live-dot aria-hidden="true"></span>

    <span class="ap-live-item">
        <span class="label">{{ __('Sales today') }}</span>
        <span class="value" data-ap-live="sales_count" data-ap-live-value="0">—</span>
    </span>

    <span class="ap-live-item">
        <span class="label">{{ __('Revenue') }}</span>
        <span class="value" data-ap-live="sales_total" data-ap-live-suffix=" EGP" data-ap-live-value="0">—</span>
    </span>

    <span class="ap-live-item">
        <span class="label">{{ __('Due') }}</span>
        <span class="value" data-ap-live="due_total" data-ap-live-suffix=" EGP" data-ap-live-value="0">—</span>
    </span>

    <span class="ap-live-item">
        <span class="label">{{ __('Low stock') }}</span>
        <span class="value" data-ap-live="low_stock" data-ap-live-value="0">—</span>
    </span>

    <span class="ap-live-item">
        <span class="label">{{ __('Expiring ≤30d') }}</span>
        <span class="value" data-ap-live="expiring_soon" data-ap-live-value="0">—</span>
    </span>
</div>
