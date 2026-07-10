<div class="responsive-table m-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                @can('interfaces-delete')
                    <th>
                        <div class="d-flex align-items-center gap-1">
                            <label class="table-custom-checkbox">
                                <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                                <span class="table-custom-checkmark custom-checkmark"></span>
                            </label>
                            <i class="fal fa-trash-alt delete-selected"></i>
                        </div>
                    </th>
                @endcan
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Image') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($interfaces as $interface)
                <tr>
                    @can('interfaces-delete')
                        <td class="w-60 checkbox text-start">
                            <label class="table-custom-checkbox">
                                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                    value="{{ $interface->id }}" data-url="{{ route('admin.interfaces.delete-all') }}">
                                <span class="table-custom-checkmark custom-checkmark"></span>
                            </label>
                        </td>
                    @endcan
                    <td>{{ ($interfaces->currentPage() - 1) * $interfaces->perPage() + $loop->iteration }}</td>
                    <td>
                        <img class="table-img" src="{{ asset($interface->image) }}" alt="img">
                    </td>
                    <td class="text-center w-150">
                        @can('interfaces-update')
                            <label class="switch">
                                <input type="checkbox" @checked($interface->status) class="status"
                                    data-url="{{ route('admin.interfaces.status', $interface->id) }}">
                                <span class="slider round"></span>
                            </label>
                        @else
                            <div class="badge bg-{{ $interface->status == 0 ? 'success' : 'danger' }}">
                                {{ $interface->status == 0 ? 'Active' : 'Deactive' }}
                            </div>
                        @endcan
                    </td>
                    <td class="d-print-none">
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                @can('interfaces-update')
                                    <li>
                                        <a href="{{ route('admin.interfaces.edit', $interface->id) }}">
                                            <i class="fal fa-pencil-alt"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </li>
                                @endcan
                                @can('interfaces-delete')
                                    <li>
                                        <a href="{{ route('admin.interfaces.destroy', $interface->id) }}"
                                            class="confirm-action" data-method="DELETE">
                                            <i class="fal fa-trash-alt"></i>
                                            {{ __('Delete') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $interfaces->links('vendor.pagination.bootstrap-5') }}
</div>
