@foreach($leaves as $leave)
    <tr>
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
    </tr>
@endforeach

