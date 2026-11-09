<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}. </th>
            <th>{{ __('Business Name') }} </th>
            <th>{{ __('Business Category') }} </th>
            <th>{{ __('Phone') }} </th>
            <th>{{ __('Package') }} </th>
            <th>{{ __('Last Enroll') }} </th>
            <th>{{ __('Expired Date') }} </th>
        </tr>
    </thead>

    <tbody>
        @foreach ($active_stores as $store)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $store->company_name }}</td>
            <td>{{ $store->category->name ?? '' }}</td>
            <td>{{ $store->phone_number }}</td>
            <td>
                <div>
                    <div>
                        {{ $store->enrolled_plan?->plan?->subscriptionName }}
                    </div>
                </div>
            </td>
            <td>{{ formatted_date($store->subscription_date) }}</td>
            <td>{{ formatted_date($store->will_expire) }}</td>
        </tr>
        @endforeach

    </tbody>
</table>
