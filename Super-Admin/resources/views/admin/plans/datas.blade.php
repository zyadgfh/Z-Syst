<div class="responsive-table m-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                @can('plans-delete')
                    <th>
                        <div class="d-flex align-items-center gap-3">
                            <label class="table-custom-checkbox">
                                <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                                <span class="table-custom-checkmark custom-checkmark"></span>
                            </label>
                            <i class="fal fa-trash-alt delete-selected"></i>
                        </div>
                    </th>
                @endcan
                <th>{{ __('SL') }}.</th>
                <th class="text-start">{{ __('Subscription Name') }}</th>
                <th>{{ __('Duration') }}</th>
                <th>{{ __('Offer Price') }}</th>
                <th>{{ __('Subscription Price') }}</th>
                @if (moduleCheck('MultiBranchAddon'))
                    <th>{{ __('Allow Multibranch') }}</th>
                @endif
                <th>{{ __('Status') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plans as $plan)
                <tr>
                    @can('plans-delete')
                        <td class="w-60 checkbox text-start">
                            <label class="table-custom-checkbox">
                                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                                    value="{{ $plan->id }}" data-url="{{ route('admin.plans.delete-all') }}">
                                <span class="table-custom-checkmark custom-checkmark"></span>
                            </label>
                        </td>
                    @endcan
                    <td>{{ $plans->perPage() * ($plans->currentPage() - 1) + $loop->iteration }}</td>
                    <td class="text-start">{{ $plan->subscriptionName }} </td>
                    <td>{{ $plan->duration }} </td>
                    <td class="fw-bold text-dark">{{ $plan->offerPrice ? currency_format($plan->offerPrice) : '' }}
                    </td>
                    <td class="fw-bold text-dark">{{ currency_format($plan->subscriptionPrice) }} </td>

                    @if (moduleCheck('MultiBranchAddon'))
                        <td>
                            <div class="badge bg-{{ $plan->allow_multibranch == 1 ? 'success' : 'danger' }}">
                                {{ $plan->allow_multibranch == 1 ? 'Yes' : 'No' }}
                            </div>
                        </td>
                    @endif

                    <td>
                        @can('plans-update')
                            <label class="switch">
                                <input type="checkbox" {{ $plan->status == 1 ? 'checked' : '' }} class="status"
                                    data-url="{{ route('admin.plans.status', $plan->id) }}">
                                <span class="slider round"></span>
                            </label>
                        @endcan
                    </td>

                    <td>
                        <div class="dropdown table-action">
                            <button type="button" data-bs-toggle="dropdown">
                                <i class="far fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                @can('plans-update')
                                    <li>
                                        <a href="{{ route('admin.plans.edit', $plan->id) }}" class="">
                                            <i class="fal fa-edit"></i>
                                            {{ __('Edit') }}
                                        </a>
                                    </li>
                                @endcan

                                @can('plans-delete')
                                    <li>
                                        <a href="{{ route('admin.plans.destroy', $plan->id) }}" class="confirm-action"
                                            data-method="DELETE">
                                            <i class="fal fa-trash-alt"></i>
                                            {{ __('Delete') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </td>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $plans->links('vendor.pagination.bootstrap-5') }}
</div>
