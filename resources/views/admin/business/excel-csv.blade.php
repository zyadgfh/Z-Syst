<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Business Name') }}</th>
            <th>{{ __('Business Category') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Package') }}</th>
            <th>{{ __('Last Enroll') }}</th>
            <th>{{ __('Expired Date') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($businesses as $business)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $business->company_name }}</td>
                <td>{{ $business->category->name ?? '' }}</td>
                <td>{{ $business->phone_number }}</td>
                <td>{{ $business->enrolled_plan?->plan?->subscriptionName }}</td>
                <td>{{ formatted_date($business->subscription_date) }}</td>
                <td>{{ formatted_date($business->will_expire) }}</td>

            </tr>
        @endforeach

    </tbody>
</table>
