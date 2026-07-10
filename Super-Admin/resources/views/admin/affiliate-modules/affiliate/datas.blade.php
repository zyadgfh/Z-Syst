@foreach ($affiliates as $affiliate)
    <tr>
        <td class="w-60 checkbox text-start">
            <label class="table-custom-checkbox">
                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item"
                    value="{{ $affiliate->id }}" data-url="{{ route('admin.affiliates.delete-all') }}">
                <span class="table-custom-checkmark custom-checkmark"></span>
            </label>
        </td>
        <td>{{ $loop->index + 1 }}</td>
        <td>{{ formatted_date($affiliate->created_at) }}</td>
        <td>{{ $affiliate->user?->name }}</td>
        <td>{{ $affiliate->user?->email }}</td>
        <td>{{ $affiliate->user?->business?->enrolled_plan?->plan?->subscriptionName ?? '' }}</td>
        <td>{{ remaining_days($affiliate->user?->business?->will_expire) }}</td>
        <td>{{ formatted_date($affiliate->user?->business?->will_expire) }}</td>
        <td>{{ currency_format($affiliate->balance) }}</td>
        <td>
            <label class="switch">
                <input type="checkbox" class="status">
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
                        <a href="#business-upgrade-modal" class="view-btn business-upgrade-plan" data-bs-toggle="modal"
                            data-id="{{ $affiliate->user?->business?->id }}" data-name="{{ $affiliate->user?->business?->companyName }}"
                            data-url="{{ route('admin.business.upgrade.plan', $affiliate->id) }}">
                            <i class="fas fa-paper-plane"></i>
                            {{ __('Upgrade Plan') }}
                        </a>
                    </li>
                    <li>
                        <a href="#affiliate-view-modal" class="affiliate-view" data-bs-toggle="modal"
                            data-date="{{ formatted_date($affiliate->created_at) }}"
                            data-name="{{ $affiliate->user?->name }}"
                            data-email="{{ $affiliate->user?->email }}"
                            data-plan="{{ $affiliate->user?->business?->enrolled_plan->plan->subscriptionName ?? '' }}"
                            data-duration="{{ remaining_days($affiliate->user?->business?->will_expire) }}"
                            data-expire-date="{{ formatted_date($affiliate->user?->business?->will_expire) }}"
                            data-total-earn="{{ currency_format($affiliate->balance) }}"
                            >
                            <i class="fal fa-eye"></i>
                            {{ __('View') }}
                        </a>

                    </li>
                    <li>
                        <a href="{{ route('admin.affiliates.destroy', $affiliate->id) }}" class="confirm-action"
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
