@extends('landing::layouts.web.master')

@section('title')
    {{ __('About Us') }}
@endsection

@section('main_content')
    <section class="about-section">
        <div class="custom-container">
            <div class="banner-bg">
                <div class="container">
                    <p class="mb-0 fw-bolder custom-clr-dark">
                        {{ __('Home') }} <span class="font-monospace">></span> {{ __('About Us') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row mb-3 mt-5">
                <div class="col-lg-7 align-self-center">
                    <div>
                        <h6>
                            <span
                                class="langing-section-subtitle ">{{ Str::words($page_data['headings']['about_short_title'] ?? '', 3, '...') }}
                            </span>
                        </h6>
                        <h2 class="mb-3 langing-section-title ">
                            {{ Str::words($page_data['headings']['about_title'] ?? '', 10, '...') }}
                        </h2>
                        <p>
                            {{ $page_data['headings']['about_desc_one'] ?? '' }}
                        </p>
                        <p>
                            {{ $page_data['headings']['about_desc_two'] ?? '' }}
                        </p>
                        <ul>
                            @foreach ($page_data['headings']['about_us_options_text'] ?? [] as $key => $about_us_options_text)
                                <li>{{ $about_us_options_text ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-5 align-self-center">
                    <div class="w-90 position-relative ms-auto about-img">
                        <img src="{{ asset($page_data['about_image'] ?? 'assets/images/icons/img-upload.png') }}"
                            alt="image" class="w-100" />
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
