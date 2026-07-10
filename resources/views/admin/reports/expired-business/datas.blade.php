@foreach ($expired_businesses as $business)
    <tr class="table-content">
        <td class="table-single-content">{{ $expired_businesses->perPage() * ($expired_businesses->currentPage() - 1) + $loop->iteration }} <i class="{{ request('id') == $business->id ? 'fas fa-bell text-red' : '' }}"></i></td>
        <td class="table-single-content">{{ $business->companyName }}</td>
        <td class="table-single-content">{{ $business->category->name ?? '' }}</td>
        <td class="table-single-content">{{ $business->phoneNumber }}</td>
        <td class="table-single-content">
            <div class="d-flex align-items-center justify-content-center ">
                <div
                    class="
                @if ($business->enrolled_plan?->plan?->subscriptionName === 'Free') free-bg
                @elseif($business->enrolled_plan?->plan?->subscriptionName === 'Premium') premium-bg
                @elseif($business->enrolled_plan?->plan?->subscriptionName === 'Standard') standard-bg @endif
               ">
                    {{ $business->enrolled_plan?->plan?->subscriptionName }}
                </div>
            </div>
        </td>
        <td class="table-single-content">{{ formatted_date($business->subscriptionDate) }}</td>
        <td class="table-single-content">{{ formatted_date($business->will_expire) }}</td>
    </tr>
@endforeach
