@extends('landing::layouts.web.master')

@section('title')
    {{ __(env('APP_NAME')) }}
@endsection

@section('main_content')
    @php
        $headings = is_array($page_data['headings'] ?? null) ? $page_data['headings'] : [];
        $heroTitle = $headings['slider_title'] ?? __('Smart pharmacy management, designed for growth');
        $heroDescription = $headings['slider_description'] ?? __('A professional platform to manage inventory, sales, prescriptions, and operations with accuracy, speed, and full control.');
        $heroPrimaryText = $headings['slider_btn1'] ?? __('Request demo');
        $heroSecondaryText = $headings['slider_btn2'] ?? __('Explore platform');
        $heroImage = $page_data['slider_image'] ?? 'assets/images/icons/img-upload.png';
    @endphp

    <section class="hero-modern-section">
        <div class="container">
            <div class="hero-modern-card" data-aos="fade-up">
                <div class="hero-copy">
                    <span class="hero-pill">{{ __('Pharmacy OS • Secure • Scalable') }}</span>
                    <h1>{{ $heroTitle }}</h1>
                    <p>{{ $heroDescription }}</p>

                    <div class="hero-actions">
                        <a class="btn btn-hero-primary" href="#plans">
                            {{ Str::words($heroPrimaryText, 3, '...') }}
                        </a>
                        <a class="btn btn-hero-secondary" href="#" data-bs-toggle="modal" data-bs-target="#watch-video-modal">
                            {{ Str::words($heroSecondaryText, 3, '...') }}
                        </a>
                    </div>

                    <div class="hero-metrics">
                        <div class="metric-card">
                            <strong>24/7</strong>
                            <span>{{ __('Operations') }}</span>
                        </div>
                        <div class="metric-card">
                            <strong>100%</strong>
                            <span>{{ __('Control') }}</span>
                        </div>
                        <div class="metric-card">
                            <strong>Fast</strong>
                            <span>{{ __('Reporting') }}</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-visual-card">
                        <div class="hero-visual-header">
                            <span></span><span></span><span></span>
                        </div>
                        <img src="{{ asset($heroImage) }}" alt="Product preview" />
                        <div class="hero-visual-footer">
                            <div>
                                <p>{{ __('Live operational view') }}</p>
                                <h5>{{ __('Built for pharmacies') }}</h5>
                            </div>
                            <span>{{ __('Live') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal modal-custom-design" id="watch-video-modal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe width="100%" height="400px" src="{{ $page_data['headings']['slider_btn2_link'] ?? '' }}"
                        title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <section class="highlights-section">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="section-heading">
                        <span class="hero-pill">{{ __('Why pharmacies choose us') }}</span>
                        <h2>{{ Str::words($headings['feature_title_start'] ?? __('Built for reliable daily operations'), 8, '...') }}</h2>
                        <p>{{ Str::words($headings['feature_title_end'] ?? __('From stock control to customer service, every module is designed to help your pharmacy run smoothly, safely, and efficiently.'), 28, '...') }}</p>
                    </div>
                    <div class="highlight-list">
                        <div class="highlight-item">
                            <div class="highlight-icon">✓</div>
                            <div>
                                <h6>{{ __('Clear operational view') }}</h6>
                                <p>{{ __('Critical information is available instantly, keeping your workflow simple and organized.') }}</p>
                            </div>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">✓</div>
                            <div>
                                <h6>{{ __('Fast and secure') }}</h6>
                                <p>{{ __('The system is optimized for real-time pharmacy use across desktop and mobile devices.') }}</p>
                            </div>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">✓</div>
                            <div>
                                <h6>{{ __('Growth-ready workflows') }}</h6>
                                <p>{{ __('Every part of the platform supports expansion, automation, and better business performance.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="highlight-card">
                        <div class="highlight-card-header">
                            <span>{{ __('Operational insight') }}</span>
                            <span>{{ __('Live overview') }}</span>
                        </div>
                        <div class="highlight-card-body">
                            <div class="progress-block">
                                <div class="progress-label">
                                    <span>{{ __('Inventory accuracy') }}</span>
                                    <strong>98%</strong>
                                </div>
                                <div class="progress-line"><span></span></div>
                            </div>
                            <div class="progress-block">
                                <div class="progress-label">
                                    <span>{{ __('Daily efficiency') }}</span>
                                    <strong>95%</strong>
                                </div>
                                <div class="progress-line"><span></span></div>
                            </div>
                            <div class="progress-block">
                                <div class="progress-label">
                                    <span>{{ __('Service quality') }}</span>
                                    <strong>97%</strong>
                                </div>
                                <div class="progress-line"><span></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('landing::web.components.feature')

    <section class="showcase-section">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="section-heading">
                        <span class="hero-pill">{{ __('Streamlined experience') }}</span>
                        <h2>{{ Str::words($headings['interface_title_start'] ?? __('A clean interface for daily pharmacy work'), 8, '...') }}</h2>
                        <p>{{ Str::words($headings['interface_description'] ?? __('Every screen is thoughtfully designed to make stock tracking, invoicing, and customer service faster and easier.'), 28, '...') }}</p>
                    </div>
                    <div class="showcase-list">
                        <div class="showcase-pill">{{ __('Accessible') }}</div>
                        <div class="showcase-pill">{{ __('Secure') }}</div>
                        <div class="showcase-pill">{{ __('Business-ready') }}</div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="showcase-grid">
                        @foreach ($interfaces as $interface)
                            <div class="showcase-card">
                                <img src="{{ asset($interface->image) }}" alt="Interface preview" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="integration-section">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="code-card">
                        <div class="code-card-header">
                            <span></span><span></span><span></span>
                            <span class="ms-auto">PHP / Laravel</span>
                        </div>
                        <pre><code>Route::get('/', function () {
    return view('welcome');
});

return response()->json([
    'status' => 'ready'
]);</code></pre>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="integration-copy">
                        <span class="hero-pill">{{ __('Ready for adoption') }}</span>
                        <h2>{{ Str::words($headings['code_title_start'] ?? __('Designed to fit your pharmacy workflow'), 8, '...') }}</h2>
                        <p>{{ Str::words($headings['code_description'] ?? __('The platform is structured to be easy to understand, fast to adopt, and practical for daily operational use.'), 28, '...') }}</p>
                        <div class="integration-points">
                            <div>{{ __('Unified dashboard') }}</div>
                            <div>{{ __('Smart modules') }}</div>
                            <div>{{ __('Professional mobile access') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-card" data-aos="fade-up">
                <div>
                    <span class="hero-pill">{{ __('Ready to launch') }}</span>
                    <h2>{{ __('Create a stronger first impression for your pharmacy business.') }}</h2>
                    <p>{{ __('Every section has been crafted to feel modern, trusted, and focused on real operational value.') }}</p>
                </div>
                <a class="btn btn-hero-primary" href="#plans">{{ __('Start now') }}</a>
            </div>
        </div>
    </section>

    @include('landing::web.components.plan')

    <section class="customer-section">
        <div class="container">
            <div class="section-heading text-center" data-aos="fade-up">
                <span class="hero-pill">{{ __('Trusted by growing teams') }}</span>
                <h2>{{ Str::words($headings['testimonial_title_start'] ?? __('What owners and managers say'), 8, '...') }}</h2>
            </div>
            <div class="row g-4" data-aos="zoom-in">
                @foreach ($testimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <div class="customer-card">
                            <img src="{{ asset($testimonial->client_image) }}" alt="" />
                            <p>“{{ Str::words($testimonial->text ?? '', 24, '...') }}”</p>
                            <div class="customer-meta">
                                <h5>{{ Str::limit($testimonial->client_name ?? '', 20, '') }}</h5>
                                <small>{{ Str::limit($testimonial->work_at ?? '', 25, '') }}</small>
                                <div class="customer-star">
                                    @for ($i = 0; $i < $testimonial->star; $i++)
                                        ★
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="blogs-section home-blogs-section">
        <div class="container">
            <div class="section-heading d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <span class="hero-pill">{{ __('Latest updates') }}</span>
                    <h2>{{ Str::words($headings['blog_title_start'] ?? __('Insights for modern pharmacy operations'), 8, '...') }}</h2>
                </div>
                <a href="{{ url($headings['blog_view_all_btn_link'] ?? '') }}" class="btn btn-hero-secondary">
                    {{ Str::words($headings['blog_view_all_btn_text'] ?? __('View all'), 3, '...') }}
                </a>
            </div>
        </div>
        @include('landing::web.components.blog')
    </section>
@endsection

@push('js')
    <script src="{{ asset('assets/web/js/typed.min.js') }}"></script>
@endpush
