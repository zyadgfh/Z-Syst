@foreach($leave_types as $leave_type)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $leave_type->id }}">
        </td>
        <td>{{ ($leave_types->currentPage() - 1) * $leave_types->perPage() + $loop->iteration }}</td>
    
        <td class="text-start">{{ $leave_type->name }}</td>
        <td class="text-start">{{ Str::limit($leave_type->description, 20, '...') }}</td>
        <td>
            <label class="switch">
                <input type="checkbox" {{ $leave_type->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('hrm.leave-types.status', $leave_type->id) }}">
                <span class="slider round"></span>
            </label>
        </td>
        <td class="print-d-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#leave-types-edit-modal" data-bs-toggle="modal" class="leave-types-edit-btn"
                        data-url="{{ route('hrm.leave-types.update', $leave_type->id) }}"
                        data-leave-types-name="{{ $leave_type->name }}"
                        data-leave-types-description="{{ $leave_type->description }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.leave-types.destroy', $leave_type->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
