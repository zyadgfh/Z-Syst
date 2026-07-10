@foreach ($items as $business)
    <tr>
        <td>{{ $loop->index + 1 }}</td>
        <td>{{ formatted_date($business->created_at) }}</td>
        <td>{{ $business->companyName }}</td>
        <td class="text-center">
        @if ($business->enrolled_plan?->plan?->subscriptionName == 'Free')
        <span
        class="free-badge">{{ $business->enrolled_plan?->plan?->subscriptionName }}</span>
        @elseif($business->enrolled_plan?->plan?->subscriptionName == 'Premium')
        <span
            class="premium-badge">{{ $business->enrolled_plan?->plan?->subscriptionName }}</span>
                @elseif($business->enrolled_plan?->plan?->subscriptionName == 'Standard')
        <span
            class="standard-badge">{{ $business->enrolled_plan?->plan?->subscriptionName }}</span>
                @else
        @endif
        </td>
        <td>{{ remaining_days($business->will_expire) }}</td>
        <td>{{ formatted_date($business->will_expire) }}</td>
        <td>550$</td>
        <td class="text-center">
            <div class="{{ $business->status == 1 ? 'active-status' : 'dective-status' }}">
                {{ $business->status == 1 ? 'Active' : 'Inactive' }}
            </div>
        </td>
    </tr>
@endforeach
