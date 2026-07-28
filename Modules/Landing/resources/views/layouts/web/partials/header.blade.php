@php
    $pageData = is_array($page_data ?? null) ? $page_data : [];
    $generalData = is_object($general ?? null) ? $general : (object) ['value' => []];
    $headerButtonRoute = data_get($pageData, 'headings.header_btn_link') ?: 'login';
    $headerButtonText = data_get($pageData, 'headings.header_btn_text') ?: __('Login');
@endphp

<header class="header-section home-header">
    <nav class="navbar navbar-expand-lg p-0">
        <div class="custom-container">
             <div class="collapse navbar-collapse navbar-lg-device d-flex align-items-center justify-content-between">
                 <ul class="navbar-nav  mb-2 mb-lg-0">
                     <li class="nav-item">
                         <a href="{{ route('home') }}" class="nav-link active" aria-current="page">{{ __('Home') }}</a>
                     </li>

                     <li class="nav-item">
                         <a class="nav-link" aria-current="page" href="{{ route('about.index') }}">{{ __('About Us') }}</a>
                     </li>

                     <li class="nav-item">
                         <a class="nav-link" aria-current="page" href="{{ route('plan.index') }}">{{ __('Pricing') }}</a>
                     </li>

                     <li class="nav-item">
                         <a class="nav-link" aria-current="page" href="{{ route('blogs.index') }}">{{ __('Blogs') }}</a>
                     </li>

                     <li class="nav-item">
                         <a class="nav-link" aria-current="page" href="{{ route('contact.index') }}">{{ __('Contact Us') }}</a>
                     </li>
                 </ul>
                 <a href="{{ route('home') }}" class="header-logo logo-lg-device ">
                    <img class="img-fluid nav-logo"
                    src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/icons/upload-icon.svg') }}"
                    alt="header-logo" />
                </a>

                    <div class="get-btn-container">
                    <a href="{{ Route::has($headerButtonRoute) ? route($headerButtonRoute) : route('login') }}"
                        class="get-app-btn ps-custom-btn">
                        <svg  width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5.48131 12.9012C4.30234 13.6032 1.21114 15.0366 3.09389 16.8304C4.01359 17.7065 5.03791 18.3332 6.32573 18.3332H13.6743C14.9621 18.3332 15.9864 17.7065 16.9061 16.8304C18.7888 15.0366 15.6977 13.6032 14.5187 12.9012C11.754 11.2549 8.24599 11.2549 5.48131 12.9012Z"
                                fill="white"></path>
                            <path
                                d="M13.75 5.4165C13.75 7.48757 12.0711 9.1665 10 9.1665C7.92893 9.1665 6.25 7.48757 6.25 5.4165C6.25 3.34544 7.92893 1.6665 10 1.6665C12.0711 1.6665 13.75 3.34544 13.75 5.4165Z"
                                fill="white"></path>
                        </svg>
                        {{ Str::words($headerButtonText, 1, '...') }}

                    </a>
                </div>
            </div>
            <div class="header-sm-container">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop"
                    aria-controls="staticBackdrop">

                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M2.5 4.16668C2.5 3.70644 2.8731 3.33334 3.33333 3.33334H16.6667C17.1269 3.33334 17.5 3.70644 17.5 4.16668C17.5 4.62692 17.1269 5.00001 16.6667 5.00001H3.33333C2.8731 5.00001 2.5 4.62691 2.5 4.16668Z"
                            fill="black" />
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M2.5 9.99999C2.5 9.53974 2.8731 9.16666 3.33333 9.16666H16.6667C17.1269 9.16666 17.5 9.53974 17.5 9.99999C17.5 10.4602 17.1269 10.8333 16.6667 10.8333H3.33333C2.8731 10.8333 2.5 10.4602 2.5 9.99999Z"
                            fill="black" />
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M2.5 15.8333C2.5 15.3731 2.8731 15 3.33333 15H16.6667C17.1269 15 17.5 15.3731 17.5 15.8333C17.5 16.2936 17.1269 16.6667 16.6667 16.6667H3.33333C2.8731 16.6667 2.5 16.2936 2.5 15.8333Z"
                            fill="black" />
                    </svg>

                </button>
                <a href="{{ route('home') }}" class="header-logo  ">
                    <img class="img-fluid nav-logo"
                    src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/icons/upload-icon.svg') }}"
                    alt="header-logo" />
                </a>

                <div class="get-btn-container login-sm-device">
                    <a href="{{ Route::has($headerButtonRoute) ? route($headerButtonRoute) : route('login') }}"
                        class="get-app-btn ps-custom-btn">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5.48131 12.9012C4.30234 13.6032 1.21114 15.0366 3.09389 16.8304C4.01359 17.7065 5.03791 18.3332 6.32573 18.3332H13.6743C14.9621 18.3332 15.9864 17.7065 16.9061 16.8304C18.7888 15.0366 15.6977 13.6032 14.5187 12.9012C11.754 11.2549 8.24599 11.2549 5.48131 12.9012Z"
                                fill="white"></path>
                            <path
                                d="M13.75 5.4165C13.75 7.48757 12.0711 9.1665 10 9.1665C7.92893 9.1665 6.25 7.48757 6.25 5.4165C6.25 3.34544 7.92893 1.6665 10 1.6665C12.0711 1.6665 13.75 3.34544 13.75 5.4165Z"
                                fill="white"></path>
                        </svg>
                        {{ Str::words($headerButtonText, 4, '...') }}

                    </a>
                </div>
            </div>
            {{-- Mobile Menu --}}

            <div class="offcanvas offcanvas-start mobile-menu" data-bs-backdrop="static" tabindex="-1"
                id="staticBackdrop" aria-labelledby="staticBackdropLabel">
                <div class="offcanvas-header">
                    <a href="{{ route('home') }}" class="header-logo"><img
                            src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/icons/upload-icon.svg') }}"
                            alt="header-logo" class="w-75" /></a>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 5L5 15" stroke="black" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M5 5L15 15" stroke="black" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div class="offcanvas-body">
                    <div class="accordion accordion-flush" id="sidebarMenuAccordion">
                        <div class="accordion-item">
                            <a href="{{ route('home') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Home') }}</a>
                        </div>
                        <div class="accordion-item">
                            <a href="{{ route('about.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('About Us') }}</a>
                        </div>

                        <div class="accordion-item">
                            <a href="{{ route('plan.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Pricing') }}</a>
                        </div>


                        <div class="accordion-item">
                            <a href="{{ route('blogs.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Blogs') }}</a>
                        </div>

                        <div class="accordion-item">
                            <a href="{{ route('contact.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Contact Us') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
