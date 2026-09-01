@extends('layouts.master')

@section('title')
    {{ __('Manual Payment Settings') }}
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/summernote-lite.css') }}">
@endpush

@section('main_content')
    <div class="erp-table-section system-settings">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys mb-4">
                    <div class="tab-content order-summary-tab mt-0">
                        <div class="tab-pane fade active show" id="add-new-petty" role="tabpanel">

                            <div class="table-header">
                                <div class="card-bodys">
                                    <div class="table-header border-0 p-16">
                                        <h4>{{ __('Manual Payment Settings') }}</h4>
                                        <p class="text-muted">{{ __('Payment gateways have been removed. Only manual payment processing is available for supplier payments.') }}</p>
                                    </div>
                                </div>
                            </div> <br>

                            <div class="row">
                                <div class="col-sm-11">
                                    <div class="order-form-section p-16">
                                        <div class="row">

                                            <div class="col-12 col-sm-12 col-md-4 mb-4">

                                                <ul class="nav nav-pills flex-column flex-column shadow w-280 p-2">
                                                    @foreach ($gateways as $gateway)
                                                        @if ($gateway->is_manual)
                                                        <li class="nav-item">
                                                            <a href="#{{ str_replace(' ', '-', $gateway->name) }}"
                                                                id="{{ str_replace(' ', '-', $gateway->name) }}-tab4"
                                                                @class([
                                                                    'add-report-btn nav-link',
                                                                    'active' => $loop->first ? true : false,
                                                                ])
                                                                data-bs-toggle="tab">{{ $gateway->name }}</a>
                                                        </li>
                                                        @endif
                                                    @endforeach
                                                </ul>

                                            </div>
                                            <div class="col-12 col-sm-12 col-md-8">
                                                <div class="cards border-0 shadow">
                                                    <div class="card-body">
                                                        <div class="tab-content no-padding">
                                                            @foreach ($gateways as $gateway)
                                                                @if ($gateway->is_manual)
                                                                <div @class([
                                                                    'tab-pane fade',
                                                                    'show active' => $loop->first ? true : false,
                                                                ])
                                                                    id="{{ str_replace(' ', '-', $gateway->name) }}">
                                                                    <form
                                                                        action="{{ route('admin.gateways.update', $gateway->id) }}"
                                                                        method="post" class="ajaxform">
                                                                        @csrf
                                                                        @method('put')

                                                                        <div class="row">
                                                                            <div class="col-11 align-self-center mb-2">
                                                                                <label class="img-label">{{ __('PAYMENT IMAGE') }}</label>
                                                                                <input type="file" name="image"
                                                                                    class="form-control">
                                                                            </div>

                                                                            <div class="col-1 align-self-center mb-2">
                                                                                <img src="{{ asset($gateway->image) }}"
                                                                                    class="img-fluid" alt="">
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <label>{{ __('PAYMENT NAME') }}</label>
                                                                                <input type="text" name="name"
                                                                                    value="{{ $gateway->name }}" required
                                                                                    class="form-control">
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <label>{{ __('Payment Charge') }}</label>
                                                                                <input type="number" step="any"
                                                                                    name="charge"
                                                                                    value="{{ $gateway->charge }}"
                                                                                    class="form-control">
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <label>{{ __('Payment Currency') }}</label>
                                                                                <div class="gpt-up-down-arrow position-relative">
                                                                                <select class="form-control" required
                                                                                    name="currency_id">
                                                                                    @foreach ($currencies as $currency)
                                                                                        <option @selected($gateway->currency_id == $currency->id)
                                                                                            value="{{ $currency->id }}">
                                                                                            {{ $currency->name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <span></span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <label>{{ __('Status') }}</label>
                                                                                <div class="gpt-up-down-arrow position-relative">
                                                                                <select class="form-control" required
                                                                                    name="status">
                                                                                    <option @selected($gateway->status == 1)
                                                                                        value="1">{{ __('Active') }}
                                                                                    </option>
                                                                                    <option @selected($gateway->status == 0)
                                                                                        value="0">{{ __('Deactive') }}
                                                                                    </option>
                                                                                </select>
                                                                                <span></span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <label>{{ __('Accept Image') }}</label>
                                                                                <div class="gpt-up-down-arrow position-relative">
                                                                                <select class="form-control" required
                                                                                    name="accept_img">
                                                                                    <option @selected($gateway->accept_img == 1)
                                                                                        value="1">{{ __('Yes') }}
                                                                                    </option>
                                                                                    <option @selected($gateway->accept_img == 0)
                                                                                        value="0">{{ __('No') }}
                                                                                    </option>
                                                                                </select>
                                                                                <span></span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12 mb-2">
                                                                                <div class="manual-rows">
                                                                                    @foreach ($gateway->manual_data['label'] ?? [] as $key => $row)
                                                                                        <div class="row row-items">
                                                                                            <div class="col-sm-5">
                                                                                                <label
                                                                                                    for="">{{ __('Label') }}</label>
                                                                                                <input type="text"
                                                                                                    name="manual_data[label][]"
                                                                                                    value="{{ $row }}"
                                                                                                    class="form-control"
                                                                                                    required
                                                                                                    placeholder="{{ __('Enter label name') }}">
                                                                                            </div>
                                                                                            <div class="col-sm-5">
                                                                                                <label
                                                                                                    for="">{{ __('Select Required/Optionl') }}</label>
                                                                                                <div class="gpt-up-down-arrow position-relative">
                                                                                                <select class="form-control"
                                                                                                    required
                                                                                                    name="manual_data[is_required][]">
                                                                                                    <option
                                                                                                        @selected($gateway->manual_data['is_required'][$key] == 1)
                                                                                                        value="1">
                                                                                                        {{ __('Required') }}
                                                                                                    </option>
                                                                                                    <option
                                                                                                        @selected($gateway->manual_data['is_required'][$key] == 0)
                                                                                                        value="0">
                                                                                                        {{ __('Optional') }}
                                                                                                    </option>
                                                                                                </select>
                                                                                                <span></span>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="col-sm-2">
                                                                                                <label>&nbsp;</label>
                                                                                                <button type="button"
                                                                                                    class="btn btn-danger remove_item mt-1">{{ __('Remove') }}</button>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12">
                                                                                <button type="button"
                                                                                    class="btn btn-primary add_item mb-2">{{ __('Add New Field') }}</button>
                                                                            </div>

                                                                            <div class="col-12 mt-3">
                                                                                <button type="submit"
                                                                                    class="btn btn-success">{{ __('Update') }}</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.remove_item', function() {
                $(this).closest('.row-items').remove();
            });

            $('.add_item').click(function() {
                let newField = `
                    <div class="row row-items">
                        <div class="col-sm-5">
                            <label for="">{{ __('Label') }}</label>
                            <input type="text" name="manual_data[label][]" class="form-control" required placeholder="{{ __('Enter label name') }}">
                        </div>
                        <div class="col-sm-5">
                            <label for="">{{ __('Select Required/Optionl') }}</label>
                            <div class="gpt-up-down-arrow position-relative">
                                <select class="form-control" required name="manual_data[is_required][]">
                                    <option value="1">{{ __('Required') }}</option>
                                    <option value="0">{{ __('Optional') }}</option>
                                </select>
                                <span></span>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger remove_item mt-1">{{ __('Remove') }}</button>
                        </div>
                    </div>
                `;
                $('.manual-rows').append(newField);
            });
        });
    </script>
@endpush