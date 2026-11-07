#!/usr/bin/env python3
"""generate_admin_views.py — create the 35 missing admin Blade views (doc 24).

Each view extends layouts.master (which loads the apple.css layer), uses the
exact variables its controller passes (verified against the controllers), and
links only to route names that exist in routes/admin.php.
"""
import os, textwrap

V = 'resources/views/admin'

def w(path, content):
    full = os.path.join(V, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, 'w') as f:
        f.write(content.lstrip('\n'))
    print('+', path)

TITLE = "@section('title')\n    {{ __('%s') }}\n@endsection\n"

# ─────────────────────────── shared partial snippets ───────────────────
def page(title, body):
    return f"""@extends('layouts.master')

{TITLE % title}
@section('main_content')
{body}
@endsection
"""

def table(title, headers, rows_blade, add_route=None, add_label=None):
    add_btn = ''
    if add_route:
        add_btn = f"""
                    <a href=\"{{{{ route('{add_route}') }}}}\" class=\"btn btn-primary\"><i class=\"fas fa-plus-circle me-1\"></i>{{ __('{add_label}') }}</a>"""
    head = ''.join(f'<th>{{ __("_{h}_") if False else __("'+h+'") }}</th>' for h in headers)
    return f"""
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('{title}') }}</h4>{add_btn}
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr>{head}</tr></thead>
                        <tbody>
{rows_blade}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
""".replace('{title}', title)

def empty_row(cols, collection):
    return f"""                            @empty({collection})
                                <tr><td colspan="{cols}" class="text-center text-muted py-4">{{ __('No data found.') }}</td></tr>
                            @endempty
"""

def form(title, action_route, fields, model=None, method=None):
    """fields: list of (name, label, type, value_expr)"""
    method_dir = ''
    if method:
        method_dir = f"    @method('{method}')\n"
    inputs = ''
    for name, label, ftype, val in fields:
        if ftype == 'select':
            opts, val_key, disp = val
            inputs += f"""
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('{label}') }}</label>
                    <select name="{name}" class="form-select" required>
                        <option value="">--</option>
                        @foreach({opts} as $item)
                            <option value="{{{{ $item->id }}}}" @selected(${model} && ${model}->{name} == $item->id)>{{{{ {disp} }}}}</option>
                        @endforeach
                    </select>
                </div>"""
        elif ftype == 'textarea':
            inputs += f"""
                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('{label}') }}</label>
                    <textarea name="{name}" rows="3" class="form-control">{{{{ old('{name}', ${model} ? ${model}->{val} : '') }}}}</textarea>
                </div>"""
        else:
            inputs += f"""
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('{label}') }}</label>
                    <input type="{ftype}" name="{name}" value="{{{{ old('{name}', ${model} ? ${model}->{val} : '') }}}}" class="form-control">
                </div>"""
    submit = f"{{ __('{ 'Save changes' if method else 'Create' }') }}"
    return page(title, f"""
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{{ __('{title}') }}</h4>
            <form action="{{{{ route('{action_route}'{', $' + model if model else ''}) }}}}" method="POST" class="row">
    @csrf
{method_dir}    {inputs}
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{submit}</button>
                    <a href="javascript:history.back()" class="btn btn-light">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
""".replace('{title}', title))

# ─────────────────────────── audit logs ───────────────────────────
w('audit-logs/index.blade.php', page('Audit Logs', f"""
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">{{ __('Audit Logs') }}</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr>
                            <th>{{ __('ID') }}</th><th>{{ __('Action') }}</th><th>{{ __('Model') }}</th>
                            <th>{{ __('Description') }}</th><th>{{ __('IP') }}</th><th>{{ __('Date') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{{{ $log->id }}}}</td>
                                    <td><span class="badge bg-secondary">{{{{ $log->action }}}}</span></td>
                                    <td>{{{{ $log->model_type ? class_basename($log->model_type) : '—' }}}} #{{{{ $log->model_id }}}}</td>
                                    <td>{{{{ Str::limit($log->description, 80) }}}}</td>
                                    <td>{{{{ $log->ip_address }}}}</td>
                                    <td>{{{{ $log->created_at?->format('Y-m-d H:i') }}}}</td>
                                    <td><a href="{{{{ route('admin.audit-logs.show', $log) }}}}" class="btn btn-sm btn-light">{{ __('View') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
"""))

