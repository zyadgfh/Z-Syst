@foreach ($prescriptions as $prescription)
    <tr class="table-content">
        <td class="w-60 checkbox table-single-content d-print-none">
            <label class="table-custom-checkbox">
                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item delete-checkbox-item multi-delete" value="{{ $prescription->id }}">
                <span class="table-custom-checkmark custom-checkmark"></span>
            </label>
        </td>

        <td>{{ $prescriptions->perPage() * ($prescriptions->currentPage() - 1) + $loop->iteration }}</td>
        <td>
            <a href="{{ asset($prescription->image) }}" target="_blank">
                <img class="table-img" height="50px" width="50px" style="object-fit:cover; border-radius:4px;" src="{{ asset($prescription->image) }}" alt="Prescription">
            </a>
        </td>
        <td class="table-single-content">{{ $prescription->party ? $prescription->party->name : __('Walk-in Customer') }}</td>
        <td class="table-single-content">
            @if ($prescription->sale)
                <a href="#">{{ $prescription->sale->invoiceNumber }}</a>
            @else
                <span class="text-muted">--</span>
            @endif
        </td>
        <td class="table-single-content">{{ Str::limit($prescription->notes ?? '--', 30, '...') }}</td>
        <td class="text-center">
            @can('prescriptions-update')
                <label class="switch">
                    <input type="checkbox" {{ $prescription->status == 'used' ? 'checked' : '' }} class="status"
                        data-url="{{ route('admin.prescriptions.status', $prescription->id) }}">
                    <span class="slider round"></span>
                </label>
            @else
                <div class="badge bg-{{ $prescription->status == 'used' ? 'success' : 'warning' }}">
                    {{ $prescription->status == 'used' ? __('Used') : __('Pending') }}
                </div>
            @endcan
        </td>
        <td class="table-single-content">{{ date('d M Y', strtotime($prescription->created_at)) }}</td>
        <td class="d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    @can('prescriptions-update')
                        <li>
                            <a href="#edit-prescription-modal" class="edit-prescription-btn" data-bs-toggle="modal"
                                data-url="{{ route('admin.prescriptions.update', $prescription->id) }}"
                                data-image="{{ asset($prescription->image) }}"
                                data-party-id="{{ $prescription->party_id }}"
                                data-notes="{{ $prescription->notes }}"
                                data-status="{{ $prescription->status }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.6874 3.83757L14.5124 3.01258C15.1959 2.32914 16.304 2.32914 16.9874 3.01258C17.6709 3.69603 17.6709 4.80411 16.9874 5.48756L16.1624 6.31255M13.6874 3.83757L8.138 9.387C7.71508 9.81 7.41505 10.3398 7.27 10.9201L6.66669 13.3333L9.07994 12.73C9.66019 12.585 10.19 12.2849 10.613 11.862L16.1624 6.31255M13.6874 3.83757L16.1624 6.31255" stroke="#4A4A52" stroke-width="1.25" stroke-linejoin="round"/>
                                <path d="M15.8333 11.2501C15.8333 13.9897 15.8332 15.3594 15.0767 16.2814C14.9382 16.4502 14.7834 16.6049 14.6146 16.7434C13.6927 17.5001 12.3228 17.5001 9.58325 17.5001H9.16667C6.02397 17.5001 4.45263 17.5001 3.47632 16.5237C2.50002 15.5475 2.5 13.9761 2.5 10.8334V10.4167C2.5 7.67718 2.5 6.30741 3.25662 5.38545C3.39514 5.21666 3.54992 5.06189 3.7187 4.92336C4.64066 4.16675 6.01043 4.16675 8.75 4.16675" stroke="#4A4A52" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ __('Edit') }}
                            </a>
                        </li>
                        @if (!$prescription->sale_id)
                        <li>
                            <a href="#" class="link-to-sale-btn"
                                data-url="{{ route('admin.prescriptions.link-to-sale', $prescription->id) }}"
                                data-prescription-id="{{ $prescription->id }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.16669 10.8333H15.8334M10 4.99998V15.8333" stroke="#4A4A52" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                {{ __('Link to Sale') }}
                            </a>
                        </li>
                        @endif
                    @endcan
                    @can('prescriptions-delete')
                        <li>
                            <a href="{{ route('admin.prescriptions.destroy', $prescription->id) }}" class="confirm-action"
                                data-method="DELETE">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
