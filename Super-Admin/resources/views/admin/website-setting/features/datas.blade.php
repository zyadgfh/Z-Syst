 <div class="responsive-table m-0">
     <table class="table" id="datatable">
         <thead>
             <tr>
                 @can('features-delete')
                     <th style="width: 0; text-align:start">
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
                 <th>{{ __('Action') }}</th>
             </tr>
         </thead>
         <tbody>
             @foreach ($features as $feature)
                 <tr>
                     @can('features-delete')
                         <td class="w-60 checkbox text-start">
                             <label class="table-custom-checkbox">
                                 <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                     value="{{ $feature->id }}" data-url="{{ route('admin.features.delete-all') }}">
                                 <span class="table-custom-checkmark custom-checkmark"></span>
                             </label>
                         </td>
                     @endcan
                     <td>{{ ($features->currentPage() - 1) * $features->perPage() + $loop->iteration }}</td>
                     <td>
                        <img class="table-img" src="{{ asset($feature->image) }}" alt="img">
                     </td>
                     <td>{{ $feature->title }}</td>
                     <td class="text-center w-150">
                         @can('features-update')
                             <label class="switch">
                                 <input type="checkbox" {{ $feature->status == 1 ? 'checked' : '' }} class="status"
                                     data-url="{{ route('admin.features.status', $feature->id) }}">
                                 <span class="slider round"></span>
                             </label>
                         @endcan
                     </td>
                     <td class="d-print-none">
                         <div class="dropdown table-action">
                             <button type="button" data-bs-toggle="dropdown">
                                 <i class="far fa-ellipsis-v"></i>
                             </button>
                             <ul class="dropdown-menu">
                                 @can('features-update')
                                     <li>
                                         <a href="{{ route('admin.features.edit', $feature->id) }}">
                                             <i class="fal fa-pencil-alt"></i>
                                             {{ __('Edit') }}
                                         </a>
                                     </li>
                                 @endcan
                                 @can('features-delete')
                                     <li>
                                         <a href="{{ route('admin.features.destroy', $feature->id) }}"
                                             class="confirm-action" data-method="DELETE">
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
         </tbody>
     </table>
 </div>

 <div class="mt-3">
    {{ $features->links('vendor.pagination.bootstrap-5') }}
</div>
