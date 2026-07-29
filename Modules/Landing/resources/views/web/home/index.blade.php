@extends('landing::layouts.web.master')

@section('title')
    {{ __(env('APP_NAME')) }}
@endsection

@section('main_content')
    <section class="home-banner-section">
        <div class="custom-container mt-2">
            <div class=" hero-bg position-relative p-3">
                <div class="banner-content">
                    <h1 data-aos="fade-right">
                        {{ str_word_count($page_data['headings']['slider_title'] ?? '') > 6
                            ? implode(' ', array_slice(explode(' ', $page_data['headings']['slider_title'] ?? ''), 0, 6)) . '...'
                            : $page_data['headings']['slider_title'] ?? '' }}
                          <br>
                        <span class="typed-text"></span>
                        <span id="typed-data" data-strings="{{ json_encode($page_data['headings']['silder_shop_text'] ?? []) }}"></span>
                    </h1>

                    <p data-aos="fade-right" data-aos-delay="300" >
                        {{ Str::words($page_data['headings']['slider_description'] ?? '', 20, '...') }}
                    </p>
                    <div data-aos="fade-right" data-aos-delay="600" class="demo-btn-group mb-3">
                        <a class="custom-btn custom-primary-btn ps-custom-btn" href="#plans">
                            {{ Str::words($page_data['headings']['slider_btn1'] ?? '', 3, '...') }}
                            <svg class="btn-icon" width="25" height="24" viewBox="0 0 25 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12.5 1.25C6.572 1.25 1.75 6.072 1.75 12C1.75 17.928 6.572 22.75 12.5 22.75C18.428 22.75 23.25 17.928 23.25 12C23.25 6.072 18.428 1.25 12.5 1.25ZM12.5 21.25C7.399 21.25 3.25 17.101 3.25 12C3.25 6.899 7.399 2.75 12.5 2.75C17.601 2.75 21.75 6.899 21.75 12C21.75 17.101 17.601 21.25 12.5 21.25ZM17.1919 12.2871C17.1539 12.3791 17.099 12.462 17.03 12.531L14.03 15.531C13.884 15.677 13.692 15.751 13.5 15.751C13.308 15.751 13.116 15.678 12.97 15.531C12.677 15.238 12.677 14.763 12.97 14.47L14.6899 12.75H8.5C8.086 12.75 7.75 12.414 7.75 12C7.75 11.586 8.086 11.25 8.5 11.25H14.689L12.969 9.53003C12.676 9.23703 12.676 8.76199 12.969 8.46899C13.262 8.17599 13.737 8.17599 14.03 8.46899L17.03 11.469C17.099 11.538 17.1539 11.6209 17.1919 11.7129C17.2679 11.8969 17.2679 12.1031 17.1919 12.2871Z"
                                    fill="white" />
                            </svg>
                        </a>

                        <a href="#" class="d-flex align-items-center justify-content-center"
                            data-bs-toggle="modal" data-bs-target="#watch-video-modal">
                            <div id="play-video" class="video-play-button">
                                <p></p>
                            </div>
                            <span
                                class="watch-text">{{ Str::words($page_data['headings']['slider_btn2'] ?? '', 3, '...') }}
                            </span>
                        </a>
                    </div>
                    <div data-aos="fade-up"  class="hero-img-container">
                        <img class="position-absolute bottom-0 start-50 translate-middle-x"
                        src="{{ asset($page_data['slider_image'] ?? 'assets/images/icons/img-upload.png') }}"
                        alt="img">
                    </div>
                </div>
                    <img data-aos="fade-left" class="element1 move-image" src="{{ asset('assets/images/icons/elements1.svg') }}" alt=""
                        srcset="">
                    <img data-aos="fade-left" class="element3" src="{{ asset('assets/images/icons/hero-arrow.svg') }}" alt=""
                        srcset="">
                    <img data-aos="fade-left" class="element2 move-image" src="{{ asset('assets/images/icons/elements2.svg') }}" alt=""
                        srcset="">
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

    {{-- Feature Code Start --}}

    @include('landing::web.components.feature')

    {{-- Interface Code Start --}}

    <section class="slick-slider-section ">
        <div class="container">
            <div data-aos="fade-up" class="section-title text-center ">

                <h2 class="langing-section-title">
                    {{ Str::words($page_data['headings']['interface_title_start'] ?? '', 5, '...') }} <span class="title-span-color">{{ Str::words($page_data['headings']['interface_title_end'] ?? '', 15, '...') }}</span>
                </h2>
                <p class="max-w-600 mx-auto section-description ">
                    {{ Str::words($page_data['headings']['interface_description'] ?? '', 20, '...') }}
                </p>
            </div>
            <div class="row app-slide">
                @foreach ($interfaces as $interface)
                    <div class="image d-flex align-items-center justify-content-center p-2">
                        <img src="{{ asset($interface->image) }}" alt="phone" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="get-app-container">
        <div class="get-app-content position-relative">
            <img class="get-app-logo" data-aos="fade-right"  src="{{ asset($page_data['get_app_icon'] ?? 'assets/images/icons/ps-logo.svg') }}" alt="img">
            <h4 data-aos="fade-right" data-aos-delay="300" class="langing-section-title">{{ $page_data['headings']['get_app_title_start'] ?? '' }} <span class="title-span-color">{{ $page_data['headings']['get_app_title_middle'] ?? '' }}</span> {{ $page_data['headings']['get_app_title_end'] ?? '' }}</h4>
            <p data-aos="fade-right" data-aos-delay="600" class="section-description">{{ $page_data['headings']['get_app_description'] ?? '' }}</p>
            <div class="d-flex align-items-center justify-content-center gap-2">
                <a href="{{ $page_data['headings']['get_apple_app_link'] ?? '' }}" target="_blank">
              <img data-aos="fade-right" data-aos-delay="900" class="download-on" src="{{ asset($page_data['get_apple_app_image'] ?? 'assets/images/icons/app-store.svg') }}"  alt="img">
            </a>
             <a href="{{ $page_data['headings']['get_google_play_app_link'] ?? '' }}" target="_blank">
                <img data-aos="fade-right" data-aos-delay="900" class="download-on" src="{{ asset($page_data['get_google_app_image'] ?? 'assets/images/icons/google-play.svg') }}" alt="img"><img class="get-arrow" src="{{ asset('assets/images/icons/get-arrow.svg') }}" alt="img">
             </a>

            </div>
        </div>
    </section>


    {{-- Watch demo Code Start --}}
    <section class="watch-demo-section watch-demo-two section-gradient-bg">
        <div class="container watch-video-container">

            <div data-aos="fade-right" class="video-wrapper position-relative">
                <img src="{{ asset($page_data['watch_image'] ?? 'assets/images/icons/img-upload.png') }}"
                    alt="watch" />
                <a class="watch-video play-btn" data-bs-toggle="modal" data-bs-target="#play-video-modal">
                    <p></p>
                </a>
            </div>
            <div class="watch-video-content">

                <h4 data-aos="fade-left" class="langing-section-title">{{ Str::words($page_data['headings']['watch_title_start'] ?? '', 5, '...') }} <span class="title-span-color">{{ Str::words($page_data['headings']['watch_title_middle'] ?? '', 5, '...') }}</span> {{ Str::words($page_data['headings']['watch_title_end'] ?? '', 5, '...') }} </h4>

                <p data-aos="fade-left" data-aos-delay="300" class="section-description">
                    {{ Str::words($page_data['headings']['watch_description'] ?? '', 20, '...') }}

                </p>
                <div data-aos="fade-left" data-aos-delay="600" class="mt-3">

                    <a class="download-btn ps-custom-btn"
                        href="{{ $page_data['headings']['download_watch_btn_link'] ?? '' }}" target="_blank">{{ Str::words($page_data['headings']['download_watch_btn_text'] ?? '', 4, '...') }}

                        <svg width="20" height="20" viewBox="0 0 24 25" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M17.4776 9.51106C17.485 9.51102 17.4925 9.51101 17.5 9.51101C19.9853 9.51101 22 11.5294 22 14.0193C22 16.3398 20.25 18.2508 18 18.5M17.4776 9.51106C17.4924 9.34606 17.5 9.17896 17.5 9.01009C17.5 5.96695 15.0376 3.5 12 3.5C9.12324 3.5 6.76233 5.71267 6.52042 8.53192M17.4776 9.51106C17.3753 10.6476 16.9286 11.6846 16.2428 12.5165M6.52042 8.53192C3.98398 8.77373 2 10.9139 2 13.5183C2 15.9417 3.71776 17.9632 6 18.4273M6.52042 8.53192C6.67826 8.51687 6.83823 8.50917 7 8.50917C8.12582 8.50917 9.16474 8.88194 10.0005 9.51101"
                                stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M12 21.5V13.5M12 21.5C11.2998 21.5 9.99153 19.5057 9.5 19M12 21.5C12.7002 21.5 14.0085 19.5057 14.5 19"
                                stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                    </a>
                </div>
            </div>
        </div>
    </section>



    <div class="modal modal-custom-design" id="play-video-modal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe width="100%" height="400px" src="{{ $page_data['headings']['watch_btn_link'] ?? '' }}"
                        title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>



    {{-- Pricing-Plan-section demo Code Start --}}
    @include('landing::web.components.plan')

    {{-- Payment-panel-section demo Code Start --}}
    <section class="payment-method-container">
        <div class="container">
            <div class="">
                <div class="section-title text-center">

                    <h4 data-aos="fade-up" class="payment-title">{{ Str::words($page_data['headings']['payment_title_start'] ?? '', 15, '...') }} <span class="payment-color-span">{{ Str::words($page_data['headings']['payment_title_middle'] ?? '', 15, '...') }}</span> {{ Str::words($page_data['headings']['payment_title_end'] ?? '', 15, '...') }} </h4>
                    <p data-aos="fade-up" data-aos-delay="300" class="payment-pera">{{ $page_data['headings']['payment_desc'] ?? '' }}</p>

                </div>
                <div data-aos="zoom-in-up" data-aos-delay="600" class="payment-img">
                    <img src="{{ asset($page_data['payment_image'] ?? 'assets/images/icons/img-upload.png') }}"
                        alt="">
                </div>
            </div>
        </div>
    </section>


    {{-- Testimonial Section Start --}}
    <section class="customer-section section-gradient-bg">
        <div class="custom-container mb-4">
            <div class="section-title text-center">


                <h4 data-aos="fade-up" class="langing-section-title">{{ Str::words($page_data['headings']['testimonial_title_start'] ?? '', 15, '...') }} <span class="title-span-color">{{ Str::words($page_data['headings']['testimonial_title_end'] ?? '', 15, '...') }}</span> </h4>

            </div>
            {{--  --}}
            <div data-aos="zoom-in" class="customer-slider-section">

                <div class="swiper-container">
                    <div class="swiper-wrapper">
                        @foreach ($testimonials as $testimonial)
                            <div class="swiper-slide">
                                <div class="customer-card">
                                    <img src="{{ asset($testimonial->client_image) }}" alt="" />
                                    <p>
                                        {{ Str::words($testimonial->text ?? '', 18, '...') }}
                                    </p>
                                    <div class="d-flex align-items-center justify-content-center flex-column">
                                        <h5 class="m-0"> {{ Str::limit($testimonial->client_name ?? '', 20, '') }}</h5>
                                        <small> {{ Str::limit($testimonial->work_at ?? '', 25, '') }}</small>
                                        <p class="customer-star">
                                            @for ($i = 0; $i < $testimonial->star; $i++)
                                                ★
                                            @endfor
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- Blogs Section Code Start --}}

    <section class="blogs-section">
        <div class="container">
            <div class="section-title  d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <h4 data-aos="fade-up" class="langing-section-title blog-section-title">{{ Str::words($page_data['headings']['blog_title_start'] ?? '', 15, '...') }} <span class="title-span-color">{{ Str::words($page_data['headings']['blog_title_end'] ?? '', 15, '...') }}</span> </h4>

                <a data-aos="fade-up" href="{{ url($page_data['headings']['blog_view_all_btn_link'] ?? '') }}"
                    class="ps-custom-btn">
                    {{ Str::words($page_data['headings']['blog_view_all_btn_text'] ?? '', 3, '...') }}
                    <svg class="btn-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.5 10.0001C12.5 10.4603 12.1269 10.8334 11.6667 10.8334H3.33333C2.87308 10.8334 2.5 10.4603 2.5 10.0001C2.5 9.53983 2.87308 9.16675 3.33333 9.16675H11.6667C12.1269 9.16675 12.5 9.53983 12.5 10.0001Z" fill="white"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M11.2735 5.9316C11.0025 6.07662 10.8333 6.359 10.8333 6.66634V13.333C10.8333 13.6403 11.0025 13.9227 11.2734 14.0677C11.5444 14.2127 11.8732 14.1969 12.1289 14.0264L17.1289 10.6934C17.3607 10.5389 17.5 10.2786 17.5 10C17.5 9.72138 17.3608 9.46121 17.129 9.30663L12.1289 5.97299C11.8732 5.80249 11.5445 5.78659 11.2735 5.9316Z" fill="white"/>
                        </svg>


                </a>
            </div>
        </div>
    @include('landing::web.components.blog')
    </section>
    @include('landing::web.components.signup')
@endsection


@push('js')
  <script src="{{ asset('assets/web/js/typed.min.js') }}"></script>
@endpush
