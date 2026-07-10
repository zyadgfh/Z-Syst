<div class="responsive-table mt-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                <th>
                    <div class="d-flex align-items-center gap-1">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                        <i class="fal fa-trash-alt delete-selected"></i>
                    </div>
                </th>
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Comment') }}</th>
                <th class="d-print-none">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($comments as $comment)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item" value="{{ $comment->id }}" data-url="{{ route('admin.comments.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                    </td>

                    <td>
                        {{ ($comments->currentPage() - 1) * $comments->perPage() + $loop->iteration }}
                    </td>
                    <td>{{ $comment->name }}</td>
                    <td>{{ $comment->email }}</td>
                    <td>{{ Str::limit($comment->comment, 20, '...') }}</td>
                    <td class="d-print-none">
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="{{ route('admin.comments.destroy', $comment->id) }}" class="confirm-action"
                                        data-method="DELETE">
                                        <i class="fal fa-trash-alt"></i>
                                        {{ __('Delete') }}
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
    {{ $comments->links('vendor.pagination.bootstrap-5') }}
</div>
