@foreach($attendances as $attendance)
    <tr>
        <td>{{ ($attendances->currentPage() - 1) * $attendances->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $attendance->employee->name ?? '' }}</td>
        <td class="text-start">{{ $attendance->month ?? '' }}</td>
        <td class="text-start">{{ $attendance->shift->name ?? '' }}</td>
        <td class="text-start">{{ formatted_date($attendance->date) }}</td>
        <td class="text-start">{{ formatted_time($attendance->time_in) }}</td>
        <td class="text-start">{{ formatted_time($attendance->time_out) }}</td>
        <td class="text-start">{{ formatTimeToWords($attendance->duration) }}</td>
    </tr>
@endforeach
