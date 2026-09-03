@extends('layouts.admin')

@section('title', __('insurance.Edit Insurance Claim'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Edit Insurance Claim') }}: {{ $claim->claim_number }}</h1>
        <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="claimForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Insurance Company') }}</label>
                        <input type="text" class="form-control" value="{{ $claim->company->name ?? '—' }}" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Policy') }}</label>
                        <input type="text" class="form-control" value="{{ $claim->policy->policy_number ?? '—' }}" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Service Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="service_date" class="form-control" required value="{{ $claim->service_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Total Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="total_amount" class="form-control" required min="0" step="0.01" value="{{ $claim->total_amount }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Covered Amount') }}</label>
                        <input type="number" name="covered_amount" class="form-control" min="0" step="0.01" value="{{ $claim->covered_amount }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Patient Responsibility') }}</label>
                        <input type="number" name="patient_responsibility" class="form-control" min="0" step="0.01" value="{{ $claim->patient_responsibility }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $claim->notes }}</textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Update') }}</button>
                    <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('claimForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('_method', 'PUT');
    try {
        const response = await fetch('{{ route("admin.insurance.claims.update", $claim) }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.redirect) { window.location.href = data.redirect; }
        else { alert(data.message || '{{ __("common.Error") }}'); }
    } catch (err) {
        alert('{{ __("common.Error") }}: ' + err.message);
    }
});
</script>
@endsection
