<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($features as $feature)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $feature->title }}</td>
                @if ($feature->status == 1)
                    <td>{{ __('Active') }}</td>
                @else
                    <td>{{ __('Inactive') }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
