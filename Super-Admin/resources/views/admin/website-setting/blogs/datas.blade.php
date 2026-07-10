<div class="responsive-table m-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                @can('users-delete')
                    <th>
                        <div class="d-flex align-items-center gap-1">
                            <label class="table-custom-checkbox">
                                <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                                <span class="table-custom-checkmark custom-checkmark"></span>
                            </label>
                            <i class="fal fa-trash-alt delete-selected"></i>
                        </div>
                    </th>
                @endcan
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Image') }}</th>
                <th>{{ __('Title') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="d-print-none">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($blogs as $blog)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                value="{{ $blog->id }}" data-url="{{ route('admin.blogs.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i></i>
                    </td>
                    <td>{{ ($blogs->currentPage() - 1) * $blogs->perPage() + $loop->iteration }}</td>
                    <td>
                        <img height="45" width="45" class="table-img border-1"
                            src="{{ asset($blog->image ?? 'assets/images/profile/avatar.jpg') }}" alt="">
                    </td>
                    <td>{{ Str::limit($blog->title, 25, '...') }}</td>
                    <td class="text-center w-150">
                        <label class="switch">
                            <input type="checkbox" {{ $blog->status == 1 ? 'checked' : '' }} class="status"
                                data-url="{{ route('admin.blogs.status', $blog->id) }}">
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td class="d-print-none">
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="{{ route('admin.blogs.edit', $blog->id) }}">
                                        <i class="fal fa-pencil-alt"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.blogs.destroy', $blog->id) }}" class="confirm-action"
                                        data-method="DELETE">
                                        <i class="fal fa-trash-alt"></i>
                                        {{ __('Delete') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.blogs.filter.comment', $blog->id) }}">
                                        <i class='fas fa-comment'></i>
                                        {{ __('Comment') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $blogs->links('vendor.pagination.bootstrap-5') }}
</div>
