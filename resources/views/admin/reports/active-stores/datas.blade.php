@foreach ($active_stores as $store)
<tr class="table-content">
    <td class="table-single-content">{{ $active_stores->perPage() * ($active_stores->currentPage() - 1) + $loop->iteration }} <i class="{{ request('id') == $store->id ? 'fas fa-bell text-red' : '' }}"></i></td>
    <td class="table-single-content">{{ $store->companyName }}</td>
    <td class="table-single-content">{{ $store->category->name ?? '' }}</td>
    <td class="table-single-content">{{ $store->phoneNumber }}</td>
    <td class="table-single-content">
        <div class="d-flex align-items-center justify-content-center">
            <div class="
            @if ($store->enrolled_plan?->plan?->subscriptionName === 'Free') free-bg
            @elseif($store->enrolled_plan?->plan?->subscriptionName === 'Premium') premium-bg
            @elseif($store->enrolled_plan?->plan?->subscriptionName === 'Standard') standard-bg @endif">
                {{ $store->enrolled_plan?->plan?->subscriptionName }}
            </div>
        </div>
    </td>
    <td class="table-single-content">{{ formatted_date($store->subscriptionDate) }}</td>
    <td class="table-single-content">{{ formatted_date($store->will_expire) }}</td>
</tr>
@endforeach


