@foreach ($messages as $message)
    <tr>

        <td class="w-60 checkbox table-single-content d-print-none">
            <label class="table-custom-checkbox">
                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item delete-checkbox-item multi-delete"
                    value="{{ $message->id }}">
                <span class="table-custom-checkmark custom-checkmark"></span>
            </label>
        </td>

        <td>{{ ($messages->perPage() * ($messages->currentPage() - 1)) + $loop->iteration }}</td>
        <td>{{ $message->name }}</td>
        <td>{{ $message->phone }}</td>
        <td>{{ $message->email }}</td>
        <td>{{ $message->company_name }}</td>
        <td>{{ Str::limit($message->message, 40, '...') }}</td>

        <td class="d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="{{ route('admin.messages.destroy', $message->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                                {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
