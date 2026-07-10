<div class="responsive-table m-0">
    <table class="table" id="datatable">
        <thead>
            <tr>
                <th>{{ __('SL') }}.</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Shop Name') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Package') }}</th>
                <th>{{ __('Started') }}</th>
                <th>{{ __('End') }}</th>
                <th>{{ __('Gateway Method') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscribers as $subscriber)
                <tr>
                    <td>{{ ($subscribers->currentPage() - 1) * $subscribers->perPage() + $loop->iteration }} <i class="{{ request('id') == $subscriber->id ? 'fas fa-bell text-red' : '' }}"></i>
                    </td>
                    <td>{{ formatted_date($subscriber->created_at) }}</td>
                    <td>{{ $subscriber->business->companyName ?? 'N/A' }}</td>
                    <td>{{ $subscriber->business?->category?->name ?? 'N/A' }}</td>
                    <td>{{ $subscriber->plan->subscriptionName ?? 'N/A' }}</td>
                    <td>{{ formatted_date($subscriber->created_at) }}</td>
                    <td>{{ $subscriber->created_at ? formatted_date($subscriber->created_at->addDays($subscriber->duration)) : '' }}
                    </td>
                    <td>{{ $subscriber->gateway->name ?? 'N/A' }}</td>
                    <td>
                        <div
                            class="badge bg-{{ $subscriber->payment_status == 'reject' ? 'danger' : ($subscriber->payment_status == 'unpaid' ? 'warning' : 'primary') }}">
                            {{ ucfirst($subscriber->payment_status) }}
                        </div>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>
<div class="mt-3">
    {{ $subscribers->links('vendor.pagination.bootstrap-5') }}
</div>
