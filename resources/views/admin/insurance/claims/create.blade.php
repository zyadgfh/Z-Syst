@extends('layouts.admin')

@section('title', __('insurance.Create Insurance Claim'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Create Insurance Claim') }}</h1>
        <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="claimForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Business ID') }} <span class="text-danger">*</span></label>
                        <input type="number" name="business_id" class="form-control" required value="{{ auth()->user()->business_id ?? 1 }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Insurance Company') }} <span class="text-danger">*</span></label>
                        <select name="insurance_company_id" id="companyId" class="form-select" required>
                            <option value="">{{ __('common.Select') }}...</option>
                            @foreach(\App\Models\InsuranceCompany::all() as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Insurance Policy') }} <span class="text-danger">*</span></label>
                        <select name="insurance_policy_id" id="policyId" class="form-select" required>
                            <option value="">{{ __('common.Select company first') }}...</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Customer') }}</label>
                        <input type="text" name="customer_id" class="form-control" placeholder="{{ __('common.Customer ID') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Service Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="service_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Total Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="total_amount" id="totalAmount" class="form-control" required min="0" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Covered Amount') }}</label>
                        <input type="number" name="covered_amount" id="coveredAmount" class="form-control" min="0" step="0.01" readonly>
                        <small class="text-muted">{{ __('insurance.Auto-calculated from policy') }}</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('common.Patient Responsibility') }}</label>
                        <input type="number" name="patient_responsibility" id="patientResponsibility" class="form-control" min="0" step="0.01" readonly>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">{{ __('common.Save') }}</button>
                    <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('companyId').addEventListener('change', async function() {
    const policySelect = document.getElementById('policyId');
    policySelect.innerHTML = '<option value="">{{ __("common.Loading") }}...</option>';
    if (!this.value) { policySelect.innerHTML = '<option value="">{{ __("common.Select company first") }}...</option>'; return; }
    try {
        const response = await fetch('/admin/insurance/policies?company_id=' + this.value, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();
        let options = '<option value="">{{ __("common.Select") }}...</option>';
        (data.data || data || []).forEach(p => { options += `<option value="${p.id}">${p.policy_number} - ${p.holder_name}</option>`; });
        policySelect.innerHTML = options;
    } catch (e) {
        policySelect.innerHTML = '<option value="">{{ __("common.Error loading policies") }}</option>';
    }
});

document.getElementById('totalAmount').addEventListener('input', async function() {
    const companyId = document.getElementById('companyId').value;
    const policyId = document.getElementById('policyId').value;
    const amount = parseFloat(this.value) || 0;
    if (!policyId || !amount) return;
    try {
        const response = await fetch(`/admin/insurance/policies/${policyId}/check-eligibility`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ amount })
        });
        const data = await response.json();
        document.getElementById('coveredAmount').value = data.covered_amount || (amount * 0.8).toFixed(2);
        document.getElementById('patientResponsibility').value = data.patient_responsibility || (amount * 0.2).toFixed(2);
    } catch (e) {}
});

document.getElementById('claimForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('{{ route("admin.insurance.claims.store") }}', {
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
