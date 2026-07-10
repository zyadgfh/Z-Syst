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
                <th>{{ __('Phone') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Company Name') }}</th>
                <th>{{ __('Message') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($messages as $message)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                value="{{ $message->id }}" data-url="{{ route('admin.messages.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                    </td>
                    <td>{{ ($messages->currentPage() - 1) * $messages->perPage() + $loop->iteration }}</td>
                    <td>{{ $message->name }}</td>
                    <td>{{ $message->phone }}</td>
                    <td>{{ $message->email }}</td>
                    <td>{{ $message->company_name }}</td>
                    <td>{{ $message->message }}</td>
                    <td>
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                @can('messages-delete')
                                    <li>
                                        <a href="{{ route('admin.messages.destroy', $message->id) }}"
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
    {{ $messages->links('vendor.pagination.bootstrap-5') }}
</div>
