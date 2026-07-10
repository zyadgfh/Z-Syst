@foreach($attendances as $attendance)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $attendance->id }}">
        </td>
        <td>{{ ($attendances->currentPage() - 1) * $attendances->perPage() + $loop->iteration }}</td>

        <td class="text-start">{{ $attendance->employee->name ?? '' }}</td>
        <td class="text-start">{{ $attendance->month ?? '' }}</td>
        <td class="text-start">{{ $attendance->shift->name ?? '' }}</td>
        <td class="text-start">{{ formatted_date($attendance->date) }}</td>
        <td class="text-start">{{ formatted_time($attendance->time_in) }}</td>
        <td class="text-start">{{ formatted_time($attendance->time_out) }}</td>
        <td class="text-start">{{ formatTimeToWords($attendance->duration) }}</td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#attendances-edit-modal" data-bs-toggle="modal" class="attendances-edit-btn"
                        data-url="{{ route('hrm.attendances.update', $attendance->id) }}"
                        data-employee-id="{{ $attendance->employee_id }}"
                        data-month="{{ $attendance->month }}"
                        data-date="{{ $attendance->date }}"
                        data-time-in="{{ $attendance->time_in }}"
                        data-time-out="{{ $attendance->time_out }}"
                        data-note="{{ $attendance->note }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.attendances.destroy', $attendance->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
