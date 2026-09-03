<table>
    <thead>
        <tr>
            <th>{{ __('common.SL') }}.</th>
            <th>{{ __('business.Advertising Name') }}</th>
            <th>{{ __('common.Status') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($banners as $banner)
        <tr class="table-content">
            <td>{{ $loop->iteration }}</td>
            <td>{{ Str::limit($banner->name, 25, '...')}}</td>
            <td>
                <div class="badge bg-{{ $banner->status == 1 ? 'success' : 'danger' }}">
                    {{ $banner->status == 1 ? 'Active' : 'Deactive' }}
                </div>
            </td>

        </tr>
    @endforeach

    </tbody>
</table>
