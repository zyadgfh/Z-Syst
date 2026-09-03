@extends('layouts.admin')

@section('title')
    {{ __('loyalty.Edit Loyalty Program') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('loyalty.Edit Loyalty Program') }}</h4>
                        <a href="{{ route('admin.loyalty.programs') }}" class="add-order-btn rounded-2 active">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
                        </a>
                    </div>

                    <div class="p-4">
                        <form action="{{ route('admin.loyalty.update', $program) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">{{ __('loyalty.Program Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $program->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="points_per_currency" class="form-label">{{ __('loyalty.Points per Currency Unit') }} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="points_per_currency" name="points_per_currency" value="{{ $program->points_per_currency }}" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="min_points_for_redemption" class="form-label">{{ __('loyalty.Min Points for Redemption') }} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="min_points_for_redemption" name="min_points_for_redemption" value="{{ $program->min_points_for_redemption }}" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="expiry_days" class="form-label">{{ __('loyalty.Point Expiry (Days)') }}</label>
                                    <input type="number" class="form-control" id="expiry_days" name="expiry_days" value="{{ $program->expiry_days }}" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="is_active" class="form-label">{{ __('common.Status') }}</label>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1" {{ $program->is_active ? 'selected' : '' }}>{{ __('common.Active') }}</option>
                                        <option value="0" {{ !$program->is_active ? 'selected' : '' }}>{{ __('common.Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ $program->is_default ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">{{ __('loyalty.Set as Default Program') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('loyalty.Update Program') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
