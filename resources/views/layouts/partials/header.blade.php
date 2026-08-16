<div class="header-bg sticky-top">
    <header class="main-header-section">
        <div class="header-wrapper">
            <div class="header-left">
                <div class="sidebar-opener"><i class="fal fa-bars" aria-hidden="true"></i></div>
                <!-- Z-Syst Logo -->
                <a href="{{ route('admin.dashboard') }}" class="logo-container">
                    <img src="{{ asset('logo.png') }}" alt="Z-Syst Pharmacy Management" class="logo logo-small">
                </a>
                <a target="_blank" class="view-website" href="{{ route('home') }}">
                    {{ __('View Website') }}
                    <i class="fas fa-chevron-double-right"></i>
                </a>
            </div>

            <div class="header-middle"></div>
            <div class="header-right">
                <div class="language-change">
                    <div class="dropdown">
                        <button class="btn language-dropdown dropdown-toggle language-btn border-0" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img src="{{ asset('flags/' . languages()[app()->getLocale()]['flag'] . '.svg') }}" alt="" class="flag-icon">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-scroll">
                            @foreach (languages() as $key => $language)
                                <li class="language-li">
                                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['lang' => $key]) }}">
                                        <div class="language-img-container">
                                            <img src="{{ asset('flags/' . $language['flag'] . '.svg') }}" alt=""
                                                class="flag-icon me-2">
                                            {{ $language['name'] }}
                                        </div>
                                    </a>
                                    @if (app()->getLocale() == $key)
                                        <i class="fas fa-check language-check"></i>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-center h-100">
                    @if (auth()->user()->role == 'superadmin')
                        <div class="notifications dropdown">
                            <a href="#" class="drop-notification-controller mt-1 me-3" data-bs-toggle="dropdown">
                                <i>
                                    <svg width="24" height="24" viewBox="0 0 21 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M9.52246 3.03984V0.75C9.52246 0.335999 9.86834 0 10.2945 0C10.7207 0 11.0666 0.335999 11.0666 0.75V3.03994C14.6782 3.41551 17.5001 6.39355 17.5001 9.99994V12.7881C17.5001 14.7671 18.3926 16.6349 19.948 17.913C20.3587 18.2531 20.5883 18.7379 20.5883 19.2501C20.5883 20.215 19.7802 21 18.7867 21H14.0768C13.7183 22.7098 12.1586 24 10.2941 24C8.42959 24 6.8699 22.7098 6.51132 21H1.80141C0.80806 21 0 20.215 0 19.2501C0 18.7379 0.229582 18.2531 0.629937 17.92C2.19573 16.6349 3.08823 14.7671 3.08823 12.7881V9.99994C3.08823 6.39325 5.91053 3.41502 9.52246 3.03984ZM10.2941 22.5C11.3011 22.5 12.1596 21.8732 12.4781 21H8.11011C8.42866 21.8732 9.28724 22.5 10.2941 22.5ZM1.80141 19.5H7.20584H13.3823H18.7867C18.9267 19.5 19.0442 19.3859 19.0442 19.2501C19.0442 19.1501 18.9874 19.088 18.9535 19.06C17.0481 17.495 15.9559 15.2089 15.9559 12.7881V9.99994C15.9559 6.96698 13.4164 4.5 10.2941 4.5C7.17189 4.5 4.63235 6.96698 4.63235 9.99994V12.7881C4.63235 15.2089 3.54024 17.495 1.63686 19.058C1.60066 19.088 1.54412 19.1501 1.54412 19.2501C1.54412 19.3859 1.66155 19.5 1.80141 19.5Z"
                                            fill="#15803D" />
                                    </svg>
                                </i>
                                <span class="badge-soft-danger position-absolute d-flex align-items-center justify-content-center">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="notification-header">
                                    <p>{{ __('You Have') }}
                                        <strong>{{ auth()->user()->unreadNotifications->count() }}</strong>
                                        {{ __('new Notifications') }}
                                    </p>
                                    <a href="{{ route('admin.notifications.mtReadAll') }}" class="text-danger">
                                        {{ __('Mark all Read') }}
                                    </a>
                                </div>
                                <ul>
                                    @foreach (auth()->user()->unreadNotifications as $notification)
                                        <li>
                                            <a href="{{ route('admin.notifications.mtView', $notification->id) }}">
                                                <strong>{{ __($notification->data['message'] ?? '') }}</strong>
                                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="notification-footer">
                                    <a class="text-danger" href="{{ route('admin.notifications.index') }}">
                                        {{ __('View all notifications') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="profile-info dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="d-flex align-items-center gap-2">
                        <img src="{{ asset(Auth::user()->image ?? 'assets/images/icons/default-user.png') }}" alt="Profile">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <p class="text-foreground">{{ auth()->user()->role == 'superadmin' ? __('Super Admin') : auth()->user()->name }}</p>
                            <i class="fas fa-chevron-down text-secondary"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="{{ url('cache-clear') }}">
                                <i class="far fa-undo"></i> {{ __('Clear cache') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.profiles.index') }}">
                                <i class="fal fa-user"></i> {{ __('My Profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="logoutButton">
                                <i class="far fa-sign-out"></i> {{ __('Logout') }}
                                <form action="{{ route('logout') }}" method="post" id="logoutForm">
                                    @csrf
                                </form>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</div>
