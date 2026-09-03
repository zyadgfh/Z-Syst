@extends('layouts.admin')

@section('title', __('insurance.Claim Statistics'))

@section('main_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('insurance.Claim Statistics') }}</h1>
        <div class="d-flex gap-2">
            <input type="date" id="dateFrom" class="form-control form-control-sm" placeholder="{{ __('common.From') }}">
            <input type="date" id="dateTo" class="form-control form-control-sm" placeholder="{{ __('common.To') }}">
            <button class="btn btn-primary-blue btn-sm" onclick="loadStats()"><i class="fas fa-filter me-1"></i> {{ __('common.Filter') }}</button>
            <a href="{{ route('admin.insurance.claims.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-gradient-blue">
                <div class="card-body text-center">
                    <div class="stat-xl-blue" id="totalClaims">0</div>
                    <div class="text-muted small">{{ __('insurance.Total Claims') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-gradient-green">
                <div class="card-body text-center">
                    <div class="stat-xl-green" id="approvedClaims">0</div>
                    <div class="text-muted small">{{ __('common.Approved') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-gradient-amber">
                <div class="card-body text-center">
                    <div class="stat-xl-amber" id="pendingClaims">0</div>
                    <div class="text-muted small">{{ __('common.Pending') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-gradient-red">
                <div class="card-body text-center">
                    <div class="stat-xl-red" id="rejectedClaims">0</div>
                    <div class="text-muted small">{{ __('common.Rejected') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-xl-blue" id="totalAmount">0.00</div>
                    <div class="text-muted small">{{ __('insurance.Total Amount') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-xl-green" id="approvedAmount">0.00</div>
                    <div class="text-muted small">{{ __('insurance.Approved Amount') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-xl-green" id="paidAmount">0.00</div>
                    <div class="text-muted small">{{ __('insurance.Paid Amount') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-xl-amber" id="avgProcessingDays">0</div>
                    <div class="text-muted small">{{ __('insurance.Avg Processing Days') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approval Rate --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('insurance.Approval Rate') }}</h5>
        </div>
        <div class="card-body">
            <div class="progress h-24">
                <div id="approvalBar" class="progress-bar bg-success w-0" role="progressbar">0%</div>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted">{{ __('insurance.Approved') }}: <span id="approvalCount">0</span></small>
                <small class="text-muted">{{ __('insurance.Rejected') }}: <span id="rejectionCount">0</span></small>
            </div>
        </div>
    </div>
</div>

<script>
async function loadStats() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    let url = '{{ route("admin.insurance.claims.statistics") }}?';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    try {
        const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();
        document.getElementById('totalClaims').textContent = data.total_claims || 0;
        document.getElementById('approvedClaims').textContent = data.approved_claims || 0;
        document.getElementById('pendingClaims').textContent = data.pending_claims || 0;
        document.getElementById('rejectedClaims').textContent = data.rejected_claims || 0;
        document.getElementById('totalAmount').textContent = parseFloat(data.total_amount || 0).toFixed(2);
        document.getElementById('approvedAmount').textContent = parseFloat(data.approved_amount || 0).toFixed(2);
        document.getElementById('paidAmount').textContent = parseFloat(data.paid_amount || 0).toFixed(2);
        document.getElementById('avgProcessingDays').textContent = parseFloat(data.avg_processing_days || 0).toFixed(1);
        const total = (data.approved_claims || 0) + (data.rejected_claims || 0);
        const rate = total > 0 ? Math.round(((data.approved_claims || 0) / total) * 100) : 0;
        document.getElementById('approvalBar').style.width = rate + '%';
        document.getElementById('approvalBar').textContent = rate + '%';
        document.getElementById('approvalCount').textContent = data.approved_claims || 0;
        document.getElementById('rejectionCount').textContent = data.rejected_claims || 0;
    } catch (e) { console.error('Stats error:', e); }
}
loadStats();
</script>
@endsection
