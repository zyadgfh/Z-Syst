@extends('layouts.master')

@section('title', __('audit.Audit Logs'))

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-header p-16">
                    <h4>{{ __('audit.Audit Logs') }}</h4>
                </div>

                <div class="table-header justify-content-center border-0 text-center d-none d-block d-print-block">
                    <h4 class="mt-2">{{ __('audit.Audit Logs') }}</h4>
                </div>
            </div>

            <div class="responsive-table table-container">
                <table class="table" id="datatable">
                    <thead>
                        <tr>
                            <th class="table-header-content">{{ __('common.SL') }}.</th>
                            <th class="table-header-content">{{ __('common.Date') }}</th>
                            <th class="table-header-content">{{ __('common.User') }}</th>
                            <th class="table-header-content">{{ __('common.Action') }}</th>
                            <th class="table-header-content">{{ __('audit.Model') }}</th>
                            <th class="table-header-content">{{ __('common.Description') }}</th>
                            <th class="table-header-content">{{ __('audit.IP Address') }}</th>
                            <th class="table-header-content d-print-none">{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->user->name ?? __('common.System') }}</td>
                            <td>
                                <span class="badge badge-{{ $log->action === 'delete' ? 'danger' : ($log->action === 'create' ? 'success' : 'info') }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td>{{ class_basename($log->model_type ?? '') }}</td>
                            <td>{{ Str::limit($log->description ?? '', 50) }}</td>
                            <td>{{ $log->ip_address ?? 'N/A' }}</td>
                            <td class="d-print-none">
                                <div class="dropdown table-action">
                                    <button type="button" data-bs-toggle="dropdown">
                                        <i class="far fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('admin.audit-logs.show', $log) }}">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1.66675 10.0003C1.66675 10.0003 4.16675 4.16699 10.0001 4.16699C15.8334 4.16699 18.3334 10.0003 18.3334 10.0003C18.3334 10.0003 15.8334 15.8337 10.0001 15.8337C4.16675 15.8337 1.66675 10.0003 1.66675 10.0003Z" stroke="#4A4A52" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="10" cy="10" r="2.5" stroke="#4A4A52" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                {{ __('common.View') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">{{ __('audit.No audit logs found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $logs->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
