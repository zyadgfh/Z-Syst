@foreach ($withdraws as $withdraw)
    <tr>
        <td>{{ $loop->index + 1 }}</td>
        <td>{{ formatted_date($withdraw->created_at) }}</td>
        <td>{{ $withdraw->user?->name }}</td>
        <td>Bank</td>
        <td>{{ currency_format($withdraw->amount) }}</td>
        <td class="text-center">
            <div class="d-flex align-items-center justify-content-center">
                <div class="paid-status">{{ ucfirst($withdraw->status) }}</div>
            </div>
        </td>
        <td class="d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">

                    {{-- <li>
                        <a href="#" class="affiliate-modal-approve" data-bs-toggle="modal"
                            data-url="{{ route('admin.affiliate-withdrawals.paid', $withdraw->id) }}"
                            data-withdraw-id="{{ $withdraw->id }}">
                            <i class="fas fa-paper-plane"></i> {{ __('Approved Payment') }}
                        </a>
                    </li> --}}

                    <li>
                        <a href="#" class="affiliate-modal-approve" data-bs-toggle="modal"
                            data-url="{{ route('admin.affiliate-withdrawals.paid', $withdraw->id) }}"
                            data-date="{{ formatted_date($withdraw->created_at) }}"
                            data-name="{{ $withdraw->user?->name }}"
                            data-amount="{{ currency_format($withdraw->amount) }}"
                            data-status="{{ ucfirst($withdraw->status) }}"
                            >
                            <i class="fas fa-paper-plane"></i> {{ __('Approved Payment') }}
                        </a>

                    </li>

                    <li>
                        <a href="#reject-modal" class="modal-reject" data-bs-toggle="modal"
                            data-url="{{ route('admin.affiliate-withdrawals.reject', $withdraw->id) }}">
                            <i class="fal fa-eye"></i>
                            {{ __('Rejected') }}
                        </a>

                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
