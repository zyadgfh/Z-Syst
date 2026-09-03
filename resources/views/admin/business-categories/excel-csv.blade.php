<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('common.Business Name') }}</th>
            <th>{{ __('common.Description') }}</th>
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
