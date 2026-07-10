<div class="responsive-table m-0">
    <table class="table" id="erp-table">
        <thead>
            <tr>
                <th>
                    <div class="d-flex align-items-center gap-1">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i class="fal fa-trash-alt delete-selected"></i>
                    </div>
                </th>
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Rate') }}</th>
                <th>{{ __('Symbol') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Default') }}</th>
                <th class="d-print-none">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($currencies as $currency)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                value="{{ $currency->id }}" data-url="{{ route('admin.currencies.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i></i>
                    </td>

                    <td>{{ ($currencies->currentPage() - 1) * $currencies->perPage() + $loop->iteration }} <i class="{{ request('id') == $currency->id ? 'fas fa-bell text-red' : '' }}"></i>
                    </td>
                    <td>{{ $currency->name }}</td>
                    <td>{{ $currency->code }}</td>
                    <td>{{ $currency->rate }}</td>
                    <td>{{ $currency->symbol }}</td>
                    <td>
                        <div class="{{ $currency->status == 1 ? 'badge bg-success' : 'badge bg-danger' }}">
                            {{ $currency->status == 1 ? 'Active' : 'Inactive' }}
                        </div>
                    </td>
                    <td>
                        <div class="{{ $currency->is_default == 1 ? 'badge bg-success' : 'badge bg-danger' }}">
                            {{ $currency->is_default == 1 ? 'Yes' : 'No' }}
                        </div>
                    </td>
                    <td class="d-print-none">
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">

                                @if ($currency->is_default)
                                    @can('currencies-update')
                                        <li>
                                            <a href="{{ route('admin.currencies.edit', $currency->id) }}">
                                                <i class="fal fa-pencil-alt"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.currencies.default', ['id' => $currency->id]) }}">
                                                <i class="fas fa-adjust"></i>
                                                {{ __('Make Default') }}
                                            </a>
                                        </li>
                                    @endcan
                                @else
                                    @can('currencies-update')
                                        <li>
                                            <a href="{{ route('admin.currencies.edit', $currency->id) }}">
                                                <i class="fal fa-pencil-alt"></i>
                                                {{ __('Edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.currencies.default', ['id' => $currency->id]) }}">
                                                <i class="fas fa-adjust"></i>
                                                {{ __('Make Default') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('currencies-delete')
                                        <li>
                                            <a href="{{ route('admin.currencies.destroy', $currency->id) }}"
                                                class="confirm-action" data-method="DELETE">
                                                <i class="fal fa-trash-alt"></i>
                                                {{ __('Delete') }}
                                            </a>
                                        </li>
                                    @endcan
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div>
    {{ $currencies->links('vendor.pagination.bootstrap-5') }}
</div>
