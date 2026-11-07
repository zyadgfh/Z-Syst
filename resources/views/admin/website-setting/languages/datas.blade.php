<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>{{ __('Name') }}</th><th>{{ __('Icon') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>
            @foreach($languages ?? [] as $language)
                <tr>
                    <td>{{ $language->name }}</td>
                    <td>{{ $language->icon }}</td>
                    <td><span class="badge {{ $language->status ? 'bg-success' : 'bg-secondary' }}">{{ $language->status ? __('Active') : __('Inactive') }}</span></td>
                    <td><a href="{{ route('admin.website-setting.languages.edit', $language) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
