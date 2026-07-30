<section class="service-section">
    <div class="container">
        <div data-aos="fade-up" class="section-heading text-center">
            <span class="hero-pill">{{ __('Core features') }}</span>
            <h2>{{ Str::words($page_data['headings']['feature_title_start'] ?? __('What makes the experience special'), 8, '...') }}</h2>
            <p>{{ Str::words($page_data['headings']['feature_title_end'] ?? __('A thoughtful structure that blends modern visuals, useful content, and a calm, premium feel.'), 24, '...') }}</p>
        </div>
        <div class="row g-4">
            @foreach ($features as $feature)
                <div class="col-sm-6 col-lg-4">
                    <div class="feature-card" style="background: {{ $feature->bg_color ?? 'linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%)' }}">
                        <div class="feature-icon">
                            <img src="{{ asset($feature->image) }}" alt="{{ $feature->title ?? __('Feature') }}" />
                        </div>
                        <div class="feature-content">
                            <h6>{{ Str::words($feature->title ?? __('Feature'), 4, '...') }}</h6>
                            <p>{{ __('Designed to feel polished, useful, and easy to understand.') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
