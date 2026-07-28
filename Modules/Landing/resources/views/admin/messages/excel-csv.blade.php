<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Company Name') }}</th>
            <th>{{ __('Message') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($messages as $message)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $message->name }}</td>
            <td>{{ $message->phone }}</td>
            <td>{{ $message->email }}</td>
            <td>{{ $message->company_name }}</td>
            <td>{{ Str::limit($message->message, 40, '...') }}</td>
        </tr>
    @endforeach

    </tbody>
</table>
