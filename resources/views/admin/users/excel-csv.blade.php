<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('common.Name') }}</th>
            <th>{{ __('common.Phone') }}</th>
            <th>{{ __('roles.User Email') }}</th>
            <th>{{ __('roles.User Role') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role }}</td>
            </tr>
        @endforeach

    </tbody>
</table>
