@extends('layouts.master')

@section('title')
    {{ __('Warehouse') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ $warehouse->name }} <small class="text-muted">({{ $warehouse->code }})</small></h4>
            @if($warehouse->is_default)<span class="badge bg-primary">{{ __('Default') }}</span>@endif
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><strong>{{ __('Location') }}:</strong> {{ $warehouse->location }}</div>
            <div class="col-md-4"><strong>{{ __('Active') }}:</strong> {{ $warehouse->is_active ? __('Yes') : __('No') }}</div>
        </div>
        @if(isset($statistics) && is_array($statistics))
            <h5>{{ __('Statistics') }}</h5>
            <div class="row g-3">
                @foreach($statistics as $key => $value)
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="fs-5 fw-bold">{{ is_scalar($value) ? $value : json_encode($value) }}</div>
                            <div class="text-muted small">{{ $key }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="mt-4">
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
