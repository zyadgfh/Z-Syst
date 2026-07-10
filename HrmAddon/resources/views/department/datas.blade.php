@foreach($departments as $department)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $department->id }}">
        </td>
        <td>{{ ($departments->currentPage() - 1) * $departments->perPage() + $loop->iteration }}</td>
    
        <td class="text-start">{{ $department->name }}</td>
        <td class="text-start">{{ Str::limit($department->description, 20, '...') }}</td>
        <td>
            <label class="switch">
                <input type="checkbox" {{ $department->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('hrm.department.status', $department->id) }}">
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
                        <a href="#department-edit-modal" data-bs-toggle="modal" class="department-edit-btn"
                        data-url="{{ route('hrm.department.update', $department->id) }}"
                        data-department-name="{{ $department->name }}"
                        data-department-description="{{ $department->description }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.department.destroy', $department->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
