<div class="responsive-table m-0">
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
                <th> {{ __('SL') }}. </th>
                <th> {{ __('Business Name') }} </th>
                <th> {{ __('Business Category') }} </th>
                <th> {{ __('Phone') }} </th>
                <th> {{ __('Email') }} </th>
                <th> {{ __('Package') }} </th>
                <th> {{ __('Last Enroll') }} </th>
                <th> {{ __('Expired Date') }} </th>
                <th> {{ __('Status') }} </th>
                <th> {{ __('Action') }} </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($businesses as $business)
                <tr>
                    <td class="w-60 checkbox text-start">
                        <label class="table-custom-checkbox">
                            <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item" value="{{ $business->id }}" data-url="{{ route('admin.business.delete-all') }}">
                            <span class="table-custom-checkmark custom-checkmark"></span>
                        </label>
                    </td>
                    <td>{{ ($businesses->currentPage() - 1) * $businesses->perPage() + $loop->iteration }} <i class="{{ request('id') == $business->id ? 'fas fa-bell text-red' : '' }}"></i>
                    </td>
                    <td>{{ $business->companyName }}</td>
                    <td>{{ $business->category->name ?? '' }}</td>
                    <td>{{ $business->phoneNumber }}</td>
                    <td>{{ $business->email }}</td>
                    <td>{{ $business->enrolled_plan->plan->subscriptionName ?? '' }}</td>
                    <td>{{ formatted_date($business->subscriptionDate) }}</td>
                    <td>{{ formatted_date($business->will_expire) }}</td>

                    <td class="text-center">
                        @can('business-update')
                            <label class="switch">
                                <input type="checkbox" {{ $business->status == 1 ? 'checked' : '' }} class="status" data-url="{{ route('admin.business.status', $business->id) }}">
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
                                <li>
                                    <a href="#business-upgrade-modal" class="view-btn business-upgrade-plan"
                                        data-bs-toggle="modal" data-id="{{ $business->id }}"
                                        data-name="{{ $business->companyName }}"
                                        data-url="{{ route('admin.business.upgrade.plan', $business->id) }}">
                                        <i class="fas fa-paper-plane"></i>
                                        {{ __('Upgrade Plan') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="#business-view-modal" class="view-btn business-view" data-bs-toggle="modal"
                                        data-image="{{ asset($business->pictureUrl ?? 'assets/img/default-shop.svg') }}"
                                        data-name="{{ $business->companyName }}"
                                        data-address="{{ $business->address }}"
                                        data-category="{{ $business->category->name ?? '' }}"
                                        data-phone="{{ $business->phoneNumber }}"
                                        data-package="{{ $business->enrolled_plan->plan->subscriptionName ?? '' }}"
                                        data-last_enroll="{{ formatted_date($business->subscriptionDate) }}"
                                        data-expired_date="{{ formatted_date($business->will_expire) }}"
                                        data-created_date="{{ formatted_date($business->created_at) }}">
                                        <i class="fal fa-eye"></i>
                                        {{ __('View') }}
                                    </a>

                                </li>
                                <li>
                                    <a href="{{ route('admin.business.edit', $business->id) }}" class="">
                                        <i class="fal fa-edit"></i>
                                        {{ __('Edit') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.business.destroy', $business->id) }}"
                                        class="confirm-action" data-method="DELETE">
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
    {{ $businesses->links('vendor.pagination.bootstrap-5') }}
</div>
