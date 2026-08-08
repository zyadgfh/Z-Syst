@extends('layouts.master')

@section('title')
    {{ __('Tenant Payment Settings') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-header d-flex justify-content-between align-items-center mb-4">
                        <h4>{{ __('Tenant Payment Settings') }}</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            {{ __('Add New Setting') }}
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Tenant') }}</th>
                                    <th>{{ __('Gateway') }}</th>
                                    <th>{{ __('Merchant Details') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($settings as $setting)
                                <tr>
                                    <td>{{ $setting->tenant->name ?? 'N/A' }}</td>
                                    <td>{{ $setting->gateway->name }}</td>
                                    <td>
                                        @if($setting->merchant_phone)
                                            <small>{{ __('Phone') }}: {{ $setting->merchant_phone }}</small><br>
                                        @endif
                                        @if($setting->merchant_name)
                                            <small>{{ __('Name') }}: {{ $setting->merchant_name }}</small><br>
                                        @endif
                                        @if($setting->bank_name)
                                            <small>{{ __('Bank') }}: {{ $setting->bank_name }}</small><br>
                                        @endif
                                        @if($setting->branch_name)
                                            <small>{{ __('Branch') }}: {{ $setting->branch_name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($setting->is_active)
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info edit-btn" data-id="{{ $setting->id }}">
                                            {{ __('Edit') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="{{ $setting->id }}">
                                            {{ __('Delete') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning toggle-btn" data-id="{{ $setting->id }}">
                                            {{ __('Toggle') }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add New Payment Setting') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Tenant') }} <span class="text-danger">*</span></label>
                                <select name="tenant_id" class="form-control" required>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Gateway') }} <span class="text-danger">*</span></label>
                                <select name="gateway_id" class="form-control" required id="gatewaySelect">
                                    @foreach ($gateways as $gateway)
                                        <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Status') }}</label>
                                <select name="is_active" class="form-control">
                                    <option value="1">{{ __('Active') }}</option>
                                    <option value="0">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Branch ID (Optional)') }}</label>
                                <input type="text" name="branch_id" class="form-control" placeholder="{{ __('For branch-specific settings') }}">
                            </div>
                        </div>

                        <!-- Egyptian Gateway Specific Fields -->
                        <div id="gatewayFields" class="row">
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Vodafone Cash,Orange Cash">
                                <label>{{ __('Merchant Phone') }}</label>
                                <input type="text" name="merchant_phone" class="form-control" placeholder="{{ __('Enter merchant phone number') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Vodafone Cash,Orange Cash,InstaPay,Bank Card,Fawry,Cash Payment">
                                <label>{{ __('Merchant Name') }}</label>
                                <input type="text" name="merchant_name" class="form-control" placeholder="{{ __('Enter merchant name') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Fawry">
                                <label>{{ __('Merchant Code') }}</label>
                                <input type="text" name="merchant_code" class="form-control" placeholder="{{ __('Enter Fawry merchant code') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Fawry">
                                <label>{{ __('Merchant Key') }}</label>
                                <input type="text" name="merchant_key" class="form-control" placeholder="{{ __('Enter Fawry merchant key') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="InstaPay">
                                <label>{{ __('InstaPay ID') }}</label>
                                <input type="text" name="merchant_instapay_id" class="form-control" placeholder="{{ __('Enter InstaPay ID') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Bank Card">
                                <label>{{ __('Bank Name') }}</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="{{ __('Enter bank name') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Bank Card">
                                <label>{{ __('Account Number') }}</label>
                                <input type="text" name="account_number" class="form-control" placeholder="{{ __('Enter account number') }}">
                            </div>
                            <div class="col-md-6 mb-3 gateway-field" data-gateways="Cash Payment">
                                <label>{{ __('Branch Name') }}</label>
                                <input type="text" name="branch_name" class="form-control" placeholder="{{ __('Enter branch name') }}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="saveBtn">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Show/hide gateway-specific fields
            $('#gatewaySelect').on('change', function() {
                const selectedGateway = $(this).find('option:selected').text();
                $('.gateway-field').hide();
                
                $('.gateway-field').each(function() {
                    const gateways = $(this).data('gateways').split(',');
                    if (gateways.includes(selectedGateway)) {
                        $(this).show();
                    }
                });
            });

            // Trigger change on load
            $('#gatewaySelect').trigger('change');

            // Save new setting
            $('#saveBtn').on('click', function() {
                $.ajax({
                    url: '{{ route('admin.tenant-payment-settings.store') }}',
                    method: 'POST',
                    data: $('#createForm').serialize(),
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Error occurred');
                    }
                });
            });

            // Delete setting
            $('.delete-btn').on('click', function() {
                const id = $(this).data('id');
                if (confirm('{{ __('Are you sure you want to delete this setting?') }}')) {
                    $.ajax({
                        url: '{{ route('admin.tenant-payment-settings.destroy', ':id') }}'.replace(':id', id),
                        method: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(response) {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        }
                    });
                }
            });

            // Toggle status
            $('.toggle-btn').on('click', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '{{ route('admin.tenant-payment-settings.toggle', ':id') }}'.replace(':id', id),
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    }
                });
            });
        });
    </script>
@endpush