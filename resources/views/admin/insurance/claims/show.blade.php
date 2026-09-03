@extends('layouts.admin')

@section('title', __('insurance.Claim') . ' - ' . $claim->claim_number)

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Claim') }}: {{ $claim->claim_number }}</h1>
        <div class="d-flex gap-2">
            @if($claim->status === 'draft')
            <button class="btn btn-success" onclick="submitClaim()">
                <i class="fas fa-paper-plane me-1"></i> {{ __('insurance.Submit Claim') }}
            </button>
            @endif
            @if(in_array($claim->status, ['submitted', 'pending_review']))
            <button class="btn btn-primary-blue" onclick="showProcessModal()">
                <i class="fas fa-check me-1"></i> {{ __('insurance.Process Claim') }}
            </button>
            @endif
            @if(in_array($claim->status, ['approved', 'partially_approved']))
            <button class="btn btn-success" onclick="showPaymentModal()">
                <i class="fas fa-money-bill me-1"></i> {{ __('insurance.Process Payment') }}
            </button>
            @endif
            <a href="{{ route('admin.insurance.claims.edit', $claim) }}" class="btn btn-primary-blue">
                <i class="fas fa-edit me-1"></i> {{ __('common.Edit') }}
            </a>
            <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Claim Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Claim Number') }}:</strong> <code>{{ $claim->claim_number }}</code>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Status') }}:</strong>
                            <span class="badge bg-{{ match($claim->status) {
                                'approved' => 'success', 'partially_approved' => 'info',
                                'submitted', 'pending_review' => 'warning',
                                'rejected' => 'danger', 'paid' => 'primary',
                                default => 'secondary'
                            } }}">{{ ucfirst(str_replace('_', ' ', $claim->status)) }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Insurance Company') }}:</strong> {{ $claim->company->name ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Policy') }}:</strong>
                            @if($claim->policy)
                            <a href="{{ route('admin.insurance.policies.show', $claim->policy) }}">{{ $claim->policy->policy_number }}</a>
                            @else — @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Service Date') }}:</strong> {{ $claim->service_date?->format('d M Y') ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Submission Date') }}:</strong> {{ $claim->submission_date?->format('d M Y') ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.External Reference') }}:</strong> {{ $claim->external_reference ?? '—' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('common.Settlement Date') }}:</strong> {{ $claim->settlement_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('insurance.Financial Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Total Amount') }}</span>
                        <strong>{{ number_format($claim->total_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Covered Amount') }}</span>
                        <strong class="text-success">{{ number_format($claim->covered_amount ?? 0, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Patient Responsibility') }}</span>
                        <strong class="text-warning">{{ number_format($claim->patient_responsibility ?? 0, 2) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Approved Amount') }}</span>
                        <strong>{{ number_format($claim->approved_amount ?? 0, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Paid Amount') }}</span>
                        <strong class="text-primary">{{ number_format($claim->paid_amount ?? 0, 2) }}</strong>
                    </div>
                    @if($claim->rejected_amount > 0)
                    <div class="d-flex justify-content-between mb-3">
                        <span>{{ __('common.Rejected Amount') }}</span>
                        <strong class="text-danger">{{ number_format($claim->rejected_amount, 2) }}</strong>
                    </div>
                    @endif
                    @if($claim->rejection_reason)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>{{ __('common.Reason') }}:</strong> {{ $claim->rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($claim->notes)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('common.Notes') }}</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ $claim->notes }}</p>
        </div>
    </div>
    @endif
</div>

{{-- Process Claim Modal --}}
<div class="modal fade" id="processModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('insurance.Process Claim') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="processForm">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.Decision') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="approved">{{ __('common.Approved') }}</option>
                            <option value="partially_approved">{{ __('insurance.Partially Approved') }}</option>
                            <option value="rejected">{{ __('common.Rejected') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.Approved Amount') }}</label>
                        <input type="number" name="approved_amount" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.Rejection Reason') }}</label>
                        <textarea name="rejection_reason" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.External Reference') }}</label>
                        <input type="text" name="external_reference" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                <button type="button" class="btn btn-primary-blue" onclick="processClaim()">{{ __('common.Submit') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('insurance.Process Payment') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('common.Payment Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" id="paymentAmount" class="form-control" min="0" step="0.01" value="{{ $claim->approved_amount ?? $claim->covered_amount ?? 0 }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="processPayment()">{{ __('insurance.Process Payment') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
function showProcessModal() { new bootstrap.Modal(document.getElementById('processModal')).show(); }
function showPaymentModal() { new bootstrap.Modal(document.getElementById('paymentModal')).show(); }

async function submitClaim() {
    if (!confirm('{{ __("insurance.Are you sure you want to submit this claim?") }}')) return;
    try {
        const response = await fetch('{{ route("admin.insurance.claims.submit", $claim) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (response.ok && data.redirect) window.location.href = data.redirect;
        else alert(data.message || '{{ __("common.Error") }}');
    } catch (e) { alert('{{ __("common.Error") }}: ' + e.message); }
}

async function processClaim() {
    const form = document.getElementById('processForm');
    const formData = new FormData(form);
    try {
        const response = await fetch('{{ route("admin.insurance.claims.process", $claim) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.redirect) window.location.href = data.redirect;
        else alert(data.message || '{{ __("common.Error") }}');
    } catch (e) { alert('{{ __("common.Error") }}: ' + e.message); }
}

async function processPayment() {
    const amount = document.getElementById('paymentAmount').value;
    try {
        const response = await fetch('{{ route("admin.insurance.claims.payment", $claim) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify({ amount })
        });
        const data = await response.json();
        if (response.ok && data.redirect) window.location.href = data.redirect;
        else alert(data.message || '{{ __("common.Error") }}');
    } catch (e) { alert('{{ __("common.Error") }}: ' + e.message); }
}
</script>
@endsection