w('audit-logs/show.blade.php', page('Audit Log', """
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">{{ __('Audit Log') }} #{{ $auditLog->id }}</h4>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light">{{ __('Back') }}</a>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><strong>{{ __('Action') }}:</strong> {{ $auditLog->action }}</div>
                <div class="col-md-6"><strong>{{ __('Model') }}:</strong> {{ $auditLog->model_type ? class_basename($auditLog->model_type) : '—' }} #{{ $auditLog->model_id }}</div>
                <div class="col-md-6"><strong>{{ __('Actor') }}:</strong> {{ $auditLog->user?->email ?? '—' }}</div>
                <div class="col-md-6"><strong>{{ __('Business') }}:</strong> {{ $auditLog->business?->companyName ?? '—' }}</div>
                <div class="col-md-6"><strong>{{ __('IP') }}:</strong> {{ $auditLog->ip_address }}</div>
                <div class="col-md-6"><strong>{{ __('Date') }}:</strong> {{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</div>
                <div class="col-12"><strong>{{ __('Description') }}:</strong><p>{{ $auditLog->description }}</p></div>
                @if($auditLog->old_values)
                    <div class="col-md-6"><strong>{{ __('Old values') }}:</strong><pre class="bg-light p-3 rounded small">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></div>
                @endif
                @if($auditLog->new_values)
                    <div class="col-md-6"><strong>{{ __('New values') }}:</strong><pre class="bg-light p-3 rounded small">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></div>
                @endif
            </div>
        </div>
    </div>
</div>
"""))

# ─────────────────────────── GRN ───────────────────────────
w('grn/create.blade.php', form('New Goods Received Note', 'admin.grn.store', [
    ('supplier_id', 'Supplier', 'select', ('suppliers', 'id', '$item->company_name ?? $item->name')),
    ('purchase_order_id', 'Purchase Order', 'select', ('purchaseOrders', 'id', '"PO #".$item->id')),
    ('received_date', 'Received date', 'date', 'received_date'),
    ('location', 'Location', 'text', 'location'),
    ('notes', 'Notes', 'textarea', 'notes'),
], model=None))

