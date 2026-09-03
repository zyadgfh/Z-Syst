<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('common.Name') }}</th>
            <th>{{ __('common.Country Name') }}</th>
            <th>{{ __('common.Code') }}</th>
            <th>{{ __('common.Symbol') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($currencies as $currency)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $currency->name }}</td>
                <td>{{ $currency->country_name }}</td>
                <td>{{ $currency->code}}</td>
                <td>{{ $currency->symbol }}</td>
            </tr>
        @endforeach

    </tbody>
</table>
