@extends('layouts.master')
@section('title') {{ __('الجرد بالمسح') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <h5 class="mb-0">📦 {{ __('جلسات الجرد') }}</h5>
            <form method="POST" action="{{ route('admin.stocktake.store') }}" class="ms-auto">
                @csrf
                <button class="btn btn-success">{{ __('بدء جلسة جرد جديدة') }}</button>
            </form>
        </div>
        <table class="table table-sm">
            <tr><th>#</th><th>{{ __('النوع') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('التاريخ') }}</th><th></th></tr>
            @forelse($audits as $a)
            <tr>
                <td>{{ $a->audit_number }}</td>
                <td>{{ $a->audit_type }}</td>
                <td><span class="badge {{ $a->status === 'completed' ? 'bg-success' : 'bg-warning' }}">{{ $a->status }}</span></td>
                <td>{{ $a->audit_date?->format('m/d H:i') }}</td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.stocktake.show', $a) }}">{{ __('فتح') }}</a></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted">{{ __('لا جلسات — ابدأ جلسة ثم امسح الأصناف بماسح الجهاز') }}</td></tr>
            @endforelse
        </table>
        {{ $audits->links() }}
    </div></div>
</div>
@endsection
