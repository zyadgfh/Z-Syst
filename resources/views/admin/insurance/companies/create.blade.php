@extends('layouts.admin')

@section('title', __('insurance.Create Insurance Company'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Create Insurance Company') }}</h1>
        <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="companyForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Contact Person') }}</label>
                        <input type="text" name="contact_person" class="form-control" maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Phone') }}</label>
                        <input type="text" name="phone" class="form-control" maxlength="50">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Email') }}</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Address') }}</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.City') }}</label>
                        <input type="text" name="city" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Country') }}</label>
                        <input type="text" name="country" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Tax ID') }}</label>
                        <input type="text" name="tax_id" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">{{ __('common.Active') }}</option>
                            <option value="inactive">{{ __('common.Inactive') }}</option>
                            <option value="suspended">{{ __('common.Suspended') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Integration Type') }} <span class="text-danger">*</span></label>
                        <select name="integration_type" class="form-select" required>
                            <option value="manual">{{ __('common.Manual') }}</option>
                            <option value="api">{{ __('common.API') }}</option>
                            <option value="hybrid">{{ __('common.Hybrid') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.API Endpoint') }}</label>
                        <input type="url" name="api_endpoint" class="form-control" placeholder="https://">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Default Coverage %') }} <span class="text-danger">*</span></label>
                        <input type="number" name="default_coverage_percent" class="form-control" required min="0" max="100" step="0.01" value="80">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Default Copay %') }} <span class="text-danger">*</span></label>
                        <input type="number" name="default_copay_percent" class="form-control" required min="0" max="100" step="0.01" value="20">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Settlement Days') }} <span class="text-danger">*</span></label>
                        <input type="number" name="settlement_days" class="form-control" required min="1" value="30">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Save') }}</button>
                    <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('companyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    try {
        const response = await fetch('{{ route("admin.insurance.companies.store") }}', {
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
