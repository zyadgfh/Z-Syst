@php
    $pageData = is_array($page_data ?? null) ? $page_data : [];
    $generalData = is_object($general ?? null) ? $general : (object) ['value' => []];
    $headerButtonRoute = data_get($pageData, 'headings.header_btn_link') ?: 'login';
    $headerButtonText = data_get($pageData, 'headings.header_btn_text') ?: __('Login');
    $currentLocale = app()->getLocale();
@endphp

<header class="header-section home-header">
    <nav class="navbar navbar-expand-lg p-0">
        <div class="custom-container">
             <div class="collapse navbar-collapse navbar-lg-device align-items-center justify-content-between">
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
                         <a class="nav-link" aria-current="page" href="{{ route('catalog.index') }}">{{ __('Catalog') }}</a>
                     </li>
                     <li class="nav-item">
                         <a class="nav-link" aria-current="page" href="{{ route('contact.index') }}">{{ __('Contact Us') }}</a>
                     </li>
                 </ul>
                 <a href="{{ route('home') }}" class="header-logo logo-lg-device ">
                    <img class="img-fluid nav-logo"
                    src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/logo/logo.png') }}"
                    alt="header-logo" />
                </a>

                    <div class="get-btn-container d-flex align-items-center gap-2">
                    {{-- Wishlist Icon --}}
                    <a href="{{ route('wishlist.index') }}" class="lang-switcher-btn position-relative" aria-label="{{ __('Wishlist') }}" style="text-decoration: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span id="wishlist-count-badge" class="wishlist-count-badge position-absolute" style="display:none; top:-4px; right:-4px; width:18px; height:18px; border-radius:50%; background:#ff3b30; color:white; font-size:10px; font-weight:700; align-items:center; justify-content:center;">0</span>
                    </a>
                    {{-- Cart Icon --}}
                    <button onclick="openCartDrawer()" class="lang-switcher-btn position-relative" aria-label="{{ __('Shopping Cart') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                        </svg>
                        <span id="cart-badge" class="position-absolute" style="display:none; top:-4px; right:-4px; width:18px; height:18px; border-radius:50%; background:#ef4444; color:white; font-size:10px; font-weight:700; align-items:center; justify-content:center;">0</span>
                    </button>
                    {{-- Language Switcher --}}
                    <form method="POST" action="{{ route('locale.switch') }}" id="lang-switcher-form" class="d-inline">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $currentLocale === 'ar' ? 'en' : 'ar' }}">
                        <button type="submit" class="lang-switcher-btn" aria-label="{{ $currentLocale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                <ellipse cx="12" cy="12" rx="4" ry="10" stroke="currentColor" stroke-width="1.5"/>
                                <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4 7h16M4 17h16" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                            </svg>
                            <span class="lang-label">{{ $currentLocale === 'ar' ? 'EN' : 'عربي' }}</span>
                        </button>
                    </form>
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
                    aria-controls="staticBackdrop" aria-label="Toggle navigation">

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
                    src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/logo/logo.png') }}"
                    alt="header-logo" />
                </a>

                <div class="get-btn-container login-sm-device d-flex align-items-center gap-2">
                    {{-- Wishlist Icon (Mobile) --}}
                    <a href="{{ route('wishlist.index') }}" class="lang-switcher-btn position-relative" aria-label="{{ __('Wishlist') }}" style="text-decoration: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span id="wishlist-count-badge-mobile" class="wishlist-count-badge position-absolute" style="display:none; top:-4px; right:-4px; width:18px; height:18px; border-radius:50%; background:#ff3b30; color:white; font-size:10px; font-weight:700; align-items:center; justify-content:center;">0</span>
                    </a>
                    {{-- Cart Icon (Mobile) --}}
                    <button onclick="openCartDrawer()" class="lang-switcher-btn position-relative" aria-label="{{ __('Shopping Cart') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                        </svg>
                        <span id="cart-badge-mobile" class="position-absolute" style="display:none; top:-4px; right:-4px; width:18px; height:18px; border-radius:50%; background:#ef4444; color:white; font-size:10px; font-weight:700; align-items:center; justify-content:center;">0</span>
                    </button>
                    {{-- Language Switcher (Mobile) --}}
                    <form method="POST" action="{{ route('locale.switch') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $currentLocale === 'ar' ? 'en' : 'ar' }}">
                        <button type="submit" class="lang-switcher-btn" aria-label="{{ $currentLocale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                <ellipse cx="12" cy="12" rx="4" ry="10" stroke="currentColor" stroke-width="1.5"/>
                                <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4 7h16M4 17h16" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                            </svg>
                            <span class="lang-label">{{ $currentLocale === 'ar' ? 'EN' : 'عربي' }}</span>
                        </button>
                    </form>
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
                        {{ Str::words($headerButtonText, 2, '') }}

                    </a>
                </div>
            </div>
            {{-- Mobile Menu --}}

            <div class="offcanvas offcanvas-start mobile-menu" data-bs-backdrop="static" tabindex="-1"
                id="staticBackdrop" aria-labelledby="staticBackdropLabel">
                <div class="offcanvas-header">
                    <a href="{{ route('home') }}" class="header-logo"><img
                            src="{{ asset(data_get($generalData, 'value.frontend_logo') ?: 'assets/images/logo/logo.png') }}"
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
                    <a href="{{ Route::has($headerButtonRoute) ? route($headerButtonRoute) : route('login') }}"
                        class="mobile-menu-login">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.48131 12.9012C4.30234 13.6032 1.21114 15.0366 3.09389 16.8304C4.01359 17.7065 5.03791 18.3332 6.32573 18.3332H13.6743C14.9621 18.3332 15.9864 17.7065 16.9061 16.8304C18.7888 15.0366 15.6977 13.6032 14.5187 12.9012C11.754 11.2549 8.24599 11.2549 5.48131 12.9012Z"
                                fill="currentColor"></path>
                            <path d="M13.75 5.4165C13.75 7.48757 12.0711 9.1665 10 9.1665C7.92893 9.1665 6.25 7.48757 6.25 5.4165C6.25 3.34544 7.92893 1.6665 10 1.6665C12.0711 1.6665 13.75 3.34544 13.75 5.4165Z"
                                fill="currentColor"></path>
                        </svg>
                        <span>{{ __('Login') }}</span>
                    </a>
                    {{-- Mobile Language Switcher in offcanvas menu --}}
                    <form method="POST" action="{{ route('locale.switch') }}" class="px-3 mb-3">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $currentLocale === 'ar' ? 'en' : 'ar' }}">
                        <button type="submit" class="lang-switcher-btn-mobile">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                <ellipse cx="12" cy="12" rx="4" ry="10" stroke="currentColor" stroke-width="1.5"/>
                                <line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4 7h16M4 17h16" stroke="currentColor" stroke-width="1" opacity="0.5"/>
                            </svg>
                            <span>{{ $currentLocale === 'ar' ? 'English' : 'العربية' }}</span>
                        </button>
                    </form>
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
                            <a href="{{ route('catalog.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Catalog') }}</a>
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

{{-- Cart Drawer --}}
@include('customer.cart.drawer')
