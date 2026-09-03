@extends('landing::layouts.web.master')

@section('title', __('Write a Review'))

@section('main_content')
<main class="min-h-screen py-8" style="background: #f9fafb;">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <!-- Back Link -->
        <a href="{{ route('catalog.show', $product->id) }}" class="inline-flex items-center gap-1 text-sm font-medium mb-6" style="color: #15803d; text-decoration: none;">
            ← {{ __('Back to Product') }}
        </a>

        <!-- Review Card -->
        <div class="bg-white rounded-2xl p-8 shadow-sm" style="border: 1px solid #f3f4f6;">
            <h1 class="text-xl font-bold mb-1" style="color: #111827;">{{ __('Write a Review') }}</h1>
            <p class="text-sm mb-6" style="color: #6b7280;">{{ __('Share your experience with this product') }}</p>

            <!-- Product Info -->
            <div class="flex items-center gap-4 p-4 rounded-xl mb-6" style="background: #f9fafb; border: 1px solid #f3f4f6;">
                <div class="w-16 h-16 rounded-xl flex items-center justify-center flex-shrink-0" style="background: #e5e7eb;">
                    @if ($product->images && is_array($product->images) && count($product->images) > 0)
                        <img src="{{ asset('storage/' . $product->images[0]) }}" alt="{{ $product->productName }}" class="w-16 h-16 rounded-xl object-cover">
                    @else
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                    @endif
                </div>
                <div>
                    <div class="font-semibold" style="color: #111827;">{{ $product->productName }}</div>
                    <div class="text-sm" style="color: #6b7280;">{{ $product->commercial_name ?? $product->scientific_name ?? '' }}</div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-3 rounded-xl text-sm" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('reviews.store', $product->id) }}" method="POST">
                @csrf

                @if ($orderItem)
                    <input type="hidden" name="customer_order_id" value="{{ $orderItem->customer_order_id }}">
                @endif

                <!-- Star Rating -->
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-3" style="color: #374151;">{{ __('Your Rating') }} *</label>
                    <div class="star-rating flex gap-1" id="star-rating">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" class="star-btn" data-value="{{ $i }}"
                                style="background: none; border: none; cursor: pointer; padding: 4px; transition: transform 100ms ease;"
                                onmousedown="this.style.transform='scale(1.2)'" onmouseup="this.style.transform='scale(1)'">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="#e5e7eb" stroke="#d1d5db" stroke-width="1" class="star-icon" data-value="{{ $i }}">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="{{ old('rating', 5) }}">
                    <div class="text-sm mt-2" style="color: #6b7280;" id="rating-text">{{ __('Excellent') }}</div>
                </div>

                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Review Title') }} <span style="color: #9ca3af;">{{ __('(optional)') }}</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('Summarize your experience') }}">
                </div>

                <!-- Review Text -->
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Your Review') }} *</label>
                    <textarea name="review" required rows="5" maxlength="2000"
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('Tell others what you think about this product...') }}">{{ old('review') }}</textarea>
                    <div class="text-xs mt-1" style="color: #9ca3af;">{{ __('Minimum 10 characters') }}</div>
                </div>

                <!-- Verified Purchase Badge -->
                @if ($orderItem && $orderItem->is_verified_purchase ?? true)
                    <div class="mb-6 p-3 rounded-xl flex items-center gap-2" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                        <span class="text-sm" style="color: #166534;">{{ __('Verified Purchase') }}</span>
                    </div>
                @endif

                <!-- Submit -->
                <button type="submit"
                    class="w-full py-3.5 rounded-xl text-white font-semibold text-sm transition-all"
                    style="background: #15803d;"
                    onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                    {{ __('Submit Review') }}
                </button>
            </form>
        </div>
    </div>
</main>

<script>
const ratingTexts = {
    1: '{{ __("Poor") }}',
    2: '{{ __("Fair") }}',
    3: '{{ __("Good") }}',
    4: '{{ __("Very Good") }}',
    5: '{{ __("Excellent") }}',
};

const ratingLabels = {
    1: '#ef4444',
    2: '#f59e0b',
    3: '#eab308',
    4: '#84cc16',
    5: '#22c55e',
};

let currentRating = {{ old('rating', 5) }};

function updateStars(value) {
    currentRating = value;
    document.getElementById('rating-input').value = value;
    document.getElementById('rating-text').textContent = ratingTexts[value];
    document.getElementById('rating-text').style.color = ratingLabels[value];

    document.querySelectorAll('.star-icon').forEach(star => {
        const starValue = parseInt(star.getAttribute('data-value'));
        if (starValue <= value) {
            star.setAttribute('fill', ratingLabels[value]);
            star.setAttribute('stroke', ratingLabels[value]);
        } else {
            star.setAttribute('fill', '#e5e7eb');
            star.setAttribute('stroke', '#d1d5db');
        }
    });
}

document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('mouseenter', () => {
        const value = parseInt(btn.getAttribute('data-value'));
        document.querySelectorAll('.star-icon').forEach(star => {
            const starValue = parseInt(star.getAttribute('data-value'));
            if (starValue <= value) {
                star.setAttribute('fill', '#fbbf24');
                star.setAttribute('stroke', '#f59e0b');
            }
        });
    });

    btn.addEventListener('mouseleave', () => {
        updateStars(currentRating);
    });

    btn.addEventListener('click', () => {
        const value = parseInt(btn.getAttribute('data-value'));
        updateStars(value);
    });
});

// Initialize
updateStars(currentRating);
</script>

<style>
    @media (prefers-reduced-motion: reduce) {
        .star-btn { transition: none !important; }
    }
</style>
@endsection
