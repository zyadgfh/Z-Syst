@foreach ($employees as $employee)
    <tr>
        <td class="w-60 checkbox d-print-none">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $employee->id }}">
        </td>
        <td>{{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}</td>

        <td>
            <img src="{{ asset($employee->image ?? 'assets/images/logo/upload2.jpg') }}" alt="Img" class="table-product-img">
        </td>

        <td>{{ $employee->name }}</td>
        <td>{{ $employee->department->name ?? '' }}</td>
        <td>{{ $employee->designation->name ?? '' }}</td>
        <td>{{ $employee->shift->name ?? '' }}</td>
        <td>{{ $employee->phone }}</td>
        <td>{{ currency_format($employee->amount, 'icon', 2, business_currency()) }}</td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#employees-view" class="employees-view" data-bs-toggle="modal"
                        data-employees-name="{{ $employee->name }}"
                        data-employees-gender="{{ $employee->gender }}"
                        data-employees-phone="{{ $employee->phone }}"
                        data-employees-amount="{{ currency_format($employee->amount, 'icon', 2, business_currency()) }}"
                        data-employees-email="{{ $employee->email }}"
                        data-employees-country="{{ $employee->country }}"
                        data-employees-birth-date="{{ $employee->birth_date }}"
                        data-employees-join-date="{{ $employee->join_date }}"
                        data-employees-designation="{{ $employee->designation->name ?? '' }}"
                        data-employees-department="{{ $employee->department->name ?? '' }}"
                        data-employees-shift="{{ $employee->shift->name ?? '' }}"
                        data-employees-status="{{ $employee->status }}"
                        data-employees-image="{{ asset($employee->image ?? 'assets/images/logo/upload2.jpg') }}">
                            <i class="fal fa-eye"></i>
                            {{ __('View') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.employees.edit', $employee->id) }}">
                            <i class="fal fa-edit"></i>
                            {{ __('Edit') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.employees.destroy', $employee->id) }}" class="confirm-action"
                            data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
