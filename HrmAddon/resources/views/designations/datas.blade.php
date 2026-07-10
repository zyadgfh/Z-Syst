@foreach($designations as $designation)
    <tr>
        <td class="w-60 checkbox">
            <input type="checkbox" name="ids[]" class="delete-checkbox-item  multi-delete" value="{{ $designation->id }}">
        </td>
        <td>{{ ($designations->currentPage() - 1) * $designations->perPage() + $loop->iteration }}</td>
    
        <td class="text-start">{{ $designation->name }}</td>
        <td class="text-start">{{ Str::limit($designation->description, 20, '...') }}</td>
        <td>
            <label class="switch">
                <input type="checkbox" {{ $designation->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('hrm.designations.status', $designation->id) }}">
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
                        <a href="#designations-edit-modal" data-bs-toggle="modal" class="designations-edit-btn"
                        data-url="{{ route('hrm.designations.update', $designation->id) }}"
                        data-designations-name="{{ $designation->name }}"
                        data-designations-description="{{ $designation->description }}">
                        <i class="fal fa-pencil-alt"></i>{{__('Edit')}}</a>
                    </li>
                    <li>
                        <a href="{{ route('hrm.designations.destroy', $designation->id) }}" class="confirm-action" data-method="DELETE">
                            <i class="fal fa-trash-alt"></i>
                            {{ __('Delete') }}
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@endforeach
