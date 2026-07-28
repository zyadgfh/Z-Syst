<table>
    <thead>
        <tr>
            <th>{{ __('SL') }}.</th>
            <th>{{ __('Stars') }}</th>
            <th>{{ __('Client Name') }}</th>
            <th>{{ __('Work At') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($testimonials as $testimonial )
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
             {{ $testimonial->star }}
            </td>
            <td>{{ $testimonial->client_name }}</td>
            <td>{{ $testimonial->work_at }}</td>
        </tr>
        @endforeach

    </tbody>
</table>
