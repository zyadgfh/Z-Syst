<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Slug') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($blogs as $blog)
        <tr>
            <td>{{ $loop->iteration }} </td>
            <td>{{ Str::limit($blog->title, 25, '...') }}</td>
            <td>{{ Str::limit($blog->slug, 25, '...') }}</td>
            @if($blog->status == 1)
            <td>{{ __('Active') }}</td>
            @else
            <td>{{ __('Inactive') }}</td>
            @endif
        </tr>
    @endforeach

    </tbody>
</table>
