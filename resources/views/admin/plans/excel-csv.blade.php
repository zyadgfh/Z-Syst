<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Subscription Name') }}</th>
            <th>{{ __('Duration') }}</th>
            <th>{{ __('Offer Price') }}</th>
            <th>{{ __('Subscription Price') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($plans as $plan)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $plan->subscriptionName }}</td>
                <td>{{ $plan->duration  }}</td>
                <td>{{ currency_format($plan->offerPrice) }}</td>
                <td>{{ currency_format($plan->subscriptionPrice) }}</td>
            </tr>
        @endforeach

    </tbody>
</table>
