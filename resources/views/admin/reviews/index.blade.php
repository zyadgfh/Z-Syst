@extends('layouts.admin')

@section('title', __('orders.Product Reviews'))

@section('main_content')
<div class="container-fluid m-h-100">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="dashboard-title">{{ __('orders.Product Reviews') }}</h2>
            <p class="dashboard-subtitle">{{ __('orders.Manage customer reviews and ratings') }}</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card kpi-card p-3 text-center">
                <div class="kpi-value">{{ $stats['total'] }}</div>
                <div class="kpi-label">{{ __('orders.Total Reviews') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #f59e0b;">
                <div class="kpi-value" class="text-amber">{{ $stats['pending'] }}</div>
                <div class="kpi-label">{{ __('common.Pending') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-3 text-center border-left-accent" class="border-left-green">
                <div class="kpi-value" class="text-green">{{ $stats['approved'] }}</div>
                <div class="kpi-label">{{ __('common.Approved') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #8b5cf6;">
                <div class="kpi-value" class="text-violet">{{ number_format($stats['avg_rating'], 1) }}</div>
                <div class="kpi-label">{{ __('orders.Avg Rating') }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reviews.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="{{ __('orders.Search reviews...') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">{{ __('common.All Status') }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('common.Approved') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('common.Pending') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="rating" class="form-select">
                        <option value="">{{ __('orders.All Ratings') }}</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(request('rating') == $i)>{{ $i }} ★</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>{{ __('common.Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="card">
        <div class="card-body">
            @if ($reviews->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-star fa-3x mb-3" class="text-gray-border"></i>
                    <p class="text-subtle">{{ __('orders.No reviews found.') }}</p>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($reviews as $review)
                        <div class="list-group-item px-0 py-4" id="review-{{ $review->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="d-flex align-items-center">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg width="16" height="16" viewBox="0 0 24 24"
                                                     fill="{{ $i <= $review->rating ? '#fbbf24' : '#e5e7eb' }}"
                                                     stroke="{{ $i <= $review->rating ? '#f59e0b' : '#d1d5db' }}" stroke-width="1">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        @if ($review->is_verified_purchase)
                                            <span class="badge" style="background: #f0fdf4; color: #166534; font-size: 11px;">
                                                <i class="fas fa-check-circle me-1"></i>{{ __('purchases.Verified') }}
                                            </span>
                                        @endif
                                        @if (!$review->is_approved)
                                            <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 11px;">
                                                {{ __('common.Pending') }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Title & Product -->
                                    <div class="mb-1">
                                        @if ($review->title)
                                            <strong class="text-dark">{{ $review->title }}</strong>
                                        @endif
                                        <span class="text-muted ms-2" class="fs-13">
                                            {{ __('orders.for') }} <span class="text-green-dark">{{ $review->product->productName ?? 'N/A' }}</span>
                                        </span>
                                    </div>

                                    <!-- Review Text -->
                                    <p class="mb-2" style="color: #374151; font-size: 14px; line-height: 1.6;">{{ $review->review }}</p>

                                    <!-- Meta -->
                                    <div class="d-flex align-items-center gap-3" style="font-size: 12px; color: #9ca3af;">
                                        <span><i class="fas fa-user me-1"></i>{{ $review->user->name ?? 'N/A' }}</span>
                                        <span><i class="fas fa-calendar me-1"></i>{{ $review->created_at->format('M d, Y') }}</span>
                                        <span><i class="fas fa-thumbs-up me-1"></i>{{ $review->helpful_count }} {{ __('orders.helpful') }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex gap-2 flex-shrink-0 ms-3">
                                    @if (!$review->is_approved)
                                        <button onclick="approveReview({{ $review->id }})"
                                            class="btn btn-sm" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;"
                                            title="{{ __('orders.Approve') }}">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    @if ($review->is_approved)
                                        <button onclick="rejectReview({{ $review->id }})"
                                            class="btn btn-sm" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;"
                                            title="{{ __('orders.Hide') }}">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    @endif
                                    <button onclick="deleteReview({{ $review->id }})"
                                        class="btn btn-sm" style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;"
                                        title="{{ __('common.Delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $reviews->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function approveReview(id) {
    fetch(`/admin/reviews/${id}/approve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}

function rejectReview(id) {
    fetch(`/admin/reviews/${id}/reject`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}

function deleteReview(id) {
    if (!confirm('{{ __('orders.Are you sure you want to delete this review?') }}')) return;
    fetch(`/admin/reviews/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById(`review-${id}`)?.remove();
        }
    });
}
</script>
@endsection
