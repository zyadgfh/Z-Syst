<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('common.Business Name') }}</th>
            <th>{{ __('common.Business Category') }}</th>
            <th>{{ __('common.Phone') }}</th>
            <th>{{ __('common.Package') }}</th>
            <th>{{ __('business.Last Enroll') }}</th>
            <th>{{ __('business.Expired Date') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($businesses as $business)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $business->companyName }}</td>
                <td>{{ $business->category->name ?? '' }}</td>
                <td>{{ $business->phoneNumber }}</td>
                <td>{{ $business->enrolled_plan?->plan?->subscriptionName }}</td>
                <td>{{ formatted_date($business->subscriptionDate) }}</td>
                <td>{{ formatted_date($business->will_expire) }}</td>

            </tr>
        @endforeach

    </tbody>
</table>
