<section class="pricing-plan-section plans-list" id="plans">
    <div class="container">
        <div class="section-title text-center">
            @php($headings = is_array($page_data['headings'] ?? null) ? $page_data['headings'] : [])

            <div class="section-pill mb-3" data-aos="fade-up">
                <i class="fa-solid fa-circle-check"></i>
                {{ __('Flexible plans') }}
            </div>

            <h2 data-aos="fade-up" class="langing-section-title">
                {{ Str::words($headings['pricing_short_title_start'] ?? '', 2, '...') }} <span class="title-span-color"> {{ Str::words($headings['pricing_short_title_middle'] ?? '', 2, '...') }}</span>   {{ Str::words($headings['pricing_short_title_end'] ?? '', 2, '...') }}
            </h2>

            <p data-aos="fade-up" class="max-w-600 mx-auto section-description ">
                {{ Str::words($headings['pricing_title'] ?? '', 20, '...') }}
            </p>

             <div class="d-flex align-items-center justify-content-center gap-4">

                <div class="w-100 d-flex flex-column align-items-center">
                    <div class="tab-content w-100" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-monthly" role="tabpanel"
                            aria-labelledby="nav-monthly-tab">
                            <div class="row">
                                @foreach ($plans as $plan)
                                    <div class="col-sm-12 col-md-6 col-lg-4 mt-3">
                                        <div class="card {{ $loop->first ? 'featured-plan' : '' }}">
                                            <div  class="card-header py-3 border-0 ">
                                                @if ($loop->first)
                                                    <span class="plan-badge">{{ __('Popular') }}</span>
                                                @endif
                                                <p class="m-0">{{ $plan->subscriptionName }}</p>
                                                <h4 class="m-0">{{ currency_format($plan->subscriptionPrice ?? 0) }}<span
                                                        class="price-span">/
                                                        {{ $plan->duration . ' ' . __('Days') }}</span></h4>
                                            </div>
                                            <div
                                                class="card-body text-start mt-3 d-flex flex-column justify-content-between h-100">
                                                <ul class="features-list d-flex align-items-start flex-column gap-1">
                                                    @foreach ($plan['features'] ?? [] as $key => $item)
                                                        <li class="feature-item">
                                                            @if (isset($item[1]))
                                                                <img src="{{ asset('modules/landing/web/images/banner/plan-check.svg') }}"
                                                                    alt="Check" class="me-1 plan-icon">
                                                            @else
                                                                <img src="{{ asset('modules/landing/web/images/banner/plan-cross.svg') }}"
                                                                    alt="Times" class="me-1 plan-icon">
                                                            @endif
                                                            <span class="single-features">{{ $item[0] ?? '' }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                @if (count($plan['features'] ?? []) > 8)
                                                    <button class="btn text-start p-0 see-more-btn">{{ __('See More') }}</button>
                                                @endif

                                                <a class="btn subscribe-plan d-block mt-4 mb-2" data-bs-target="#registration-modal" data-bs-toggle="modal">{{ __("Buy Now") }}</a>

                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
@include('landing::web.components.signup')
