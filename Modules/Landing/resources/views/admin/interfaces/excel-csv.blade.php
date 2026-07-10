<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Image') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($interfaces as $interface)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                <img src="{{ asset($interface->image) }}" alt="img">
            </td>
            <td>
                <div>
                    {{ $interface->status == 0 ? 'Active' : 'Deactive' }}
                </div>
            </td>
        </tr>
    @endforeach

    </tbody>
</table>
