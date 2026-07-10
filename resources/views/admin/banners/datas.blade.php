@foreach ($banners as $banner)
    <tr class="table-content">
        <td class="w-60 checkbox table-single-content d-print-none">
            <label class="table-custom-checkbox">
                <input type="checkbox" name="ids[]" class="table-hidden-checkbox checkbox-item delete-checkbox-item multi-delete" value="{{ $banner->id }}">
                <span class="table-custom-checkmark custom-checkmark"></span>
            </label>
        </td>

        <td>{{ $banners->perPage() * ($banners->currentPage() - 1) + $loop->iteration }}</td>
        <td>
            <img class="table-img" height="35px" src="{{ asset($banner->imageUrl ?? 'assets/images/icons/empty-img.svg') }}" alt="img">
        </td>
        <td class="table-single-content">{{ Str::limit($banner->name, 25, '...')}}</td>
        <td class="text-center">
            @can('banners-update')
                <label class="switch">
                    <input type="checkbox" {{ $banner->status == 1 ? 'checked' : '' }} class="status"
                        data-url="{{ route('admin.banners.status', $banner->id) }}">
                    <span class="slider round"></span>
                </label>
            @else
                <div class="badge bg-{{ $banner->status == 1 ? 'success' : 'danger' }}">
                    {{ $banner->status == 1 ? 'Active' : 'Deactive' }}
                </div>
            @endcan
        </td>
        <td class="d-print-none">
            <div class="dropdown table-action">
                <button type="button" data-bs-toggle="dropdown">
                    <i class="far fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    @can('banners-update')
                        <li>
                            <a href="#edit-banner-modal" class="edit-banner-btn" data-bs-toggle="modal"
                                data-url="{{ route('admin.banners.update', $banner->id) }}"
                                data-image="{{ asset($banner->imageUrl) }}"
                                data-banner-name="{{ $banner->name }}"
                                data-status="{{ $banner->status }}"
                                >
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.6874 3.83757L14.5124 3.01258C15.1959 2.32914 16.304 2.32914 16.9874 3.01258C17.6709 3.69603 17.6709 4.80411 16.9874 5.48756L16.1624 6.31255M13.6874 3.83757L8.138 9.387C7.71508 9.81 7.41505 10.3398 7.27 10.9201L6.66669 13.3333L9.07994 12.73C9.66019 12.585 10.19 12.2849 10.613 11.862L16.1624 6.31255M13.6874 3.83757L16.1624 6.31255" stroke="#4A4A52" stroke-width="1.25" stroke-linejoin="round"/>
                                <path d="M15.8333 11.2501C15.8333 13.9897 15.8332 15.3594 15.0767 16.2814C14.9382 16.4502 14.7834 16.6049 14.6146 16.7434C13.6927 17.5001 12.3228 17.5001 9.58325 17.5001H9.16667C6.02397 17.5001 4.45263 17.5001 3.47632 16.5237C2.50002 15.5475 2.5 13.9761 2.5 10.8334V10.4167C2.5 7.67718 2.5 6.30741 3.25662 5.38545C3.39514 5.21666 3.54992 5.06189 3.7187 4.92336C4.64066 4.16675 6.01043 4.16675 8.75 4.16675" stroke="#4A4A52" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                                {{ __('Edit') }}
                            </a>
                        </li>
                    @endcan
                    @can('banners-delete')
                        <li>
                            <a href="{{ route('admin.banners.destroy', $banner->id) }}" class="confirm-action"
                                data-method="DELETE">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.5 4.375H14.5342C13.7833 4.375 13.7517 4.28 13.5458 3.66333L13.3775 3.1575C13.1217 2.39083 12.4075 1.875 11.5992 1.875H8.40083C7.59249 1.875 6.8775 2.39 6.6225 3.1575L6.45417 3.66333C6.24834 4.28083 6.21666 4.375 5.46583 4.375H2.5C2.155 4.375 1.875 4.655 1.875 5C1.875 5.345 2.155 5.625 2.5 5.625H3.58166L4.22084 15.2075C4.34417 17.0617 5.48084 18.125 7.33917 18.125H12.6617C14.5192 18.125 15.6558 17.0617 15.78 15.2075L16.4192 5.625H17.5C17.845 5.625 18.125 5.345 18.125 5C18.125 4.655 17.845 4.375 17.5 4.375ZM7.80833 3.5525C7.89416 3.29667 8.13166 3.125 8.40083 3.125H11.5992C11.8683 3.125 12.1067 3.29667 12.1917 3.5525L12.36 4.05833C12.3967 4.1675 12.4333 4.27333 12.4733 4.375H7.525C7.565 4.2725 7.60251 4.16666 7.63917 4.05833L7.80833 3.5525ZM14.5317 15.1242C14.4525 16.3183 13.8575 16.875 12.6608 16.875H7.33833C6.14167 16.875 5.5475 16.3192 5.4675 15.1242L4.83417 5.625H5.465C5.56917 5.625 5.65583 5.61417 5.74917 5.6075C5.7775 5.61167 5.80333 5.625 5.8325 5.625H14.1658C14.1958 5.625 14.2208 5.61167 14.2492 5.6075C14.3425 5.61417 14.4292 5.625 14.5333 5.625H15.1642L14.5317 15.1242ZM12.2917 9.16667V13.3333C12.2917 13.6783 12.0117 13.9583 11.6667 13.9583C11.3217 13.9583 11.0417 13.6783 11.0417 13.3333V9.16667C11.0417 8.82167 11.3217 8.54167 11.6667 8.54167C12.0117 8.54167 12.2917 8.82167 12.2917 9.16667ZM8.95833 9.16667V13.3333C8.95833 13.6783 8.67833 13.9583 8.33333 13.9583C7.98833 13.9583 7.70833 13.6783 7.70833 13.3333V9.16667C7.70833 8.82167 7.98833 8.54167 8.33333 8.54167C8.67833 8.54167 8.95833 8.82167 8.95833 9.16667Z" fill="#4A4A52"/>
                                    </svg>

                                {{ __('Delete') }}
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </td>
    </tr>
@endforeach
