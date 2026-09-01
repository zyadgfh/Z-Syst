@foreach ($comments as $comment)
    <tr>
        <td>{{ ($comments->perPage() * ($comments->currentPage() - 1)) + $loop->iteration }} <i class="{{ request('id') == $comment->id ? 'fas fa-bell text-red' : '' }}"></i></td>
        <td>{{ $comment->name }}</td>
        <td>{{ $comment->email }}</td>
        <td>{{  Str::limit($comment->comment, 20, '...') }}</td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    @can('blogs-delete')
                        <li>
                            <a href="{{ route('admin.comments.destroy', $comment->id) }}" class="confirm-action" data-method="DELETE">
                                <i class="fal fa-trash-alt"></i>
                                {{ __('Delete') }}
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </td>
    </tr>
@endforeach
