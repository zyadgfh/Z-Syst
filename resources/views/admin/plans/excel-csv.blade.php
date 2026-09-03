<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('business.Subscription Name') }}</th>
            <th>{{ __('business.Duration') }}</th>
            <th>{{ __('business.Offer Price') }}</th>
            <th>{{ __('business.Subscription Price') }}</th>
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
