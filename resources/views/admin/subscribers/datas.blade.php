@foreach ($subscribers as $subscriber)
    <tr class="table-content">
        <td class="table-single-content">{{ ($subscribers->perPage() * ($subscribers->currentPage() - 1)) + $loop->iteration }} <i class="{{ request('id') == $subscriber->id ? 'fas fa-bell text-red' : '' }}"> </td>
        <td class="table-single-content">{{ formatted_date($subscriber->created_at) }}</td>
        <td class="table-single-content">{{ $subscriber->business->companyName ?? 'N/A' }}</td>
        <td class="table-single-content">{{ optional($subscriber->business->category)->name ?? 'N/A' }}</td>
        <td class="table-single-content">
            <div class="d-flex align-items-center justify-content-center ">
                <div
                    class="
                @if ($subscriber->plan->subscriptionName === 'Free') free-bg
                @elseif($subscriber->plan->subscriptionName === 'Premium') premium-bg
                @elseif($subscriber->plan->subscriptionName === 'Standard') standard-bg @endif
               ">
                    {{ $subscriber->plan->subscriptionName }}
                </div>
            </div>
        </td>
        <td class="table-single-content">{{ formatted_date($subscriber->created_at) }}</td>
        <td class="table-single-content">{{ $subscriber->created_at ? formatted_date($subscriber->created_at->addDays($subscriber->duration)) : '' }}</td>
        <td class="table-single-content">{{ $subscriber->gateway->name ?? 'N/A' }}</td>

        <td class="table-single-content">
            <div class="text-{{
                $subscriber->payment_status == 'paid' ? 'success' :
                ($subscriber->payment_status == 'unpaid' ? 'warning' : 'danger')
            }}">
                {{ ucfirst($subscriber->payment_status) }}
            </div>
        </td>

        <td class="table-single-content d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">

                    <li>
                        <a href="#subscriber-view-modal" class="subscriber-view" data-bs-toggle="modal"
                        data-name="{{ $subscriber->business->companyName ?? 'N/A' }}"
                        data-image="{{ asset($subscriber->business->pictureUrl ?? 'assets/img/default-shop.svg') }}"
                        data-manul-attachment="{{ asset($subscriber->notes['attachment'] ?? '') }}"
                        data-category="{{ optional($subscriber->business->category)->name ?? 'N/A' }}"
                            data-package="{{ $subscriber->plan->subscriptionName ?? 'N/A' }}"
                            data-gateway="{{ $subscriber->gateway->name ?? 'N/A' }}"
                            data-enroll="{{ formatted_date($subscriber->created_at) }}"
                            data-expired="{{  $subscriber->created_at ? formatted_date($subscriber->created_at->addDays($subscriber->duration)) : '' }}"
                        >
                            <img src="{{ asset('assets/images/icons/eye.svg') }}" alt="">
                            {{ __('View') }}
                        </a>

                    </li>

                    <li>
                        <a target="_blank" href="{{ route('admin.subscription-reports.invoice', $subscriber->id) }}">
                            <img src="{{ asset('assets/images/icons/invoice.svg') }}" alt="">                            {{ __('Invoice') }}
                        </a>
                    </li>

                    @if($subscriber->payment_status == 'unpaid')
                    <li>
                        <a href="#approve-modal" class="modal-approve" data-bs-toggle="modal" data-bs-target="#approve-modal" data-url="{{ route('admin.subscription-reports.paid', $subscriber->id) }}">
                            <img src="{{ asset('assets/images/icons/accept.svg') }}" alt="">
                            {{ __('Accept') }}
                        </a>
                    </li>
                    <li>
                        <a href="#reject-modal" class="modal-reject" data-bs-toggle="modal" data-bs-target="#reject-modal" data-url="{{ route('admin.subscription-reports.reject', $subscriber->id) }}">
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


