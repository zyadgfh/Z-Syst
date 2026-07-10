<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Shop Name') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Plan') }}</th>
            <th>{{ __('Started') }}</th>
            <th>{{ __('End') }}</th>
            <th>{{ __('Gateway Method') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($manual_payments as $subscriber)
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
