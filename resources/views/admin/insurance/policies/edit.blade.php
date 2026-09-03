@extends('layouts.admin')

@section('title', __('insurance.Edit Insurance Policy'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Edit Insurance Policy') }}</h1>
        <a href="{{ route('admin.insurance.policies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="policyForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Insurance Company') }} <span class="text-danger">*</span></label>
                        <select name="insurance_company_id" class="form-select" required>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $policy->insurance_company_id === $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Holder Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="holder_name" class="form-control" required maxlength="255" value="{{ $policy->holder_name }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Member ID') }}</label>
                        <input type="text" name="member_id" class="form-control" maxlength="255" value="{{ $policy->member_id }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Card Number') }}</label>
                        <input type="text" name="card_number" class="form-control" maxlength="255" value="{{ $policy->card_number }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Date of Birth') }}</label>
                        <input type="date" name="holder_dob" class="form-control" value="{{ $policy->holder_dob?->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Gender') }}</label>
                        <select name="holder_gender" class="form-select">
                            <option value="">{{ __('common.Select') }}...</option>
                            <option value="male" {{ $policy->holder_gender === 'male' ? 'selected' : '' }}>{{ __('common.Male') }}</option>
                            <option value="female" {{ $policy->holder_gender === 'female' ? 'selected' : '' }}>{{ __('common.Female') }}</option>
                            <option value="other" {{ $policy->holder_gender === 'other' ? 'selected' : '' }}>{{ __('common.Other') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Phone') }}</label>
                        <input type="text" name="holder_phone" class="form-control" maxlength="50" value="{{ $policy->holder_phone }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Email') }}</label>
                        <input type="email" name="holder_email" class="form-control" value="{{ $policy->holder_email }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Plan Type') }} <span class="text-danger">*</span></label>
                        <select name="plan_type" class="form-select" required>
                            <option value="individual" {{ $policy->plan_type === 'individual' ? 'selected' : '' }}>{{ __('common.Individual') }}</option>
                            <option value="family" {{ $policy->plan_type === 'family' ? 'selected' : '' }}>{{ __('common.Family') }}</option>
                            <option value="corporate" {{ $policy->plan_type === 'corporate' ? 'selected' : '' }}>{{ __('common.Corporate') }}</option>
                            <option value="government" {{ $policy->plan_type === 'government' ? 'selected' : '' }}>{{ __('common.Government') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $policy->status === 'active' ? 'selected' : '' }}>{{ __('common.Active') }}</option>
                            <option value="pending" {{ $policy->status === 'pending' ? 'selected' : '' }}>{{ __('common.Pending') }}</option>
                            <option value="expired" {{ $policy->status === 'expired' ? 'selected' : '' }}>{{ __('common.Expired') }}</option>
                            <option value="suspended" {{ $policy->status === 'suspended' ? 'selected' : '' }}>{{ __('common.Suspended') }}</option>
                            <option value="cancelled" {{ $policy->status === 'cancelled' ? 'selected' : '' }}>{{ __('common.Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Start Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required value="{{ $policy->start_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.End Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required value="{{ $policy->end_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Annual Limit') }}</label>
                        <input type="number" name="annual_limit" class="form-control" min="0" step="0.01" value="{{ $policy->annual_limit }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Coverage %') }}</label>
                        <input type="number" name="coverage_percent" class="form-control" min="0" max="100" step="0.01" value="{{ $policy->coverage_percent }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('common.Copay %') }}</label>
                        <input type="number" name="copay_percent" class="form-control" min="0" max="100" step="0.01" value="{{ $policy->copay_percent }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $policy->notes }}</textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Update') }}</button>
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
    formData.append('_method', 'PUT');
    try {
        const response = await fetch('{{ route("admin.insurance.policies.update", $policy) }}', {
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
