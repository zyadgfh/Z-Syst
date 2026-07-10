@extends('layouts.web.master')

@section('title')
    {{ __('Data Deletion Instructions') }}
@endsection

@section('main_content')
<section class="banner-bg p-4">
    <div class="container">
        <p class="mb-0 fw-bolder custom-clr-dark">
            {{ __('Home') }} <span class="font-monospace">></span> {{ __('Data Deletion Instructions') }}
        </p>
    </div>
</section>

<section class="terms-policy-section">
    <div class="container">
        <h2 class="mb-2">{{__('Data Deletion Instructions')}}</h2>
        <div>
            <div>
                <p>{{__('If you want to delete your data from our system, please follow these steps:')}}</p>
            </div>
            <div class="mt-3">
                <p>{{__('Send an email to [your-email@example.com] with the subject line “Delete My Data”.')}}</p>
            </div>
            <div class="mt-3">
                <p>{{__('Include your name and the email address you used to register.')}}</p>
            </div>
            <div class="mt-3">
                <p>{{__('We will process your request and confirm deletion within 7 business days.')}}</p>
            </div>
        </div>
    </div>
</section>
@endsection
