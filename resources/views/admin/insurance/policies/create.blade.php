@extends('layouts.admin')

@section('title', __('insurance.Create Insurance Policy'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Create Insurance Policy') }}</h1>
        <a href="{{ route('admin.insurance.policies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="policyForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Insurance Company') }} <span class="text-danger">*</span></label>
                        <select name="insurance_company_id" class="form-select" required>
                            <option value="">{{ __('common.Select') }}...</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Business ID') }} <span class="text-danger">*</span></label>
                        <input type="number" name="business_id" class="form-control" required value="{{ auth()->user()->business_id ?? 1 }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Holder Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="holder_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Member ID') }}</label>
                        <input type="text" name="member_id" class="form-control" maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Card Number') }}</label>
                        <input type="text" name="card_number" class="form-control" maxlength="255">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Date of Birth') }}</label>
                        <input type="date" name="holder_dob" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Gender') }}</label>
                        <select name="holder_gender" class="form-select">
                            <option value="">{{ __('common.Select') }}...</option>
                            <option value="male">{{ __('common.Male') }}</option>
                            <option value="female">{{ __('common.Female') }}</option>
                            <option value="other">{{ __('common.Other') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Phone') }}</label>
                        <input type="text" name="holder_phone" class="form-control" maxlength="50">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Email') }}</label>
                        <input type="email" name="holder_email" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Plan Type') }} <span class="text-danger">*</span></label>
                        <select name="plan_type" class="form-select" required>
                            <option value="individual">{{ __('common.Individual') }}</option>
                            <option value="family">{{ __('common.Family') }}</option>
                            <option value="corporate">{{ __('common.Corporate') }}</option>
                            <option value="government">{{ __('common.Government') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">{{ __('common.Active') }}</option>
                            <option value="pending">{{ __('common.Pending') }}</option>
                            <option value="expired">{{ __('common.Expired') }}</option>
                            <option value="cancelled">{{ __('common.Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Start Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Annual Limit') }}</label>
                        <input type="number" name="annual_limit" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Coverage %') }}</label>
                        <input type="number" name="coverage_percent" class="form-control" min="0" max="100" step="0.01" value="80">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Copay %') }}</label>
                        <input type="number" name="copay_percent" class="form-control" min="0" max="100" step="0.01" value="20">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Address') }}</label>
                        <textarea name="holder_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Save') }}</button>
                    <a href="{{ route('admin.insurance.policies.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('policyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('{{ route("admin.insurance.policies.store") }}', {
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
