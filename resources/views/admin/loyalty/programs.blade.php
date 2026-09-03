@extends('layouts.master')

@section('title')
    {{ __('loyalty.Loyalty Programs') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('loyalty.Loyalty Programs') }}</h4>
                        @can('loyalty-create')
                            <a href="{{ route('admin.loyalty.create') }}" class="add-order-btn rounded-2 active">
                                <i class="fas fa-plus-circle me-1"></i> {{ __('loyalty.Add Program') }}
                            </a>
                        @endcan
                    </div>

                    <div class="responsive-table table-container mt-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="table-header-content">{{ __('common.SL') }}.</th>
                                    <th class="table-header-content">{{ __('common.Name') }}</th>
                                    <th class="table-header-content">{{ __('loyalty.Points Rate') }}</th>
                                    <th class="table-header-content">{{ __('loyalty.Min Redemption') }}</th>
                                    <th class="table-header-content">{{ __('common.Status') }}</th>
                                    <th class="table-header-content d-print-none">{{ __('common.Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($programs as $program)
                                    <tr class="table-content">
                                        <td class="table-single-content">{{ $loop->iteration }}</td>
                                        <td class="table-single-content">{{ $program->name }}</td>
                                        <td class="table-single-content">{{ $program->points_per_currency }} {{ __('loyalty.points per') }} {{ $program->currency ?? '1' }} {{ __('loyalty.unit') }}</td>
                                        <td class="table-single-content">{{ $program->min_points_for_redemption }}</td>
                                        <td class="table-single-content">
                                            @if ($program->is_active)
                                                <span class="badge bg-success">{{ __('common.Active') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('common.Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="table-single-content d-print-none">
                                            <div class="d-flex gap-2">
                                                @can('loyalty-update')
                                                    <a href="{{ route('admin.loyalty.edit', $program) }}" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('loyalty-delete')
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProgram({{ $program->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">{{ __('loyalty.No loyalty programs found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $programs->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        function deleteProgram(id) {
            if (confirm('{{ __('loyalty.Are you sure you want to delete this loyalty program?') }}')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/loyalty-programs/${id}`;
                form.innerHTML = '@csrf @method("DELETE")';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endpush
