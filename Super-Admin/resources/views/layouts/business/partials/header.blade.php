<header class="main-header-section sticky-top d-print-none">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="sidebar-opner menu-opener"><i class="fal fa-bars" aria-hidden="true"></i></div>
            <a target="_blank" class="text-custom-primary view-website" href="{{ route('home') }}">
                {{ __('View Website') }}
                <i class="fas fa-chevron-double-right"></i>
            </a>

            <a class="pos-logo" href="javascript:void(0)"><img src="{{ asset(get_option('general')['common_header_logo'] ?? 'assets/images/logo/backend_logo.png') }}" alt="Logo"></a>
        </div>

        <div class=" d-flex align-items-center">
            @if (moduleCheck('MultiBranchAddon') && auth()->user()->active_branch_id)
            @php
                $branch = auth()->user()->active_branch;
            @endphp
            <a class="d-flex align-items-center gap-1 main-branch-btn exit-branch-btn" href="javascript:void(0)" data-title="Are you sure you want to exit from {{ $branch->name ?? '' }}?" data-exit-url="{{ route('multibranch.exit-branch', $branch->id ?? null) }}">
                <img src="{{ asset('assets/images/dashboard/main.svg') }}" alt="">
                <p>
                    {{ $branch->name ?? '' }}
                </p>
            </a>
            @endif
            <div class="language-change">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <img src="{{ asset('flags/' . languages()[app()->getLocale()]['flag'] . '.svg') }}"
                            alt="" class="flag-icon me-2">
                            <span class="lang-name">
                                {{ languages()[app()->getLocale()]['name'] }}
                            </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-scroll">
                        @foreach (languages() as $key => $language)
                            <li class="language-li">
                                <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['lang' => $key]) }}">
                                    <img src="{{ asset('flags/' . $language['flag'] . '.svg') }}" alt=""
                                        class="flag-icon me-2">
                                    {{ $language['name'] }}
                                </a>
                                @if (app()->getLocale() == $key)
                                    <i class="fas fa-check language-check"></i>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="notifications dropdown">
                <a href="#" class="dropdown-toggleer mt-1 me-2" data-bs-toggle="dropdown">
                    <i><img src="{{ asset('assets/images/icons/bel.svg') }}" alt=""></i>
                    <span class="bg-red">{{ auth()->user()->unreadNotifications->count() }}</span>
                </a>
                <div class="dropdown-menu notification-container">
                    <div class="notification-header ">
                        <a href="{{ route('business.notifications.mtReadAll') }}"
                            class="text-red">{{ __('Mark all Read') }}</a>
                    </div>
                    <ul>
                        @foreach (auth()->user()->unreadNotifications  as $notification)
                            <li>
                                <a href="{{ route('business.notifications.mtView', $notification->id) }}">
                                    <strong>{{ __($notification->data['message'] ?? '') }}</strong>
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="notification-footer">
                        <a class="text-red" href="{{ route('business.notifications.index') }}">{{ __('View all notifications') }}</a>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-center">
                <div class="profile-info dropdown">
                    <a href="#" data-bs-toggle="dropdown">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="greet-name">
                                <p class="nav-greeting">{{__('Hello')}}🖐</p>
                                <h6 class="nav-name">
                                    {{ auth()->user()->role == 'staff' ? (optional(auth()->user()->business)->companyName . ' [' . auth()->user()->name . ']')  : optional(auth()->user()->business)->companyName }}
                                </h6>
                            </div>
                            <img src="{{ asset(auth()->user()->image ?? 'assets/images/icons/default-user.png') }}" alt="Profile">
                        </div>
                    </a>
                    <div class=" business-profile bg-success">

                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('business.profiles.index') }}"> <i class="fal fa-user"></i>
                                    {{ __('My Profile') }}
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
                <div class="sidebar-opner menu-openerr"><i class="fal fa-bars" aria-hidden="true"></i></div>
            </div>
        </div>
    </div>
</header>

@push('modal')
    <div class="modal fade custom-modal" id="exitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <img src="{{ asset('assets/images/dashboard/main2.svg') }}" alt="">
                    <h5 class="exit-title">{{__('Are you sure you want to exit?')}}</h5>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn-no" data-bs-dismiss="modal">{{__('No')}}</button>
                        <a href="javascript:void(0)" class="btn-yes exit-branch">{{__('Yes')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush
