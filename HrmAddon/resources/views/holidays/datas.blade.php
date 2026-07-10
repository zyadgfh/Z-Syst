@foreach($holidays as $holiday)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $holiday->id }}">
        </td>
        <td>{{ ($holidays->currentPage() - 1) * $holidays->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $holiday->name }}</td>
        <td class="text-start">{{ formatted_date($holiday->start_date) }}</td>
        <td class="text-start">{{ formatted_date($holiday->end_date) }}</td>
        <td class="text-start">{{ Str::limit($holiday->description, 20, '...') }}</td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#holidays-edit-modal" data-bs-toggle="modal" class="holidays-edit-btn"
                        data-url="{{ route('hrm.holidays.update', $holiday->id) }}"
                        data-holidays-name="{{ $holiday->name }}"
                        data-holidays-start-date="{{ $holiday->start_date }}"
                        data-holidays-end-date="{{ $holiday->end_date }}"
                        data-holidays-description="{{ $holiday->description }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.holidays.destroy', $holiday->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
