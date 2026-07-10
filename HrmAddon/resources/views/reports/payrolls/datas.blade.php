@foreach($payrolls as $payroll)
    <tr>
        <td>{{ ($payrolls->currentPage() - 1) * $payrolls->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $payroll->employee->name ?? '' }}</td>
        <td class="text-start">{{ $payroll->month }}</td>
        <td class="text-start">{{ $payroll->puid }}</td>
        <td class="text-start">{{ formatted_date($payroll->date) }}</td>
        <td class="text-start">{{ currency_format($payroll->amount) }}</td>
        <td class="text-start">{{ $payroll->payment_type->name ?? '' }}</td>
        @if ($payroll->status == 'unpaid')
        <td class="text-warning  text-center">
            <div class="padding-status">Unpaid</div>
        </td>
        @else
        <td class="text-warning text-center">
            <div class="approved-status">Paid</div>
        </td>
        @endif
    </tr>
@endforeach
