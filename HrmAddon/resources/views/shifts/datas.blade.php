@foreach($shifts as $shift)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $shift->id }}">
        </td>
        <td>{{ ($shifts->currentPage() - 1) * $shifts->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $shift->name }}</td>
        <td class="text-start">{{ formatted_time($shift->start_time) }}</td>
        <td class="text-start">{{ formatted_time($shift->end_time) }}</td>
        <td class="text-start">{{ formatted_time($shift->start_break_time ?? '') }} - {{ formatted_time($shift->end_break_time ?? '') }} </td>
        <td class="text-start">{{ formatTimeToWords($shift->break_time) }}</td>
        <td>
            <label class="switch">
                <input type="checkbox" {{ $shift->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('hrm.shifts.status', $shift->id) }}">
                <span class="slider round"></span>
            </label>
        </td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#shifts-edit-modal" data-bs-toggle="modal" class="shifts-edit-btn"
                        data-url="{{ route('hrm.shifts.update', $shift->id) }}"
                        data-shifts-name="{{ $shift->name }}"
                        data-shifts-break-status="{{ $shift->break_status }}"
                        data-shifts-start="{{ $shift->start_time }}" data-shifts-end="{{ $shift->end_time }}" data-start-break-time="{{ $shift->start_break_time }}" data-end-break-time="{{ $shift->end_break_time }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.shifts.destroy', $shift->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
