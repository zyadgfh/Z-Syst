<section class="service-section">
    <div class="container">
        <div data-aos="fade-up" class="section-title text-center feature-header">
            <h2 class="langing-section-title">{{ Str::words($page_data['headings']['feature_title_start'] ?? '', 5, '...') }} <span class="title-span-color">{{ Str::words($page_data['headings']['feature_title_end'] ?? '', 3, '...') }}</span> </h2>
        </div>
        <div class="row">
            @foreach ($features as $feature)
                <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                    <div  class="text-center service-card" style="background: {{ $feature->bg_color }}">
                        <div class="image">
                            <img src="{{ asset($feature->image) }}" alt="image" />
                        </div>
                        <div class="service-content">
                            <h6>{{ Str::words($feature->title ?? '', 3, '...') }}</h6>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
