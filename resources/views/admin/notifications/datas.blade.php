@foreach ($notifications  as $notification)
    <tr class="table-content">
        <td class="table-single-content">{{ $loop->index+1 }}</td>
        <td class="table-single-content">{{ $notification->data['message'] ?? '' }}</td>
        <td class="table-single-content">{{ formatted_date($notification->created_at, 'd M Y - H:i A') }}</td>
        <td class="table-single-content">{{ formatted_date($notification->read_at, 'd M Y - H:i A') }}</td>
        <td class="table-single-content">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="{{ route('admin.notifications.mtView', $notification->id) }}"><i class="fas fa-eye"></i> @lang('View')</a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
