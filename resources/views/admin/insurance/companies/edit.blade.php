@extends('layouts.admin')

@section('title', __('insurance.Edit Insurance Company'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Edit Insurance Company') }}</h1>
        <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="companyForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="255" value="{{ $company->name }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Contact Person') }}</label>
                        <input type="text" name="contact_person" class="form-control" maxlength="255" value="{{ $company->contact_person }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Phone') }}</label>
                        <input type="text" name="phone" class="form-control" maxlength="50" value="{{ $company->phone }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Email') }}</label>
                        <input type="email" name="email" class="form-control" value="{{ $company->email }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Address') }}</label>
                        <textarea name="address" class="form-control" rows="2">{{ $company->address }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.City') }}</label>
                        <input type="text" name="city" class="form-control" maxlength="100" value="{{ $company->city }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Country') }}</label>
                        <input type="text" name="country" class="form-control" maxlength="100" value="{{ $company->country }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Tax ID') }}</label>
                        <input type="text" name="tax_id" class="form-control" maxlength="100" value="{{ $company->tax_id }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $company->status === 'active' ? 'selected' : '' }}>{{ __('common.Active') }}</option>
                            <option value="inactive" {{ $company->status === 'inactive' ? 'selected' : '' }}>{{ __('common.Inactive') }}</option>
                            <option value="suspended" {{ $company->status === 'suspended' ? 'selected' : '' }}>{{ __('common.Suspended') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Integration Type') }} <span class="text-danger">*</span></label>
                        <select name="integration_type" class="form-select" required>
                            <option value="manual" {{ $company->integration_type === 'manual' ? 'selected' : '' }}>{{ __('common.Manual') }}</option>
                            <option value="api" {{ $company->integration_type === 'api' ? 'selected' : '' }}>{{ __('common.API') }}</option>
                            <option value="hybrid" {{ $company->integration_type === 'hybrid' ? 'selected' : '' }}>{{ __('common.Hybrid') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.API Endpoint') }}</label>
                        <input type="url" name="api_endpoint" class="form-control" value="{{ $company->api_endpoint }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Default Coverage %') }} <span class="text-danger">*</span></label>
                        <input type="number" name="default_coverage_percent" class="form-control" required min="0" max="100" step="0.01" value="{{ $company->default_coverage_percent }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Default Copay %') }} <span class="text-danger">*</span></label>
                        <input type="number" name="default_copay_percent" class="form-control" required min="0" max="100" step="0.01" value="{{ $company->default_copay_percent }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Settlement Days') }} <span class="text-danger">*</span></label>
                        <input type="number" name="settlement_days" class="form-control" required min="1" value="{{ $company->settlement_days }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $company->notes }}</textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Update') }}</button>
                    <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('companyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('_method', 'PUT');
    try {
        const response = await fetch('{{ route("admin.insurance.companies.update", $company) }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || '{{ __("common.Error") }}');
        }
    } catch (err) {
        alert('{{ __("common.Error") }}: ' + err.message);
    }
});
</script>
@endsection
