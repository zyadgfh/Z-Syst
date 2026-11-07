@extends('layouts.master')

@section('title')
    {{ __('Audit Logs') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">{ __('Audit Logs') }</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr>
                            <th>{ __('ID') }</th><th>{ __('Action') }</th><th>{ __('Model') }</th>
                            <th>{ __('Description') }</th><th>{ __('IP') }</th><th>{ __('Date') }</th><th></th>
                        </tr></thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                    <td>{{ $log->model_type ? class_basename($log->model_type) : '—' }} #{{ $log->model_id }}</td>
                                    <td>{{ Str::limit($log->description, 80) }}</td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td><a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-light">{ __('View') }</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
