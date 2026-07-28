<div class="sidebar-container">
    <nav class="side-bar">
        <div class="side-bar-logo">
            <a href="{{ route('admin.dashboard.index') }}"><img
                    src="{{ asset(get_option('general')['admin_logo'] ?? 'assets/images/logo/backend_logo.png') }}"
                    alt="Logo"></a>

            <button class="close-btn"><i class="fal fa-times"></i></button>
        </div>
        <div class="side-bar-manu">
            <ul>
                @can('dashboard-read')
                    <li class="{{ Request::routeIs('admin.dashboard.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard.index') }}" class="active">
                            <span class="sidebar-icon">

                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2632_3521)">
                                        <path
                                            d="M12.5001 14.1667C11.8339 14.6854 10.9586 15 10.0001 15C9.04156 15 8.1664 14.6854 7.50012 14.1667"
                                            stroke="#00987F" stroke-width="1.5" stroke-linecap="round" />
                                        <path
                                            d="M1.95963 11.0113C1.66545 9.09685 1.51836 8.13971 1.88028 7.29117C2.2422 6.44262 3.04517 5.86205 4.65109 4.7009L5.85097 3.83335C7.84872 2.38891 8.84758 1.66669 10.0002 1.66669C11.1527 1.66669 12.1516 2.38891 14.1493 3.83335L15.3492 4.7009C16.9552 5.86205 17.7581 6.44262 18.12 7.29117C18.4819 8.13971 18.3348 9.09685 18.0407 11.0113L17.7898 12.6437C17.3727 15.3574 17.1642 16.7144 16.191 17.5239C15.2177 18.3334 13.7948 18.3334 10.9492 18.3334H9.05116C6.20543 18.3334 4.78257 18.3334 3.80931 17.5239C2.83605 16.7144 2.62753 15.3574 2.2105 12.6437L1.95963 11.0113Z"
                                            stroke="#00987F" stroke-width="1.5" stroke-linejoin="round" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2632_3521">
                                            <rect width="20" height="20" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>

                            </span>
                            {{ __('Dashboard') }}
                        </a>
                    </li>
                @endcan

                @can('business-read')
                <li
                    class="{{ Request::routeIs('admin.business.index', 'admin.business.create', 'admin.business.edit') ? 'active' : '' }}">
                    <a href="{{ route('admin.business.index') }}" class="active">
                        <span class="sidebar-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_2632_3533)">
                                    <path
                                        d="M2.47235 8.74652V12.9147C2.47235 15.2726 2.47235 16.4515 3.20458 17.184C3.93682 17.9165 5.11533 17.9165 7.47235 17.9165H12.4724C14.8294 17.9165 16.0079 17.9165 16.7401 17.184C17.4724 16.4515 17.4724 15.2726 17.4724 12.9147V8.74652"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    <path d="M5.80566 14.9941H9.139" stroke="white" stroke-width="1.5"
                                        stroke-linecap="round" />
                                    <path
                                        d="M8.41985 7.0151C8.18487 7.86377 7.33025 9.32775 5.70649 9.53992C4.27279 9.72725 3.1854 9.10142 2.90765 8.83975C2.60142 8.62758 1.90348 7.94863 1.73257 7.5243C1.56164 7.09997 1.76105 6.18057 1.90348 5.80575L2.47288 4.15709C2.61188 3.74299 2.93727 2.76357 3.27086 2.4323C3.60446 2.10102 4.27986 2.08662 4.55785 2.08662H10.3958C11.8984 2.10784 15.1841 2.07322 15.8336 2.08662C16.4831 2.10002 16.8734 2.64447 16.9874 2.87785C17.9564 5.2252 18.3334 6.56955 18.3334 7.1424C18.2069 7.75349 17.6834 8.90575 15.8336 9.41258C13.9111 9.93933 12.8212 8.91483 12.4793 8.5215M7.62937 8.5215C7.89999 8.85383 8.74893 9.52292 9.97952 9.53992C11.2102 9.55683 12.2728 8.69825 12.6502 8.26688C12.757 8.13958 12.9878 7.76192 13.2271 7.0151"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_2632_3533">
                                        <rect width="20" height="20" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>

                        </span>
                        {{ __('Store List') }}
                    </a>
                </li>
                @endcan

                @can('business-categories-read')
                  <li
                    class="{{ Request::routeIs('admin.business-categories.index', 'admin.business-categories.create', 'admin.business-categories.edit') ? 'active' : '' }}">
                <a href="{{ route('admin.business-categories.index') }}" class="active">
                    <span class="sidebar-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.16669 4.58331H17.5" stroke="white" stroke-width="1.25"
                                stroke-linecap="round" />
                            <path
                                d="M4.5 14.0772C5.38889 14.6725 5.83333 14.9702 5.83333 15.4167C5.83333 15.8632 5.38889 16.1608 4.5 16.7562C3.61111 17.3515 3.16667 17.6491 2.83333 17.4259C2.5 17.2027 2.5 16.6073 2.5 15.4167C2.5 14.226 2.5 13.6307 2.83333 13.4074C3.16667 13.1842 3.61111 13.4818 4.5 14.0772Z"
                                stroke="white" stroke-width="1.25" stroke-linecap="round" />
                            <path
                                d="M4.5 3.24386C5.38889 3.83918 5.83333 4.13684 5.83333 4.58333C5.83333 5.02982 5.38889 5.32748 4.5 5.92281C3.61111 6.51813 3.16667 6.81579 2.83333 6.59254C2.5 6.3693 2.5 5.77397 2.5 4.58333C2.5 3.39269 2.5 2.79737 2.83333 2.57412C3.16667 2.35087 3.61111 2.64853 4.5 3.24386Z"
                                stroke="white" stroke-width="1.25" stroke-linecap="round" />
                            <path d="M9.16669 10H17.5" stroke="white" stroke-width="1.25" stroke-linecap="round" />
                            <path d="M9.16669 15.4167H17.5" stroke="white" stroke-width="1.25"
                                stroke-linecap="round" />
                        </svg>

                    </span>
                    {{ __('Category List') }}
                </a>
               </li>
            @endcan

                @can('banners-read')
                    <li
                        class="{{ Request::routeIs('admin.banners.index', 'admin.banners.create', 'admin.banners.edit') ? 'active' : '' }}">
                        <a href="{{ route('admin.banners.index') }}" class="active">
                            <span class="sidebar-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12.4386 2.42588L6.8946 5.08712C6.46793 5.29193 6.01203 5.34324 5.54729 5.23913C5.24314 5.17099 5.09105 5.13693 4.96858 5.12294C3.44786 4.94929 2.5 6.15287 2.5 7.53691V8.29646C2.5 9.68052 3.44786 10.8841 4.96858 10.7104C5.09105 10.6964 5.24315 10.6624 5.54729 10.5943C6.01203 10.4901 6.46793 10.5414 6.8946 10.7463L12.4386 13.4075C13.7112 14.0184 14.3475 14.3239 15.057 14.0858C15.7664 13.8477 16.0099 13.3368 16.497 12.315C17.8343 9.50935 17.8343 6.32406 16.497 3.51832C16.0099 2.49657 15.7664 1.98569 15.057 1.7476C14.3475 1.50952 13.7112 1.81497 12.4386 2.42588Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M9.5484 17.3091L8.3056 18.3333C5.50427 16.1116 5.84651 15.0521 5.84651 10.8333H6.79137C7.1748 13.2174 8.07925 14.3466 9.32723 15.1641C10.096 15.6676 10.2545 16.7271 9.5484 17.3091Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M6.25 10.4167V5.41669" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            {{ __('Advertising') }}
                        </a>
                    </li>
                @endcan

                @can('prescriptions-read')
                    <li
                        class="{{ Request::routeIs('admin.prescriptions.index', 'admin.prescriptions.create', 'admin.prescriptions.edit') ? 'active' : '' }}">
                        <a href="{{ route('admin.prescriptions.index') }}" class="active">
                            <span class="sidebar-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            {{ __('Prescriptions') }}
                        </a>
                    </li>
                @endcan

                @canany(['plans-read', 'plans-create'])
                    <li
                        class="dropdown {{ Route::is('admin.plans.index', 'admin.plans.create', 'admin.plans.edit') ? 'active' : '' }}">
                        <a href="#">
                            <span class="sidebar-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M2.5 4.26082C2.5 3.80026 2.5 3.56998 2.53574 3.37813C2.6942 2.52762 3.35471 1.8623 4.19907 1.70269C4.38954 1.66669 4.61816 1.66669 5.0754 1.66669H14.9246C15.3818 1.66669 15.6104 1.66669 15.8009 1.70269C16.6452 1.8623 17.3058 2.52762 17.4642 3.37813C17.5 3.56998 17.5 3.80026 17.5 4.26082C17.5 4.71269 17.5 4.93861 17.4737 5.14811C17.3591 6.06286 16.876 6.88986 16.1378 7.43509C15.9687 7.55995 15.7727 7.66968 15.3805 7.88912L13.2376 9.08827C11.6551 9.97385 10.8637 10.4167 10 10.4167C9.13625 10.4167 8.34492 9.97385 6.76238 9.08827L4.61948 7.88912C4.22733 7.66968 4.03127 7.55995 3.86221 7.43509C3.12404 6.88986 2.64095 6.06286 2.52627 5.14811C2.5 4.93861 2.5 4.71269 2.5 4.26082Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    <path d="M6.66669 4.16669V5.00002M10 4.16669V6.66669M13.3334 4.16669V5.00002"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    <path
                                        d="M10.6478 11.3749L11.3077 12.7057C11.3977 12.8909 11.6377 13.0686 11.8402 13.1026L13.0363 13.303C13.8012 13.4315 13.9812 13.9911 13.4301 14.543L12.5002 15.4806C12.3427 15.6394 12.2564 15.9456 12.3052 16.1649L12.5714 17.3255C12.7814 18.2442 12.2977 18.5996 11.4915 18.1194L10.3704 17.4503C10.1679 17.3294 9.83417 17.3294 9.628 17.4503L8.50683 18.1194C7.70444 18.5996 7.21699 18.2404 7.42697 17.3255L7.69319 16.1649C7.74193 15.9456 7.65569 15.6394 7.49821 15.4806L6.56832 14.543C6.02087 13.9911 6.19711 13.4315 6.96202 13.303L8.15814 13.1026C8.35683 13.0686 8.59683 12.8909 8.68683 12.7057L9.34675 11.3749C9.70675 10.6529 10.2917 10.6529 10.6478 11.3749Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            {{ __('Subscription Plan') }}
                        </a>
                        <ul>
                            @can('plans-create')
                                <li><a class="{{ Route::is('admin.plans.create') ? 'active' : '' }}"
                                        href="{{ route('admin.plans.create') }}">{{ __('Create Plan') }}</a></li>
                            @endcan
                            @can('plans-read')
                                <li><a class="{{ Route::is('admin.plans.index', 'admin.plans.edit') ? 'active' : '' }}"
                                        href="{{ route('admin.plans.index') }}">{{ __('Manage Plans') }}</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['subscription-reports-read','manual-payment-reports-read','active-store-reports-read','expired-store-reports-read'])
                    <li class="dropdown {{ Route::is('admin.subscription-reports.index','admin.manual-payments.index', 'admin.expired-business.index', 'admin.active-stores.index') ? 'active' : '' }}">
                        <a href="#">
                            <span class="sidebar-icon">
                                <svg width="20" height="21" viewBox="0 0 20 21" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12.4838 6.36633C12.4838 6.36633 12.9004 6.783 13.3171 7.61633C13.3171 7.61633 14.6406 5.533 15.8171 5.11633"
                                        stroke="white" stroke-width="1.25" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M8.32906 2.20446C6.247 2.11631 4.63845 2.35616 4.63845 2.35616C3.62275 2.42879 1.67624 2.99822 1.67625 6.32379C1.67627 9.62109 1.65472 13.6861 1.67625 15.3066C1.67625 16.2967 2.28927 18.6061 4.41107 18.7298C6.9901 18.8803 11.6356 18.9123 13.7671 18.7298C14.3376 18.6977 16.2372 18.2498 16.4776 16.183C16.7267 14.0419 16.6771 12.5539 16.6771 12.1998"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M18.3333 6.36631C18.3333 8.6675 16.466 10.533 14.1626 10.533C11.8592 10.533 9.99194 8.6675 9.99194 6.36631C9.99194 4.06513 11.8592 2.19965 14.1626 2.19965C16.466 2.19965 18.3333 4.06513 18.3333 6.36631Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    <path d="M5.81714 11.3663H9.15045" stroke="white" stroke-width="1.5"
                                        stroke-linecap="round" />
                                    <path d="M5.81714 14.6997H12.4838" stroke="white" stroke-width="1.5"
                                        stroke-linecap="round" />
                                </svg>
                            </span>
                            {{ __('Reports') }}
                        </a>
                        <ul>
                            @can('subscription-reports-read')
                                <li><a class="{{ Route::is('admin.subscription-reports.index') ? 'active' : '' }}"
                                        href="{{ route('admin.subscription-reports.index') }}">{{ __('Subscription Report') }}</a>
                                </li>
                            @endcan
                            @can('manual-payment-reports-read')
                            <li><a class="{{ Route::is('admin.manual-payments.index') ? 'active' : '' }}"
                                href="{{ route('admin.manual-payments.index') }}">{{ __('Manual Payment') }}</a>
                            </li>
                            @endcan
                            @can('active-store-reports-read')
                            <li><a class="{{ Route::is('admin.active-stores.index') ? 'active' : '' }}"
                                href="{{ route('admin.active-stores.index') }}">{{ __('Active Store') }}</a>
                            </li>
                            @endcan
                            @can('expired-store-reports-read')
                            <li><a class="{{ Route::is('admin.expired-business.index') ? 'active' : '' }}"
                                href="{{ route('admin.expired-business.index') }}">{{ __('Expired Store') }}</a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('users-read')
                <li
                    class="dropdown {{ Request::routeIs('admin.users.index', 'admin.users.create', 'admin.users.edit') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M17.3116 15C17.936 15 18.4327 14.6071 18.8786 14.0576C19.7915 12.9328 18.2927 12.034 17.721 11.5938C17.1399 11.1463 16.4911 10.8928 15.8333 10.8333M14.9999 9.16667C16.1505 9.16667 17.0833 8.23392 17.0833 7.08333C17.0833 5.93274 16.1505 5 14.9999 5"
                                    stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                <path
                                    d="M2.68822 15C2.0638 15 1.56715 14.6071 1.1212 14.0576C0.208328 12.9328 1.70715 12.034 2.27879 11.5938C2.8599 11.1463 3.50874 10.8928 4.16659 10.8333M4.58325 9.16667C3.43266 9.16667 2.49992 8.23392 2.49992 7.08333C2.49992 5.93274 3.43266 5 4.58325 5"
                                    stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                <path
                                    d="M6.73642 12.5927C5.88494 13.1192 3.6524 14.1943 5.01217 15.5395C5.6764 16.1967 6.41619 16.6667 7.34627 16.6667H12.6536C13.5837 16.6667 14.3234 16.1967 14.9877 15.5395C16.3474 14.1943 14.1149 13.1192 13.2634 12.5927C11.2667 11.358 8.7331 11.358 6.73642 12.5927Z"
                                    stroke="white" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M12.9166 6.24998C12.9166 7.86081 11.6108 9.16665 9.99994 9.16665C8.3891 9.16665 7.08325 7.86081 7.08325 6.24998C7.08325 4.63915 8.3891 3.33331 9.99994 3.33331C11.6108 3.33331 12.9166 4.63915 12.9166 6.24998Z"
                                    stroke="white" stroke-width="1.5" />
                            </svg>
                        </span>
                        {{ __('Staff Manage') }} </a>
                    <ul>
                        <li><a class="{{ Request::routeIs('admin.users.create') ? 'active' : '' }}"
                                href="{{ route('admin.users.create') }}">{{ __('Create Staff') }}</a></li>
                        <li><a class="{{ Request::routeIs('admin.users.index', 'admin.users.edit') ? 'active' : '' }}"
                                href="{{ route('admin.users.index') }}">{{ __('Manage Staff') }}</a></li>
                    </ul>
                </li>
            @endcan

                @canany(['roles-read', 'permissions-read'])
                    <li
                        class="dropdown {{ Request::routeIs('admin.roles.index', 'admin.roles.create', 'admin.roles.edit', 'admin.permissions.index') ? 'active' : '' }}">
                        <a href="#">
                            <span class="sidebar-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2632_6629)">
                                        <path d="M10 18.3333L8.33335 13.3333H1.66669L3.33335 18.3333H10ZM10 18.3333H13.3334"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M9.99998 10.8333V10.4166C9.99998 8.84531 9.99998 8.05962 9.51181 7.57147C9.02365 7.08331 8.238 7.08331 6.66665 7.08331C5.0953 7.08331 4.30962 7.08331 3.82147 7.57147C3.33331 8.05962 3.33331 8.84531 3.33331 10.4166V10.8333"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M15.8333 10.8334C15.8333 11.7539 15.0872 12.5 14.1667 12.5C13.2462 12.5 12.5 11.7539 12.5 10.8334C12.5 9.91285 13.2462 9.16669 14.1667 9.16669C15.0872 9.16669 15.8333 9.91285 15.8333 10.8334Z"
                                            stroke="white" stroke-width="1.5" />
                                        <path
                                            d="M8.33333 3.33335C8.33333 4.25383 7.58714 5.00002 6.66667 5.00002C5.74619 5.00002 5 4.25383 5 3.33335C5 2.41288 5.74619 1.66669 6.66667 1.66669C7.58714 1.66669 8.33333 2.41288 8.33333 3.33335Z"
                                            stroke="white" stroke-width="1.5" />
                                        <path
                                            d="M11.6667 14.5833H16.6667C17.5872 14.5833 18.3334 15.3295 18.3334 16.25V16.6666C18.3334 17.5871 17.5872 18.3333 16.6667 18.3333H15.8334"
                                            stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2632_6629">
                                            <rect width="20" height="20" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                            {{ __('Roles & Permissions') }}
                        </a>
                        <ul>
                            @can('roles-read')
                                <li>
                                    <a class="{{ Request::routeIs('admin.roles.index', 'admin.roles.create', 'admin.roles.edit') ? 'active' : '' }}"
                                        href="{{ route('admin.roles.index') }}">{{ __('Roles') }}</a>
                                </li>
                            @endcan
                            @can('permissions-read')
                                <li>
                                    <a class="{{ Request::routeIs('admin.permissions.index') ? 'active' : '' }}"
                                        href="{{ route('admin.permissions.index') }}">{{ __('Permissions') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['web-settings-read', 'features-read', 'blogs-read', 'testimonials-read', 'interfaces-read', 'term-condition-read', 'privacy-policy-read', 'messages-read'])
                <li class="dropdown {{ Route::is('admin.website-settings.index', 'admin.features.index', 'admin.features.create', 'admin.features.edit', 'admin.blogs.index', 'admin.comments.index', 'admin.testimonials.index', 'admin.interfaces.index', 'admin.term-conditions.index', 'admin.privacy-policy.index', 'admin.blogs.filter.comment', 'admin.testimonials.create', 'admin.testimonials.edit', 'admin.interfaces.create', 'admin.interfaces.edit', 'admin.blogs.create', 'admin.blogs.edit', 'admin.messages.index') ? 'active' : '' }}">
                    <a href="#">
                        <span class="sidebar-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.08337 9.99967C2.08337 6.26772 2.08337 4.40175 3.24274 3.24237C4.40212 2.08301 6.26809 2.08301 10 2.08301C13.732 2.08301 15.598 2.08301 16.7574 3.24237C17.9167 4.40175 17.9167 6.26772 17.9167 9.99967C17.9167 13.7316 17.9167 15.5976 16.7574 16.757C15.598 17.9163 13.732 17.9163 10 17.9163C6.26809 17.9163 4.40212 17.9163 3.24274 16.757C2.08337 15.5976 2.08337 13.7316 2.08337 9.99967Z" stroke="white" stroke-width="1.5"/>
                                <path d="M2.08337 7.5H17.9167" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                                <path d="M10.8334 10.833H14.1667" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10.8334 14.167H12.5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83313 5H5.84061" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.1665 5H9.174" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7.5 7.5V17.9167" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        {{ __('Frontend Module') }}
                    </a>
                    <ul>
                        @can('web-settings-read')
                        <li>
                            <a class="{{ Request::routeIs('admin.website-settings.index') ? 'active' : '' }}" href="{{ route('admin.website-settings.index') }}">{{ __('Manage Pages') }}</a>
                        </li>
                        @endcan
                        @can('features-read')
                        <li><a class="{{ Request::routeIs('admin.features.index', 'admin.features.create', 'admin.features.edit') ? 'active' : '' }}" href="{{ route('admin.features.index') }}">{{ __('Features') }}</a></li>
                        @endcan
                        @can('blogs-read')
                        <li><a class="{{ Request::routeIs('admin.blogs.index', 'admin.blogs.filter.comment', 'admin.blogs.create', 'admin.blogs.edit') ? 'active' : '' }}" href="{{ route('admin.blogs.index') }}">{{ __('Blogs') }}</a></li>
                        @endcan
                        @can('testimonials-read')
                        <li><a class="{{ Request::routeIs('admin.testimonials.index', 'admin.testimonials.create', 'admin.testimonials.edit') ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}">{{ __('Testimonials') }}</a></li>
                        @endcan
                        @can('interfaces-read')
                        <li><a class="{{ Request::routeIs('admin.interfaces.index', 'admin.interfaces.create', 'admin.interfaces.edit') ? 'active' : '' }}" href="{{ route('admin.interfaces.index') }}">{{ __('Interfaces') }}</a></li>
                        @endcan
                        @can('term-condition-read')
                        <li>
                            <a class="{{ Request::routeIs('admin.term-conditions.index') ? 'active' : '' }}" href="{{ route('admin.term-conditions.index') }}">{{ __('Terms & Conditions') }}</a>
                        </li>
                        @endcan
                        @can('privacy-policy-read')
                        <li>
                            <a class="{{ Request::routeIs('admin.privacy-policy.index') ? 'active' : '' }}" href="{{ route('admin.privacy-policy.index') }}">{{ __('Privacy & Policy') }}</a>
                        </li>
                        @endcan
                        @can('messages-read')
                        <li><a class="{{ Request::routeIs('admin.messages.index') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}">{{ __('Messages') }}</a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                @canany(['settings-read', 'notifications-read', 'currencies-read', 'gateways-read', 'addons-read'])
                    <li
                        class="dropdown {{ Request::routeIs('admin.settings.index', 'admin.notifications.index', 'admin.system-settings.index', 'admin.currencies.index', 'admin.currencies.create', 'admin.currencies.edit', 'admin.sms-settings.index', 'admin.gateways.index', 'admin.addons.index') ? 'active' : '' }}">
                        <a href="#">
                            <span class="sidebar-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12.9166 9.99998C12.9166 11.6108 11.6108 12.9166 9.99998 12.9166C8.38915 12.9166 7.08331 11.6108 7.08331 9.99998C7.08331 8.38915 8.38915 7.08331 9.99998 7.08331C11.6108 7.08331 12.9166 8.38915 12.9166 9.99998Z"
                                        stroke="white" stroke-width="1.5" />
                                    <path
                                        d="M17.5092 11.7471C17.9441 11.6298 18.1616 11.5712 18.2474 11.459C18.3334 11.3469 18.3334 11.1665 18.3334 10.8058V9.19435C18.3334 8.8336 18.3334 8.65318 18.2474 8.5411C18.1615 8.42893 17.9441 8.37026 17.5092 8.253C15.8839 7.81467 14.8666 6.11544 15.2861 4.50074C15.4014 4.05667 15.4591 3.83465 15.404 3.70442C15.3489 3.5742 15.1909 3.48446 14.8748 3.30498L13.4375 2.48896C13.1274 2.31284 12.9723 2.22478 12.8331 2.24353C12.6939 2.26228 12.5369 2.41896 12.2227 2.73229C11.0067 3.94541 8.99469 3.94536 7.77864 2.73221C7.46455 2.41887 7.3075 2.26221 7.16829 2.24345C7.02909 2.2247 6.874 2.31276 6.5638 2.48887L5.12655 3.30491C4.81046 3.48437 4.65241 3.57411 4.59734 3.70431C4.54225 3.83451 4.59991 4.05657 4.71523 4.50068C5.1345 6.11543 4.11645 7.81471 2.49087 8.25301C2.05595 8.37026 1.8385 8.42893 1.75259 8.54101C1.66669 8.65318 1.66669 8.8336 1.66669 9.19435V10.8058C1.66669 11.1665 1.66669 11.3469 1.75259 11.459C1.83848 11.5712 2.05595 11.6298 2.49087 11.7471C4.11619 12.1854 5.13342 13.8847 4.71395 15.4993C4.5986 15.9434 4.54091 16.1654 4.59599 16.2957C4.65107 16.4259 4.80912 16.5157 5.12523 16.6951L6.56248 17.5112C6.8727 17.6873 7.0278 17.7753 7.16702 17.7566C7.30624 17.7378 7.46325 17.5811 7.77728 17.2678C8.99394 16.0537 11.0074 16.0536 12.2241 17.2677C12.5381 17.5811 12.6951 17.7378 12.8344 17.7565C12.9735 17.7753 13.1287 17.6872 13.4389 17.5111L14.8761 16.695C15.1923 16.5156 15.3504 16.4258 15.4054 16.2956C15.4604 16.1653 15.4028 15.9433 15.2874 15.4993C14.8677 13.8847 15.8841 12.1855 17.5092 11.7471Z"
                                        stroke="white" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </span>
                            {{ __('Settings') }}
                        </a>
                        <ul>
                            @can('currencies-read')
                                <li><a class="{{ Request::routeIs('admin.currencies.index', 'admin.currencies.create', 'admin.currencies.edit') ? 'active' : '' }}"
                                        href="{{ route('admin.currencies.index') }}">{{ __('Currencies') }}</a></li>
                            @endcan
                            @can('notifications-read')
                                <li>
                                    <a class="{{ Request::routeIs('admin.notifications.index') ? 'active' : '' }}"
                                        href="{{ route('admin.notifications.index') }}">{{ __('Notifications') }}</a>
                                </li>
                            @endcan
                            @can('gateways-read')
                                <li>
                                    <a class="{{ Request::routeIs('admin.gateways.index') ? 'active' : '' }}"
                                        href="{{ route('admin.gateways.index') }}">{{ __('Payment Gateway') }}</a>
                                </li>
                            @endcan
                            @can('settings-read')
                                <li>
                                    <a class="{{ Request::routeIs('admin.system-settings.index') ? 'active' : '' }}"
                                        href="{{ route('admin.system-settings.index') }}">{{ __('System Settings') }}</a>
                                </li>
                                <li>
                                    <a class="{{ Request::routeIs('admin.settings.index') ? 'active' : '' }}"
                                        href="{{ route('admin.settings.index') }}">{{ __('General Settings') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
            </ul>
        </div>
    </nav>
</div>
