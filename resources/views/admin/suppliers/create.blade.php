@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __("Add Supplier") }}</h2>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.suppliers.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name">{{ __("Company Name") }} *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" required>
                                    @error('company_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_person">{{ __("Contact Person") }} *</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                                    @error('contact_person')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">{{ __("Email") }} *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    @error('email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">{{ __("Phone") }} *</label>
                                    <input type="text" class="form-control" id="phone" name="phone" required>
                                    @error('phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">{{ __("Address") }}</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_id">{{ __("Tax ID") }}</label>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id">
                                </div>
                                <div class="form-group">
                                    <label for="license_number">{{ __("License Number") }}</label>
                                    <input type="text" class="form-control" id="license_number" name="license_number">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="payment_terms">{{ __("Payment Terms") }}</label>
                                    <select class="form-control" id="payment_terms" name="payment_terms">
                                        <option value="net_30">{{ __("Net 30") }}</option>
                                        <option value="net_45">{{ __("Net 45") }}</option>
                                        <option value="net_60">{{ __("Net 60") }}</option>
                                        <option value="cod">{{ __("COD") }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="credit_limit">{{ __("Credit Limit") }}</label>
                                    <input type="number" class="form-control" id="credit_limit" name="credit_limit" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">{{ __("Status") }}</label>
                                    <select class="form-control" id="is_active" name="is_active">
                                        <option value="1">{{ __("Active") }}</option>
                                        <option value="0">{{ __("Inactive") }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_start">{{ __("Contract Start") }}</label>
                                    <input type="date" class="form-control" id="contract_start" name="contract_start">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_end">{{ __("Contract End") }}</label>
                                    <input type="date" class="form-control" id="contract_end" name="contract_end">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">{{ __("Notes") }}</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __("Save Supplier") }}
                            </button>
                            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
