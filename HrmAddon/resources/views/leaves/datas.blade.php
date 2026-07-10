@foreach($leaves as $leave)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $leave->id }}">
        </td>
        <td>{{ ($leaves->currentPage() - 1) * $leaves->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $leave->employee->name ?? '' }}</td>
        <td class="text-start">{{ $leave->month ?? '' }}</td>
        <td class="text-start">{{ $leave->department->name ?? '' }}</td>
        <td class="text-start">{{ formatted_date($leave->start_date) }}</td>
        <td class="text-start">{{ formatted_date($leave->end_date) }}</td>
        <td class="text-start">{{ $leave->leave_type->name ?? '' }}</td>
        <td class="text-start">{{ $leave->leave_duration }} {{ __('Days') }}</td>
        @if ($leave->status == 'pending')
        <td class="text-warning text-center">
            <div class="padding-status">Pending</div>
        </td>
        @elseif ($leave->status == 'approved')
        <td class="text-warning text-center">
            <div class="approved-status">Approved</div>
        </td>
        @elseif ($leave->status == 'rejected')
        <td class="text-warning text-center">
            <div class="rejected-status">Rejected</div>
        </td>
        @endif
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#leaves-edit-modal" data-bs-toggle="modal" class="leaves-edit-btn"
                        data-url="{{ route('hrm.leaves.update', $leave->id) }}"
                        data-employee-id="{{ $leave->employee_id }}"
                        data-month="{{ $leave->month }}"
                        data-leave-type-id="{{ $leave->leave_type_id }}"
                        data-start-date="{{ $leave->start_date }}"
                        data-end-date="{{ $leave->end_date }}"
                        data-leave-duration="{{ $leave->leave_duration }}"
                        data-status="{{ $leave->status }}"
                        data-description="{{ $leave->description }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.leaves.destroy', $leave->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
