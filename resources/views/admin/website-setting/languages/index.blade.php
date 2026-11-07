@extends('layouts.master')

@section('title')
    {{ __('Languages') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Languages') }}</h4>
                <a href="{{ route('admin.website-setting.languages.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add language') }}</a>
            </div>
            <div id="languages-table">
                @include('admin.website-setting.languages.datas')
            </div>
        </div></div>
    </div>
</div>

@endsection