w('grn/show.blade.php', page('Goods Received Note', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('GRN') }}: {{ $grn->grn_number }}</h4>
            <span class="badge bg-info">{{ $grn->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Supplier') }}:</strong> {{ $grn->supplier?->company_name ?? $grn->supplier?->name ?? $grn->supplier_id }}</div>
            <div class="col-md-4"><strong>{{ __('Purchase order') }}:</strong> #{{ $grn->purchase_order_id }}</div>
            <div class="col-md-4"><strong>{{ __('Received at') }}:</strong> {{ $grn->received_date }}</div>
            <div class="col-md-4"><strong>{{ __('Location') }}:</strong> {{ $grn->location }}</div>
            <div class="col-md-4"><strong>{{ __('Verified at') }}:</strong> {{ $grn->verified_at ?? '—' }}</div>
            <div class="col-12"><strong>{{ __('Notes') }}:</strong><p>{{ $grn->notes }}</p></div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($grn->status === 'pending')
                <form action="{{ route('admin.grn.verify', $grn) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Verify') }}</button></form>
                <form action="{{ route('admin.grn.accept', $grn) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Accept') }}</button></form>
                <form action="{{ route('admin.grn.reject', $grn) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Reject') }}</button></form>
            @endif
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

w('grn/edit.blade.php', form('Edit Goods Received Note', 'admin.grn.update', [
    ('received_date', 'Received date', 'date', 'received_date'),
    ('location', 'Location', 'text', 'location'),
    ('notes', 'Notes', 'textarea', 'notes'),
], model='grn', method='PUT'))

# ─────────────────────────── insurance companies ───────────────────────────
CO_FIELDS = [
    ('name', 'Name', 'text'), ('code', 'Code', 'text'), ('contact_person', 'Contact person', 'text'),
    ('phone', 'Phone', 'text'), ('email', 'Email', 'email'), ('city', 'City', 'text'),
    ('country', 'Country', 'text'), ('status', 'Status', 'text'),
    ('default_coverage_percent', 'Coverage %', 'number'), ('default_copay_percent', 'Copay %', 'number'),
    ('settlement_days', 'Settlement days', 'number'),
]

w('insurance/companies/index.blade.php', page('Insurance Companies', f"""
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Insurance Companies') }}</h4>
                <a href="{{{{ route('admin.companies.create') }}}}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add company') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Name') }}</th><th>{{ __('Code') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Coverage') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td>{{{{ $company->name }}}}</td>
                                <td>{{{{ $company->code }}}}</td>
                                <td>{{{{ $company->contact_person }}}}</td>
                                <td>{{{{ $company->phone }}}}</td>
                                <td>{{{{ $company->default_coverage_percent }}}}%</td>
                                <td><span class="badge bg-secondary">{{{{ $company->status }}}}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{{{ route('admin.companies.show', $company) }}}}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{{{ route('admin.companies.edit', $company) }}}}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('insurance/companies/create.blade.php', form('Add Insurance Company', 'admin.companies.store',
    [(n, l, t, n) for n, l, t in CO_FIELDS], model=None))

w('insurance/companies/show.blade.php', page('Insurance Company', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ $company->name }} <small class="text-muted">({{ $company->code }})</small></h4>
            <span class="badge bg-secondary">{{ $company->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Contact') }}:</strong> {{ $company->contact_person }}</div>
            <div class="col-md-4"><strong>{{ __('Phone') }}:</strong> {{ $company->phone }}</div>
            <div class="col-md-4"><strong>{{ __('Email') }}:</strong> {{ $company->email }}</div>
            <div class="col-md-4"><strong>{{ __('City') }}:</strong> {{ $company->city }}</div>
            <div class="col-md-4"><strong>{{ __('Coverage') }}:</strong> {{ $company->default_coverage_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Copay') }}:</strong> {{ $company->default_copay_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Settlement days') }}:</strong> {{ $company->settlement_days }}</div>
            <div class="col-12"><strong>{{ __('Address') }}:</strong> {{ $company->address }}</div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-primary">{{ __('Edit') }}</a>
            <a href="{{ route('admin.companies.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

w('insurance/companies/edit.blade.php', form('Edit Insurance Company', 'admin.companies.update',
    [(n, l, t, n) for n, l, t in CO_FIELDS], model='company', method='PUT'))

# ─────────────────────────── insurance policies ───────────────────────────
POL_FIELDS = [
    ('policy_number', 'Policy number', 'text'), ('member_id', 'Member ID', 'text'),
    ('card_number', 'Card number', 'text'), ('holder_name', 'Holder name', 'text'),
    ('holder_dob', 'Holder DOB', 'date'), ('holder_phone', 'Holder phone', 'text'),
    ('plan_type', 'Plan type', 'text'), ('status', 'Status', 'text'),
    ('start_date', 'Start date', 'date'), ('end_date', 'End date', 'date'),
    ('annual_limit', 'Annual limit', 'number'), ('coverage_percent', 'Coverage %', 'number'),
    ('copay_percent', 'Copay %', 'number'),
]

w('insurance/policies/index.blade.php', page('Insurance Policies', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Insurance Policies') }}</h4>
                <a href="{{ route('admin.policies.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add policy') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Policy #') }}</th><th>{{ __('Holder') }}</th><th>{{ __('Company') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Limit') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($policies as $policy)
                            <tr>
                                <td>{{ $policy->policy_number }}</td>
                                <td>{{ $policy->holder_name }}</td>
                                <td>{{ $policy->company?->name ?? $policy->insurance_company_id }}</td>
                                <td>{{ $policy->plan_type }}</td>
                                <td>{{ $policy->remaining_limit }} / {{ $policy->annual_limit }}</td>
                                <td><span class="badge bg-secondary">{{ $policy->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.policies.show', $policy) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.policies.edit', $policy) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('insurance/policies/create.blade.php', form('Add Insurance Policy', 'admin.policies.store',
    [('insurance_company_id', 'Company', 'select', ('companies', 'id', '$item->name'))]
    + [(n, l, t, n) for n, l, t in POL_FIELDS], model=None))

w('insurance/policies/show.blade.php', page('Insurance Policy', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Policy') }}: {{ $policy->policy_number }}</h4>
            <span class="badge bg-secondary">{{ $policy->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Holder') }}:</strong> {{ $policy->holder_name }}</div>
            <div class="col-md-4"><strong>{{ __('Company') }}:</strong> {{ $policy->company?->name ?? $policy->insurance_company_id }}</div>
            <div class="col-md-4"><strong>{{ __('Plan') }}:</strong> {{ $policy->plan_type }}</div>
            <div class="col-md-4"><strong>{{ __('Annual limit') }}:</strong> {{ $policy->annual_limit }}</div>
            <div class="col-md-4"><strong>{{ __('Used') }}:</strong> {{ $policy->used_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Remaining') }}:</strong> {{ $policy->remaining_limit }}</div>
            <div class="col-md-4"><strong>{{ __('Coverage') }}:</strong> {{ $policy->coverage_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Valid') }}:</strong> {{ $policy->start_date }} → {{ $policy->end_date }}</div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('admin.policies.edit', $policy) }}" class="btn btn-primary">{{ __('Edit') }}</a>
            <a href="{{ route('admin.policies.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

w('insurance/policies/edit.blade.php', form('Edit Insurance Policy', 'admin.policies.update',
    [('insurance_company_id', 'Company', 'select', ('companies', 'id', '$item->name'))]
    + [(n, l, t, n) for n, l, t in POL_FIELDS], model='policy', method='PUT'))

# ─────────────────────────── insurance claims ───────────────────────────
CL_FIELDS = [
    ('claim_number', 'Claim number', 'text'), ('service_date', 'Service date', 'date'),
    ('submission_date', 'Submission date', 'date'), ('total_amount', 'Total amount', 'number'),
    ('covered_amount', 'Covered amount', 'number'), ('patient_responsibility', 'Patient responsibility', 'number'),
    ('approved_amount', 'Approved amount', 'number'), ('paid_amount', 'Paid amount', 'number'),
    ('rejected_amount', 'Rejected amount', 'number'), ('status', 'Status', 'text'),
    ('rejection_reason', 'Rejection reason', 'text'), ('settlement_date', 'Settlement date', 'date'),
]

w('insurance/claims/index.blade.php', page('Insurance Claims', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Insurance Claims') }}</h4>
                <a href="{{ route('admin.claims.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add claim') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Claim #') }}</th><th>{{ __('Service date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Covered') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($claims as $claim)
                            <tr>
                                <td>{{ $claim->claim_number }}</td>
                                <td>{{ $claim->service_date }}</td>
                                <td>{{ $claim->total_amount }}</td>
                                <td>{{ $claim->covered_amount }}</td>
                                <td><span class="badge bg-secondary">{{ $claim->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.claims.show', $claim) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.claims.edit', $claim) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('insurance/claims/create.blade.php', form('Add Insurance Claim', 'admin.claims.store',
    [(n, l, t, n) for n, l, t in CL_FIELDS], model=None))

w('insurance/claims/show.blade.php', page('Insurance Claim', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Claim') }}: {{ $claim->claim_number }}</h4>
            <span class="badge bg-secondary">{{ $claim->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Service date') }}:</strong> {{ $claim->service_date }}</div>
            <div class="col-md-4"><strong>{{ __('Total') }}:</strong> {{ $claim->total_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Covered') }}:</strong> {{ $claim->covered_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Approved') }}:</strong> {{ $claim->approved_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Paid') }}:</strong> {{ $claim->paid_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Patient responsibility') }}:</strong> {{ $claim->patient_responsibility }}</div>
            @if($claim->rejection_reason)<div class="col-12"><strong>{{ __('Rejection reason') }}:</strong> {{ $claim->rejection_reason }}</div>@endif
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            @if($claim->status === 'draft')
                <form action="{{ route('admin.claims.submit', $claim) }}" method="POST">@csrf<button class="btn btn-primary">{{ __('Submit') }}</button></form>
            @endif
            @if($claim->status === 'submitted')
                <form action="{{ route('admin.claims.process', $claim) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Process') }}</button></form>
            @endif
            <a href="{{ route('admin.claims.edit', $claim) }}" class="btn btn-outline-secondary">{{ __('Edit') }}</a>
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

w('insurance/claims/edit.blade.php', form('Edit Insurance Claim', 'admin.claims.update',
    [(n, l, t, n) for n, l, t in CL_FIELDS], model='claim', method='PUT'))

# ─────────────────────────── dashboard report ───────────────────────────
w('reports/dashboard.blade.php', page('Dashboard Reports', """
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.overall') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Overall report') }}</h5>
                    <p class="card-text text-muted">{{ __('Sales, purchases, stock and financial summary.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.warehouse') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Warehouse report') }}</h5>
                    <p class="card-text text-muted">{{ __('Stock distribution across warehouses.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.transfers') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Transfers report') }}</h5>
                    <p class="card-text text-muted">{{ __('Inter-warehouse transfer activity.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.sales-by-date') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Sales by date') }}</h5>
                    <p class="card-text text-muted">{{ __('Daily sales trend.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.subscription-reports.index') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Subscription reports') }}</h5>
                    <p class="card-text text-muted">{{ __('Business subscriptions overview.') }}</p>
                </div></div>
            </a>
        </div>
    </div>
</div>
"""))

# ─────────────────────────── stock transfers ───────────────────────────
w('stock-transfers/index.blade.php', page('Stock Transfers', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Stock Transfers') }}</h4>
                <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New transfer') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('ID') }}</th><th>{{ __('Product') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td>#{{ $transfer->id }}</td>
                                <td>{{ $transfer->product?->productName ?? $transfer->product_id }}</td>
                                <td>{{ $transfer->quantity }}</td>
                                <td><span class="badge bg-secondary">{{ $transfer->status }}</span></td>
                                <td><a href="{{ route('admin.stock-transfers.show', $transfer) }}" class="btn btn-sm btn-light">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('stock-transfers/create.blade.php', form('New Stock Transfer', 'admin.stock-transfers.store', [
    ('from_warehouse_id', 'From warehouse', 'select', ('warehouses', 'id', '$item->name')),
    ('to_warehouse_id', 'To warehouse', 'select', ('warehouses', 'id', '$item->name')),
    ('quantity', 'Quantity', 'number', 'quantity'),
    ('notes', 'Notes', 'textarea', 'notes'),
], model=None))

w('stock-transfers/show.blade.php', page('Stock Transfer', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Transfer') }} #{{ $transfer->id }}</h4>
            <span class="badge bg-secondary">{{ $transfer->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Product') }}:</strong> {{ $transfer->product?->productName ?? $transfer->product_id }}</div>
            <div class="col-md-4"><strong>{{ __('Quantity') }}:</strong> {{ $transfer->quantity }}</div>
            <div class="col-md-4"><strong>{{ __('Notes') }}:</strong> {{ $transfer->notes }}</div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($transfer->status === 'pending')
                <form action="{{ route('admin.stock-transfers.complete', $transfer) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Complete') }}</button></form>
                <form action="{{ route('admin.stock-transfers.cancel', $transfer) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Cancel') }}</button></form>
            @endif
            <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

# ─────────────────────────── subscriptions ───────────────────────────
w('subscriptions/index.blade.php', page('Subscriptions', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Subscriptions') }}</h4>
                <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New subscription') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Business') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Status') }}</th><th>{{ __('Starts') }}</th><th>{{ __('Ends') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->business?->companyName ?? $subscription->business_id }}</td>
                                <td>{{ $subscription->plan?->name ?? $subscription->plan_id }}</td>
                                <td><span class="badge bg-secondary">{{ $subscription->status }}</span></td>
                                <td>{{ $subscription->starts_at?->format('Y-m-d') }}</td>
                                <td>{{ $subscription->ends_at?->format('Y-m-d') }}</td>
                                <td><a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-sm btn-light">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('subscriptions/create.blade.php', form('New Subscription', 'admin.subscriptions.store', [
    ('business_id', 'Business', 'select', ('businesses', 'id', '$item->companyName ?? $item->name')),
    ('plan_id', 'Plan', 'select', ('plans', 'id', '$item->name ?? $item->id')),
    ('payment_method', 'Payment method', 'text', 'payment_method'),
], model=None))

w('subscriptions/show.blade.php', page('Subscription', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Subscription') }} #{{ $subscription->id }}</h4>
            <span class="badge bg-secondary">{{ $subscription->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Business') }}:</strong> {{ $subscription->business?->companyName ?? $subscription->business_id }}</div>
            <div class="col-md-4"><strong>{{ __('Plan') }}:</strong> {{ $subscription->plan?->name ?? $subscription->plan_id }}</div>
            <div class="col-md-4"><strong>{{ __('Payment method') }}:</strong> {{ $subscription->payment_method }}</div>
            <div class="col-md-4"><strong>{{ __('Starts') }}:</strong> {{ $subscription->starts_at?->format('Y-m-d') }}</div>
            <div class="col-md-4"><strong>{{ __('Ends') }}:</strong> {{ $subscription->ends_at?->format('Y-m-d') }}</div>
            <div class="col-md-4"><strong>{{ __('Trial ends') }}:</strong> {{ $subscription->trial_ends_at?->format('Y-m-d') }}</div>
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Renew') }}</button></form>
            <form action="{{ route('admin.subscriptions.generate-invoice', $subscription) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Generate invoice') }}</button></form>
            <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Cancel') }}</button></form>
            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

# ─────────────────────────── supplier payments ───────────────────────────
SP_FIELDS = [
    ('payment_date', 'Payment date', 'date'), ('payment_method', 'Payment method', 'text'),
    ('reference', 'Reference', 'text'), ('bank_reference', 'Bank reference', 'text'),
    ('amount', 'Amount', 'number'), ('status', 'Status', 'text'), ('notes', 'Notes', 'textarea'),
]

w('supplier-payments/index.blade.php', page('Supplier Payments', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Supplier Payments') }}</h4>
                <a href="{{ route('admin.supplier-payments.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New payment') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Number') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->supplier?->company_name ?? $payment->supplier_id }}</td>
                                <td>{{ $payment->amount }}</td>
                                <td>{{ $payment->payment_date }}</td>
                                <td><span class="badge bg-secondary">{{ $payment->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.supplier-payments.show', $payment) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.supplier-payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
"""))

w('supplier-payments/create.blade.php', form('New Supplier Payment', 'admin.supplier-payments.store',
    [('supplier_id', 'Supplier', 'select', ('suppliers', 'id', '$item->company_name ?? $item->name'))]
    + [(n, l, t, n) for n, l, t in SP_FIELDS], model=None))

w('supplier-payments/show.blade.php', page('Supplier Payment', """
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Payment') }}: {{ $payment->payment_number }}</h4>
            <span class="badge bg-secondary">{{ $payment->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Supplier') }}:</strong> {{ $payment->supplier?->company_name ?? $payment->supplier_id }}</div>
            <div class="col-md-4"><strong>{{ __('Amount') }}:</strong> {{ $payment->amount }}</div>
            <div class="col-md-4"><strong>{{ __('Date') }}:</strong> {{ $payment->payment_date }}</div>
            <div class="col-md-4"><strong>{{ __('Method') }}:</strong> {{ $payment->payment_method }}</div>
            <div class="col-md-4"><strong>{{ __('Approved at') }}:</strong> {{ $payment->approved_at ?? '—' }}</div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($payment->status === 'pending')
                <form action="{{ route('admin.supplier-payments.approve', $payment) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Approve') }}</button></form>
            @endif
            <a href="{{ route('admin.supplier-payments.edit', $payment) }}" class="btn btn-outline-secondary">{{ __('Edit') }}</a>
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>
"""))

w('supplier-payments/edit.blade.php', form('Edit Supplier Payment', 'admin.supplier-payments.update',
    [('supplier_id', 'Supplier', 'select', ('suppliers', 'id', '$item->company_name ?? $item->name'))]
    + [(n, l, t, n) for n, l, t in SP_FIELDS], model='payment', method='PUT'))

# ─────────────────────────── tenant payment settings ───────────────────────────
TPS_FIELDS = [
    ('merchant_phone', 'Merchant phone', 'text'), ('merchant_name', 'Merchant name', 'text'),
    ('merchant_code', 'Merchant code', 'text'), ('merchant_key', 'Merchant key', 'text'),
    ('merchant_instapay_id', 'InstaPay ID', 'text'), ('bank_name', 'Bank name', 'text'),
    ('account_number', 'Account number', 'text'), ('branch_name', 'Branch name', 'text'),
]

w('tenant-payment-settings/create.blade.php', form('New Tenant Payment Setting', 'admin.tenant-payment-settings.store', [
    ('tenant_id', 'Tenant (business)', 'select', ('tenants', 'id', '$item->companyName ?? $item->name')),
    ('gateway_id', 'Gateway', 'select', ('gateways', 'id', '$item->title ?? $item->name')),
] + [(n, l, t, n) for n, l, t in TPS_FIELDS], model=None))

w('tenant-payment-settings/edit.blade.php', form('Edit Tenant Payment Setting', 'admin.tenant-payment-settings.update', [
    ('tenant_id', 'Tenant (business)', 'select', ('tenants', 'id', '$item->companyName ?? $item->name')),
    ('gateway_id', 'Gateway', 'select', ('gateways', 'id', '$item->title ?? $item->name')),
] + [(n, l, t, n) for n, l, t in TPS_FIELDS], model='setting', method='PUT'))

# ─────────────────────────── warehouses show ───────────────────────────
w('warehouses/show.blade.php', page('Warehouse', """
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
"""))

# ─────────────────────────── languages ───────────────────────────
w('website-setting/languages/index.blade.php', page('Languages', """
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Languages') }}</h4>
                <a href="{{ route('admin.website-setting.languages.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('Add language') }}</a>
            </div>
            <div id="languages-table">
                @include('admin.website-setting.languages.datas')
            </div>
        </div></div>
    </div>
</div>
"""))

w('website-setting/languages/datas.blade.php', """
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>{{ __('Name') }}</th><th>{{ __('Icon') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>
            @foreach($languages ?? [] as $language)
                <tr>
                    <td>{{ $language->name }}</td>
                    <td>{{ $language->icon }}</td>
                    <td><span class="badge {{ $language->status ? 'bg-success' : 'bg-secondary' }}">{{ $language->status ? __('Active') : __('Inactive') }}</span></td>
                    <td><a href="{{ route('admin.website-setting.languages.edit', $language) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
""")

w('website-setting/languages/create.blade.php', form('Add Language', 'admin.website-setting.languages.store', [
    ('name', 'Name', 'text', 'name'),
    ('icon', 'Icon', 'text', 'icon'),
], model=None))

w('website-setting/languages/edit.blade.php', form('Edit Language', 'admin.website-setting.languages.update', [
    ('name', 'Name', 'text', 'name'),
    ('icon', 'Icon', 'text', 'icon'),
], model='language', method='PUT'))

print('\nDone — 35 views generated.')
