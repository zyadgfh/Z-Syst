@extends('layouts.master')

@section('title')
    {{ __('Settings') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Settings') }}</h4>
                    </div>

                    <ul class="nav nav-tabs " id="settingsTab" role="tablist">
                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link active" id="all-tab" data-bs-toggle="tab"
                                data-bs-target="#all" type="button" role="tab">
                                {{__('All Settings')}}
                            </button>
                        </li>

                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link" id="general-tab" data-bs-toggle="tab"
                                data-bs-target="#general" type="button" role="tab">
                                {{__('General')}}
                            </button>
                        </li>

                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link" id="system-tab" data-bs-toggle="tab"
                                data-bs-target="#system" type="button" role="tab">
                                {{__('System')}}
                            </button>
                        </li>

                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link" id="currencies-tab" data-bs-toggle="tab"
                                data-bs-target="#currencies" type="button" role="tab">
                                {{__('Currencies')}}
                            </button>
                        </li>

                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link" id="otp-tab" data-bs-toggle="tab"
                                data-bs-target="#otp" type="button" role="tab">
                                {{__('User Sign Up')}}
                            </button>
                        </li>

                        @if (moduleCheck('CustomDomainAddon'))
                        <li class="nav-item settings-item" role="presentation">
                            <button class="nav-link settings-link" id="domain-tab" data-bs-toggle="tab" data-bs-target="#domain" type="button" role="tab">
                                {{__('Domain Setting')}}
                            </button>
                        </li>
                        @endif
                    </ul>

                    <div class="tab-content mt-3" id="settingsTabContent">
                        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            <div class="settings-box-container">
                                <div>
                                    <a href="{{ route('admin.settings.index') }}" class="text-decoration-none text-dark">
                                        <div class=" setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M15.5 12C15.5 13.933 13.933 15.5 12 15.5C10.067 15.5 8.5 13.933 8.5 12C8.5 10.067 10.067 8.5 12 8.5C13.933 8.5 15.5 10.067 15.5 12Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M21.011 14.0949C21.5329 13.9542 21.7939 13.8838 21.8969 13.7492C22 13.6147 22 13.3982 22 12.9653V11.0316C22 10.5987 22 10.3822 21.8969 10.2477C21.7938 10.1131 21.5329 10.0427 21.011 9.90194C19.0606 9.37595 17.8399 7.33687 18.3433 5.39923C18.4817 4.86635 18.5509 4.59992 18.4848 4.44365C18.4187 4.28738 18.2291 4.1797 17.8497 3.96432L16.125 2.98509C15.7528 2.77375 15.5667 2.66808 15.3997 2.69058C15.2326 2.71308 15.0442 2.90109 14.6672 3.27709C13.208 4.73284 10.7936 4.73278 9.33434 3.277C8.95743 2.90099 8.76898 2.71299 8.60193 2.69048C8.43489 2.66798 8.24877 2.77365 7.87653 2.98499L6.15184 3.96423C5.77253 4.17959 5.58287 4.28727 5.51678 4.44351C5.45068 4.59976 5.51987 4.86623 5.65825 5.39916C6.16137 7.33686 4.93972 9.37599 2.98902 9.90196C2.46712 10.0427 2.20617 10.1131 2.10308 10.2476C2 10.3822 2 10.5987 2 11.0316V12.9653C2 13.3982 2 13.6147 2.10308 13.7492C2.20615 13.8838 2.46711 13.9542 2.98902 14.0949C4.9394 14.6209 6.16008 16.66 5.65672 18.5976C5.51829 19.1305 5.44907 19.3969 5.51516 19.5532C5.58126 19.7095 5.77092 19.8172 6.15025 20.0325L7.87495 21.0118C8.24721 21.2231 8.43334 21.3288 8.6004 21.3063C8.76746 21.2838 8.95588 21.0957 9.33271 20.7197C10.7927 19.2628 13.2088 19.2627 14.6689 20.7196C15.0457 21.0957 15.2341 21.2837 15.4012 21.3062C15.5682 21.3287 15.7544 21.223 16.1266 21.0117L17.8513 20.0324C18.2307 19.8171 18.4204 19.7094 18.4864 19.5531C18.5525 19.3968 18.4833 19.1304 18.3448 18.5975C17.8412 16.66 19.0609 14.621 21.011 14.0949Z"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('General Settings')}}</h6>
                                                    <small class="text-muted d-block">{{__('Configure the fundamental information of the site.')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <div>
                                    <a href="{{ route('admin.notifications.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M5.49235 11.491C5.41887 12.887 5.50334 14.373 4.25611 15.3084C3.67562 15.7438 3.33398 16.427 3.33398 17.1527C3.33398 18.1508 4.11578 19 5.13398 19H19.534C20.5522 19 21.334 18.1508 21.334 17.1527C21.334 16.427 20.9924 15.7438 20.4119 15.3084C19.1646 14.373 19.2491 12.887 19.1756 11.491C18.9841 7.85223 15.9778 5 12.334 5C8.69015 5 5.68386 7.85222 5.49235 11.491Z"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                        <path
                                                            d="M10.834 3.125C10.834 3.95343 11.5056 5 12.334 5C13.1624 5 13.834 3.95343 13.834 3.125C13.834 2.29657 13.1624 2 12.334 2C11.5056 2 10.834 2.29657 10.834 3.125Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M15.334 19C15.334 20.6569 13.9909 22 12.334 22C10.6771 22 9.33398 20.6569 9.33398 19"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('Notifications')}}</h6>
                                                    <small class="text-muted d-block">{{__('Control and configure overall notification systems')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <div>
                                    <a href="{{ route('admin.system-settings.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                   <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M20.5 16.5V8.5C20.5 6.14298 20.5 4.96447 19.7677 4.23223C19.0355 3.5 17.857 3.5 15.5 3.5H8.5C6.14298 3.5 4.96446 3.5 4.23223 4.23223C3.5 4.96447 3.5 6.14298 3.5 8.5V16.5" stroke="#C52127" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M21.9842 20.5H2.01579C1.63285 20.5 1.38379 20.1088 1.55505 19.7764L3.5 16.5H20.5L22.4449 19.7764C22.6162 20.1088 22.3671 20.5 21.9842 20.5Z" stroke="#C52127" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M12 12V13.5M12 12C12.737 12 13.3809 11.6013 13.7278 11.0079M12 12C11.263 12 10.6191 11.6013 10.2721 11.0079M13.7278 11.0079L15 11.75M13.7278 11.0079C13.9008 10.7119 14 10.3676 14 10C14 9.63244 13.9008 9.28805 13.7278 8.99209M10.2721 11.0079L9 11.75M10.2721 11.0079C10.0991 10.712 10 10.3676 10 10C10 9.63244 10.0991 9.28804 10.2721 8.99209M12 8V6.5M12 8C12.737 8 13.3809 8.39866 13.7278 8.99209M12 8C11.263 8 10.6191 8.39865 10.2721 8.99209M13.7278 8.99209L15 8.25M10.2721 8.99209L9 8.25" stroke="#C52127" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>

                                                </div>
                                                <div>
                                                    <h6 class="">{{__('System')}}</h6>
                                                    <small class="text-muted d-block">{{__('View and update system settings')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <div>
                                    <a href="{{ route('admin.gateways.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13.5 15H6C4.11438 15 3.17157 15 2.58579 14.4142C2 13.8284 2 12.8856 2 11V7C2 5.11438 2 4.17157 2.58579 3.58579C3.17157 3 4.11438 3 6 3H18C19.8856 3 20.8284 3 21.4142 3.58579C22 4.17157 22 5.11438 22 7V12C22 12.9319 22 13.3978 21.8478 13.7654C21.6448 14.2554 21.2554 14.6448 20.7654 14.8478C20.3978 15 19.9319 15 19 15" stroke="#C52127" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M14 9C14 10.1045 13.1046 11 12 11C10.8954 11 10 10.1045 10 9C10 7.89543 10.8954 7 12 7C13.1046 7 14 7.89543 14 9Z" stroke="#C52127" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M13 17C13 15.3431 14.3431 14 16 14V12C16 10.3431 17.3431 9 19 9V14.5C19 16.8346 19 18.0019 18.5277 18.8856C18.1548 19.5833 17.5833 20.1548 16.8856 20.5277C16.0019 21 14.8346 21 12.5 21H12C10.1362 21 9.20435 21 8.46927 20.6955C7.48915 20.2895 6.71046 19.5108 6.30448 18.5307C6 17.7956 6 16.8638 6 15" stroke="#C52127" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>

                                                </div>
                                                <div>
                                                    <h6 class="">{{__('Payment Gateway')}}</h6>
                                                    <small class="text-muted d-block">{{__('View and update payment gateway settings')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <div>
                                    <a href="{{ route('admin.currencies.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M3.16602 12C3.16602 7.77027 3.16602 5.6554 4.36399 4.25276C4.5341 4.05358 4.7196 3.86808 4.91878 3.69797C6.32142 2.5 8.43629 2.5 12.666 2.5C16.8957 2.5 19.0106 2.5 20.4132 3.69797C20.6124 3.86808 20.7979 4.05358 20.968 4.25276C22.166 5.6554 22.166 7.77027 22.166 12C22.166 16.2297 22.166 18.3446 20.968 19.7472C20.7979 19.9464 20.6124 20.1319 20.4132 20.302C19.0106 21.5 16.8957 21.5 12.666 21.5C8.43629 21.5 6.32142 21.5 4.91878 20.302C4.7196 20.1319 4.5341 19.9464 4.36399 19.7472C3.16602 18.3446 3.16602 16.2297 3.16602 12Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M15.3762 10.063C15.2771 9.30039 14.4014 8.06817 12.8268 8.06814C10.9972 8.06811 10.2274 9.08141 10.0712 9.58806C9.82746 10.2657 9.8762 11.659 12.0207 11.8109C14.7014 12.0009 15.7753 12.3174 15.6387 13.958C15.502 15.5985 14.0077 15.953 12.8268 15.9149C11.6458 15.877 9.71365 15.3344 9.63867 13.8752M12.6394 7V8.07177M12.6394 15.9051V16.9999"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('Currencies')}}</h6>
                                                    <small class="text-muted d-block">{{__('View and update currency settings')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div>
                                    <a href="#" id="openUserSignupTab" class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 22L10 16H2L4 22H12ZM12 22H16" stroke="#C52127" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M12 13V12.5C12 10.6144 12 9.67157 11.4142 9.08579C10.8284 8.5 9.88562 8.5 8 8.5C6.11438 8.5 5.17157 8.5 4.58579 9.08579C4 9.67157 4 10.6144 4 12.5V13" stroke="#C52127" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M19 13C19 14.1046 18.1046 15 17 15C15.8954 15 15 14.1046 15 13C15 11.8954 15.8954 11 17 11C18.1046 11 19 11.8954 19 13Z" stroke="#C52127" stroke-width="1.5"/>
                                                    <path d="M10 4C10 5.10457 9.10457 6 8 6C6.89543 6 6 5.10457 6 4C6 2.89543 6.89543 2 8 2C9.10457 2 10 2.89543 10 4Z" stroke="#C52127" stroke-width="1.5"/>
                                                    <path d="M14 17.5H20C21.1046 17.5 22 18.3954 22 19.5V20C22 21.1046 21.1046 22 20 22H19" stroke="#C52127" stroke-width="1.5" stroke-linecap="round"/>
                                                    </svg>

                                                </div>
                                                <div>
                                                    <h6 class="">{{__('User Sign Up')}}</h6>
                                                    <small class="text-muted d-block">{{__('View and update user sign up settings')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="settings-box-container">
                                <div>
                                    <a href="{{ route('admin.settings.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class=" setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M15.5 12C15.5 13.933 13.933 15.5 12 15.5C10.067 15.5 8.5 13.933 8.5 12C8.5 10.067 10.067 8.5 12 8.5C13.933 8.5 15.5 10.067 15.5 12Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M21.011 14.0949C21.5329 13.9542 21.7939 13.8838 21.8969 13.7492C22 13.6147 22 13.3982 22 12.9653V11.0316C22 10.5987 22 10.3822 21.8969 10.2477C21.7938 10.1131 21.5329 10.0427 21.011 9.90194C19.0606 9.37595 17.8399 7.33687 18.3433 5.39923C18.4817 4.86635 18.5509 4.59992 18.4848 4.44365C18.4187 4.28738 18.2291 4.1797 17.8497 3.96432L16.125 2.98509C15.7528 2.77375 15.5667 2.66808 15.3997 2.69058C15.2326 2.71308 15.0442 2.90109 14.6672 3.27709C13.208 4.73284 10.7936 4.73278 9.33434 3.277C8.95743 2.90099 8.76898 2.71299 8.60193 2.69048C8.43489 2.66798 8.24877 2.77365 7.87653 2.98499L6.15184 3.96423C5.77253 4.17959 5.58287 4.28727 5.51678 4.44351C5.45068 4.59976 5.51987 4.86623 5.65825 5.39916C6.16137 7.33686 4.93972 9.37599 2.98902 9.90196C2.46712 10.0427 2.20617 10.1131 2.10308 10.2476C2 10.3822 2 10.5987 2 11.0316V12.9653C2 13.3982 2 13.6147 2.10308 13.7492C2.20615 13.8838 2.46711 13.9542 2.98902 14.0949C4.9394 14.6209 6.16008 16.66 5.65672 18.5976C5.51829 19.1305 5.44907 19.3969 5.51516 19.5532C5.58126 19.7095 5.77092 19.8172 6.15025 20.0325L7.87495 21.0118C8.24721 21.2231 8.43334 21.3288 8.6004 21.3063C8.76746 21.2838 8.95588 21.0957 9.33271 20.7197C10.7927 19.2628 13.2088 19.2627 14.6689 20.7196C15.0457 21.0957 15.2341 21.2837 15.4012 21.3062C15.5682 21.3287 15.7544 21.223 16.1266 21.0117L17.8513 20.0324C18.2307 19.8171 18.4204 19.7094 18.4864 19.5531C18.5525 19.3968 18.4833 19.1304 18.3448 18.5975C17.8412 16.66 19.0609 14.621 21.011 14.0949Z"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('General Settings')}}</h6>
                                                    <small class="text-muted d-block">{{__('Configure the fundamental information of the site.')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                         <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                            <div class="settings-box-container">
                                <div>
                                    <a href="{{ route('admin.system-settings.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class=" setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M15.5 12C15.5 13.933 13.933 15.5 12 15.5C10.067 15.5 8.5 13.933 8.5 12C8.5 10.067 10.067 8.5 12 8.5C13.933 8.5 15.5 10.067 15.5 12Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M21.011 14.0949C21.5329 13.9542 21.7939 13.8838 21.8969 13.7492C22 13.6147 22 13.3982 22 12.9653V11.0316C22 10.5987 22 10.3822 21.8969 10.2477C21.7938 10.1131 21.5329 10.0427 21.011 9.90194C19.0606 9.37595 17.8399 7.33687 18.3433 5.39923C18.4817 4.86635 18.5509 4.59992 18.4848 4.44365C18.4187 4.28738 18.2291 4.1797 17.8497 3.96432L16.125 2.98509C15.7528 2.77375 15.5667 2.66808 15.3997 2.69058C15.2326 2.71308 15.0442 2.90109 14.6672 3.27709C13.208 4.73284 10.7936 4.73278 9.33434 3.277C8.95743 2.90099 8.76898 2.71299 8.60193 2.69048C8.43489 2.66798 8.24877 2.77365 7.87653 2.98499L6.15184 3.96423C5.77253 4.17959 5.58287 4.28727 5.51678 4.44351C5.45068 4.59976 5.51987 4.86623 5.65825 5.39916C6.16137 7.33686 4.93972 9.37599 2.98902 9.90196C2.46712 10.0427 2.20617 10.1131 2.10308 10.2476C2 10.3822 2 10.5987 2 11.0316V12.9653C2 13.3982 2 13.6147 2.10308 13.7492C2.20615 13.8838 2.46711 13.9542 2.98902 14.0949C4.9394 14.6209 6.16008 16.66 5.65672 18.5976C5.51829 19.1305 5.44907 19.3969 5.51516 19.5532C5.58126 19.7095 5.77092 19.8172 6.15025 20.0325L7.87495 21.0118C8.24721 21.2231 8.43334 21.3288 8.6004 21.3063C8.76746 21.2838 8.95588 21.0957 9.33271 20.7197C10.7927 19.2628 13.2088 19.2627 14.6689 20.7196C15.0457 21.0957 15.2341 21.2837 15.4012 21.3062C15.5682 21.3287 15.7544 21.223 16.1266 21.0117L17.8513 20.0324C18.2307 19.8171 18.4204 19.7094 18.4864 19.5531C18.5525 19.3968 18.4833 19.1304 18.3448 18.5975C17.8412 16.66 19.0609 14.621 21.011 14.0949Z"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('System Settings')}}</h6>
                                                    <small class="text-muted d-block">{{__('Configure the fundamental information of the site.')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="currencies" role="tabpanel" aria-labelledby="currencies-tab">
                            <div>
                                <div class="settings-box-container">
                                     <a href="{{ route('admin.currencies.index') }}"
                                        class="text-decoration-none text-dark">
                                        <div class="setting-box">
                                            <div class="d-flex align-items-center jusitfy-content-center gap-3">
                                                <div class="settings-icon">
                                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M3.16602 12C3.16602 7.77027 3.16602 5.6554 4.36399 4.25276C4.5341 4.05358 4.7196 3.86808 4.91878 3.69797C6.32142 2.5 8.43629 2.5 12.666 2.5C16.8957 2.5 19.0106 2.5 20.4132 3.69797C20.6124 3.86808 20.7979 4.05358 20.968 4.25276C22.166 5.6554 22.166 7.77027 22.166 12C22.166 16.2297 22.166 18.3446 20.968 19.7472C20.7979 19.9464 20.6124 20.1319 20.4132 20.302C19.0106 21.5 16.8957 21.5 12.666 21.5C8.43629 21.5 6.32142 21.5 4.91878 20.302C4.7196 20.1319 4.5341 19.9464 4.36399 19.7472C3.16602 18.3446 3.16602 16.2297 3.16602 12Z"
                                                            stroke="#C52127" stroke-width="1.5" />
                                                        <path
                                                            d="M15.3762 10.063C15.2771 9.30039 14.4014 8.06817 12.8268 8.06814C10.9972 8.06811 10.2274 9.08141 10.0712 9.58806C9.82746 10.2657 9.8762 11.659 12.0207 11.8109C14.7014 12.0009 15.7753 12.3174 15.6387 13.958C15.502 15.5985 14.0077 15.953 12.8268 15.9149C11.6458 15.877 9.71365 15.3344 9.63867 13.8752M12.6394 7V8.07177M12.6394 15.9051V16.9999"
                                                            stroke="#C52127" stroke-width="1.5" stroke-linecap="round" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h6 class="">{{__('Currencies')}}</h6>
                                                    <small class="text-muted d-block">{{__('View and update currency settings')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="otp" role="tabpanel" aria-labelledby="otp-tab">
                            <div class="order-form-section p-16">
                                <h4 class="otp-title">{{__('User Registration Email Setup')}}</h4>
                                <form action="{{ route('admin.manage-settings.store') }}" method="post" class="ajaxform">
                                    @csrf
                                    <div class="row product-setting-form mt-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <input
                                                type="radio"
                                                id="otp_status_on"
                                                class="delete-checkbox-item multi-delete otp-status-on "
                                                name="otp_status"
                                                value="on"
                                                {{ ($otp->value['otp_status'] ?? '') === 'on' ? 'checked' : '' }}
                                            >
                                            <label for="otp_status_on" class="custom-top-label">
                                                {{ __('Verify email with OTP on signup?') }}
                                            </label>
                                        </div>

                                        <div class="form-group  otp-expiration-field">
                                            <label class="otp-input-label">{{ __('Valid Time') }}</label>
                                            <div class="otp-input-group">
                                                <input type="text" name="otp_expiration_time" placeholder="Ex: 30" value="{{ $otp->value['otp_expiration_time'] ?? '' }}" class="otp-input">

                                                <select name="otp_duration_type" class="otp-select">
                                                    <option value="second" {{ ($otp->value['otp_duration_type'] ?? '') === 'second' ? 'selected' : '' }}>
                                                        {{ __('Seconds') }}
                                                    </option>
                                                    <option value="minute" {{ ($otp->value['otp_duration_type'] ?? '') === 'minute' ? 'selected' : '' }}>
                                                        {{__('Minutes')}}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center mb-3 mt-3">
                                            <input
                                                type="radio"
                                                id="otp_status_off"
                                                class="delete-checkbox-item multi-delete otp-status-off"
                                                name="otp_status"
                                                value="off"
                                                {{ ($otp->value['otp_status'] ?? '') === 'off' ? 'checked' : '' }}
                                            >
                                            <label for="otp_status_off" class="custom-top-label">
                                                {{ __('Verify email without OTP on signup?') }}
                                            </label>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="text-center mt-5">
                                                <button type="submit" class="theme-btn m-2 submit-btn">{{ __('Update') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if (moduleCheck('CustomDomainAddon'))
                        <div class="tab-pane fade" id="domain" role="tabpanel" aria-labelledby="domain-tab">
                            <div class="order-form-section p-16">
                                <h4 class="otp-title">{{ __('Domain Setting') }}</h4>
                                <form action="{{ route('admin.domain.setting') }}" method="post" class="ajaxform">
                                    @csrf
                                    <div class="row product-setting-form mt-3">
                                        <div class="col-lg-12">
                                            <div class="d-flex align-items-center mb-3">
                                                <input type="radio" id="ssl_required" class="delete-checkbox-item multi-delete" name="ssl_required" value="on" {{ ($domain->value['ssl_required'] ?? '') === 'on' ? 'checked' : '' }}>
                                                <label for="ssl_required" class="custom-top-label">
                                                    {{ __('SSL is required.') }}
                                                </label>
                                            </div>

                                            <div class="d-flex align-items-center mb-3 mt-3">
                                                <input type="radio" id="ssl_nullable" class="delete-checkbox-item multi-delete" name="ssl_required" value="off" {{ ($domain->value['ssl_required'] ?? '') === 'off' ? 'checked' : '' }}>
                                                <label for="ssl_nullable" class="custom-top-label">
                                                    {{ __('SSL is not required.') }}
                                                </label>
                                            </div>

                                            <div class="d-flex align-items-center mb-3">
                                                <input type="radio" id="automatic_approve" class="delete-checkbox-item multi-delete" name="automatic_approve" value="on" {{ ($domain->value['automatic_approve'] ?? '') === 'on' ? 'checked' : '' }}>
                                                <label for="automatic_approve" class="custom-top-label">
                                                    {{ __('Subdomain / Custom domains are allowed automatically.') }}
                                                </label>
                                            </div>

                                            <div class="d-flex align-items-center mb-3 mt-3">
                                                <input type="radio" id="domain_not_allowed" class="delete-checkbox-item multi-delete" name="automatic_approve" value="off" {{ ($domain->value['automatic_approve'] ?? '') === 'off' ? 'checked' : '' }}>
                                                <label for="domain_not_allowed" class="custom-top-label">
                                                    {{ __('Subdomain / Custom domains are not allowed automatically.') }}
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 mt-5">
                                            <div class="text-center mt-5">
                                                <button type="submit" class="theme-btn m-2 submit-btn">{{ __('Update') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
