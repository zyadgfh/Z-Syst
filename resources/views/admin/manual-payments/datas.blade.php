@foreach ($manual_payments as $payment)
    <tr class="table-content">
        <td class="table-single-content">{{ ($manual_payments->perPage() * ($manual_payments->currentPage() - 1)) + $loop->iteration }} <i class="{{ request('id') == $payment->id ? 'fas fa-bell text-red' : '' }}"> </td>
        <td class="table-single-content">{{ formatted_date($payment->created_at) }}</td>
        <td class="table-single-content">{{ $payment->business->companyName ?? 'N/A' }}</td>
        <td class="table-single-content">{{ optional($payment->business->category)->name ?? 'N/A' }}</td>
        <td class="table-single-content">
            <div class="d-flex align-items-center justify-content-center ">
                <div
                    class="
                @if ($payment->plan->subscriptionName === 'Free') free-bg
                @elseif($payment->plan->subscriptionName === 'Premium') premium-bg
                @elseif($payment->plan->subscriptionName === 'Standard') standard-bg @endif
               ">
                    {{ $payment->plan->subscriptionName }}
                </div>
            </div>
        </td>
        <td class="table-single-content">{{ formatted_date($payment->created_at) }}</td>
        <td class="table-single-content">{{ $payment->created_at ? formatted_date($payment->created_at->addDays($payment->duration)) : '' }}</td>
        <td class="table-single-content">{{ $payment->gateway->name ?? 'N/A' }}</td>

        <td class="table-single-content">
            <div class="text-{{
                $payment->payment_status == 'paid' ? 'success' :
                ($payment->payment_status == 'unpaid' ? 'warning' : 'danger')
            }}">
                {{ ucfirst($payment->payment_status) }}
            </div>
        </td>

        <td class="table-single-content d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">

                    <li>
                        <a href="#manual-view-modal" class="manual-subscriber-view" data-bs-toggle="modal"
                        data-name="{{ $payment->business->companyName ?? 'N/A' }}"
                        data-image="{{ asset($payment->business->pictureUrl ?? 'assets/img/default-shop.svg') }}"
                        data-manul-attachment="{{ asset($payment->notes['attachment'] ?? '') }}"
                        data-category="{{ optional($payment->business->category)->name ?? 'N/A' }}"
                            data-package="{{ $payment->plan->subscriptionName ?? 'N/A' }}"
                            data-gateway="{{ $payment->gateway->name ?? 'N/A' }}"
                            data-enroll="{{ formatted_date($payment->created_at) }}"
                            data-expired="{{  $payment->created_at ? formatted_date($payment->created_at->addDays($payment->duration)) : '' }}"
                        >
                           <img src="{{ asset('assets/images/icons/eye.svg') }}" alt="">
                            {{ __('View') }}
                        </a>

                    </li>

                    <li>
                        <a target="_blank" href="{{ route('admin.manual-payments.invoice', $payment->id) }}">
                            <img src="{{ asset('assets/images/icons/invoice.svg') }}" alt="">                            {{ __('Invoice') }}
                        </a>
                    </li>

                    @if($payment->payment_status == 'unpaid')
                    <li>
                        <a href="#approve-modal" class="manual-payment-modal" data-bs-toggle="modal" data-bs-target="#approve-modal" data-url="{{ route('admin.manual-payments.paid', $payment->id) }}">
                            <img src="{{ asset('assets/images/icons/accept.svg') }}" alt="">
                            {{ __('Accept') }}
                        </a>
                    </li>
                    <li>
                        <a href="#reject-modal" class="manual-payment-reject-modal" data-bs-toggle="modal" data-bs-target="#reject-modal" data-url="{{ route('admin.manual-payments.reject', $payment->id) }}">
                            <img src="{{ asset('assets/images/icons/reject.svg') }}" alt="">
                            {{ __('Reject') }}
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </td>
    </tr>
@endforeach


