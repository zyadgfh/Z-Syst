<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Country Name') }}</th>
            <th>{{ __('Code') }}</th>
            <th>{{ __('Symbol') }}</th>
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
