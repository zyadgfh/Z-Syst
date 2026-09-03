@extends('layouts.admin')

@section('title', __('security.Security Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-shield-alt me-2"></i>{{ __('security.Security Dashboard') }}</h4>
            <small class="text-muted">{{ __('security.Audit logs, user activity, permission changes, and security metrics') }}</small>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3"><i class="fas fa-clipboard-list text-primary"></i></div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $auditStats['total_30d'] }}</div>
                            <small class="text-muted">{{ __('security.Audit Events (30d)') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3"><i class="fas fa-calendar-day text-success"></i></div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $auditStats['today'] }}</div>
                            <small class="text-muted">{{ __('security.Events Today') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3"><i class="fas fa-users text-info"></i></div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $auditStats['unique_users'] }}</div>
                            <small class="text-muted">{{ __('security.Active Users (30d)') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3"><i class="fas fa-user-shield text-warning"></i></div>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $userStats['total'] }}</div>
                            <small class="text-muted">{{ __('security.Total Users') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Activity Trend Chart --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-line me-1"></i>{{ __('security.Activity Trend (14 Days)') }}</h6></div>
                <div class="card-body">
                    <canvas id="activityChart" height="80"></canvas>
                </div>
            </div>

            {{-- Recent Audit Logs --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-list me-1"></i>{{ __('security.Recent Audit Logs') }}</h6>
                    <span class="badge badge-soft-secondary">{{ $auditStats['total_30d'] }} {{ __('products.events') }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('common.Date') }}</th>
                                <th>{{ __('common.Action') }}</th>
                                <th>{{ __('common.User') }}</th>
                                <th>{{ __('security.Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAuditLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d M H:i') }}</td>
                                    <td><span class="badge badge-soft-info">{{ $log->action }}</span></td>
                                    <td>{{ $log->user->name ?? '—' }}</td>
                                    <td class="text-muted" style="max-width:300px">{{ Str::limit($log->description ?? json_encode($log->meta ?? ''), 60) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ __('security.No audit logs in the last 30 days.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentAuditLogs->hasPages())
                    <div class="card-footer">{{ $recentAuditLogs->withQueryString()->links() }}</div>
                @endif
            </div>

            {{-- Permission Changes --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-user-tag me-1"></i>{{ __('security.Permission & User Changes') }}</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('common.Date') }}</th>
                                <th>{{ __('common.Action') }}</th>
                                <th>{{ __('security.Actor') }}</th>
                                <th>{{ __('security.Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permissionChanges as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td><span class="badge badge-soft-warning">{{ $log->action }}</span></td>
                                    <td>{{ $log->user->name ?? '—' }}</td>
                                    <td class="text-muted">{{ Str::limit($log->description ?? '', 80) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ __('security.No permission changes recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Action Distribution --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-1"></i>{{ __('security.Action Distribution (30d)') }}</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($actionDistribution as $action)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><span class="badge badge-soft-info me-2">{{ $action->action }}</span></span>
                                <span class="badge bg-primary rounded-pill">{{ $action->count }}</span>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-3">{{ __('common.No data.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Top Active Users --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-trophy me-1"></i>{{ __('security.Most Active Users (30d)') }}</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($topUsers as $u)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user-circle me-2 text-muted"></i>{{ $u->name }}</span>
                                <span class="badge bg-secondary rounded-pill">{{ $u->activity_count }}</span>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-3">{{ __('common.No data.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- User Accounts --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-users-cog me-1"></i>{{ __('security.User Accounts') }}</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('security.Total Users') }}</span>
                        <strong>{{ $userStats['total'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('security.Verified Accounts') }}</span>
                        <strong>{{ $userStats['active'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('security.Active This Week') }}</span>
                        <strong>{{ $userStats['recent_logins'] }}</strong>
                    </div>
                </div>
            </div>

            {{-- Product Security --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-pills me-1"></i>{{ __('security.Item Security') }}</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('security.Active Items') }}</span>
                        <strong>{{ $productSecurity['active_items'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('products.Rx Required') }}</span>
                        <strong class="text-warning">{{ $productSecurity['prescription_items'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('security.Controlled Items') }}</span>
                        <strong class="text-danger">{{ $productSecurity['controlled_items'] }}</strong>
                    </div>
                </div>
            </div>

            {{-- Unusual Activity --}}
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10"><h6 class="mb-0 text-warning"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('security.Sensitive Actions (7d)') }}</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($unusualActivity as $log)
                            <div class="list-group-item py-2">
                                <div class="d-flex justify-content-between">
                                    <small><span class="badge badge-soft-danger me-1">{{ $log->action }}</span> {{ $log->user->name ?? '—' }}</small>
                                    <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-3">{{ __('security.No sensitive actions recorded.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const dailyData = @json($dailyActivity);
    const ctx = document.getElementById('activityChart');
    if (ctx && dailyData.length > 0) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [{
                    label: '{{ __('security.Audit Events') }}',
                    data: dailyData.map(d => d.count),
                    backgroundColor: 'rgba(78, 115, 223, 0.6)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
</script>
@endpush
@endsection
