<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Business Name') }}</th>
            <th>{{ __('Description') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($categories as $category)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ Str::limit($category->description, 25, '...') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
