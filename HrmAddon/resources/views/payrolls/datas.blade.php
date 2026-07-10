@foreach($payrolls as $payroll)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $payroll->id }}">
        </td>
        <td>{{ ($payrolls->currentPage() - 1) * $payrolls->perPage() + $loop->iteration }}</td>
        <td class="text-start">{{ $payroll->employee->name ?? '' }}</td>
        <td class="text-start">{{ $payroll->payemnt_year }}</td>
        <td class="text-start">{{ $payroll->month }}</td>
        <td class="text-start">{{ $payroll->puid }}</td>
        <td class="text-start">{{ formatted_date($payroll->date) }}</td>
        <td class="text-start">{{ currency_format($payroll->amount, 'icon', 2, business_currency()) }}</td>
        <td class="text-start">{{ $payroll->payment_type->name ?? '' }}</td>
        <td class="text-warning text-center">
            <div class="approved-status">Paid</div>
        </td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#payrolls-edit-modal" data-bs-toggle="modal" class="payrolls-edit-btn"
                        data-url="{{ route('hrm.payrolls.update', $payroll->id) }}"
                        data-employee-id="{{ $payroll->employee_id }}"
                        data-payment-type-id="{{ $payroll->payment_type_id }}"
                        data-date="{{ $payroll->date }}"
                        data-month="{{ $payroll->month }}"
                        data-note="{{ $payroll->note }}"
                        data-amount="{{ $payroll->amount }}"
                        data-payment-year="{{ $payroll->payemnt_year }}"
                        >
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.payrolls.destroy', $payroll->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
