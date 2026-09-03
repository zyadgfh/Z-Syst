<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('common.Date') }}</th>
            <th>{{ __('gateways.Shop Name') }}</th>
            <th>{{ __('common.Category') }}</th>
            <th>{{ __('common.Package') }}</th>
            <th>{{ __('gateways.Started') }}</th>
            <th>{{ __('gateways.End') }}</th>
            <th>{{ __('gateways.Gateway Method') }}</th>
            <th>{{ __('common.Status') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($subscribers as $subscriber)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ formatted_date($subscriber->created_at) }}</td>
                <td>{{ $subscriber->business->companyName ?? 'N/A' }}</td>
                <td>{{ optional($subscriber->business->category)->name ?? 'N/A' }}</td>
                <td>{{ $subscriber->plan?->subscriptionName }}</td>
                <td>{{ formatted_date($subscriber->created_at) }}</td>
                <td>{{ $subscriber->created_at ? formatted_date($subscriber->created_at->addDays($subscriber->duration)) : ''  }}</td>
                <td>{{ $subscriber->gateway->name ?? 'N/A' }}</td>
                <td>{{ ucfirst($subscriber->payment_status) }}</td>
            </tr>
        @endforeach

    </tbody>
</table>
