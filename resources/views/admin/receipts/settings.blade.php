@extends('layouts.master')

@section('title')
    {{ __('Receipt Settings') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Receipt Settings') }}</h4>
                        <a href="{{ route('admin.receipts.index') }}" class="add-order-btn rounded-2 active">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Receipts') }}
                        </a>
                    </div>

                    <div class="p-4">
                        <form action="{{ route('admin.receipts.update-settings') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="receipt_header" class="form-label">{{ __('Receipt Header') }}</label>
                                    <input type="text" class="form-control" id="receipt_header" name="receipt_header" value="{{ $settings->receipt_header ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="receipt_footer" class="form-label">{{ __('Receipt Footer') }}</label>
                                    <input type="text" class="form-control" id="receipt_footer" name="receipt_footer" value="{{ $settings->receipt_footer ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label">{{ __('Tax Number') }}</label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number" value="{{ $settings->tax_number ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">{{ __('Phone') }}</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $settings->phone ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $settings->email ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">{{ __('Address') }}</label>
                                    <textarea class="form-control" id="address" name="address" rows="2">{{ $settings->address ?? '' }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="show_qr" class="form-label">{{ __('Show QR Code') }}</label>
                                    <select class="form-select" id="show_qr" name="show_qr">
                                        <option value="1" {{ ($settings->show_qr ?? 1) ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                        <option value="0" {{ !($settings->show_qr ?? 1) ? 'selected' : '' }}>{{ __('No') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="show_barcode" class="form-label">{{ __('Show Barcode') }}</label>
                                    <select class="form-select" id="show_barcode" name="show_barcode">
                                        <option value="1" {{ ($settings->show_barcode ?? 0) ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                        <option value="0" {{ !($settings->show_barcode ?? 0) ? 'selected' : '' }}>{{ __('No') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="paper_size" class="form-label">{{ __('Paper Size') }}</label>
                                    <select class="form-select" id="paper_size" name="paper_size">
                                        <option value="a4" {{ ($settings->paper_size ?? 'a4') === 'a4' ? 'selected' : '' }}>{{ __('A4') }}</option>
                                        <option value="a5" {{ ($settings->paper_size ?? 'a4') === 'a5' ? 'selected' : '' }}>{{ __('A5') }}</option>
                                        <option value="letter" {{ ($settings->paper_size ?? 'a4') === 'letter' ? 'selected' : '' }}>{{ __('Letter') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
