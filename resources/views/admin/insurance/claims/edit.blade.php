@extends('layouts.master')

@section('title')
    {{ __('Edit Insurance Claim') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Edit Insurance Claim') }</h4>
            <form action="{{ route('admin.insurance.claims.update', $claim) }}" method="POST" class="row">
    @csrf
    @method('PUT')
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Claim number') }</label>
                    <input type="text" name="claim_number" value="{{ old('claim_number', $claim ? $claim->claim_number : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Service date') }</label>
                    <input type="date" name="service_date" value="{{ old('service_date', $claim ? $claim->service_date : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Submission date') }</label>
                    <input type="date" name="submission_date" value="{{ old('submission_date', $claim ? $claim->submission_date : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Total amount') }</label>
                    <input type="number" name="total_amount" value="{{ old('total_amount', $claim ? $claim->total_amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Covered amount') }</label>
                    <input type="number" name="covered_amount" value="{{ old('covered_amount', $claim ? $claim->covered_amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Patient responsibility') }</label>
                    <input type="number" name="patient_responsibility" value="{{ old('patient_responsibility', $claim ? $claim->patient_responsibility : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Approved amount') }</label>
                    <input type="number" name="approved_amount" value="{{ old('approved_amount', $claim ? $claim->approved_amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Paid amount') }</label>
                    <input type="number" name="paid_amount" value="{{ old('paid_amount', $claim ? $claim->paid_amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Rejected amount') }</label>
                    <input type="number" name="rejected_amount" value="{{ old('rejected_amount', $claim ? $claim->rejected_amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Status') }</label>
                    <input type="text" name="status" value="{{ old('status', $claim ? $claim->status : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Rejection reason') }</label>
                    <input type="text" name="rejection_reason" value="{{ old('rejection_reason', $claim ? $claim->rejection_reason : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Settlement date') }</label>
                    <input type="date" name="settlement_date" value="{{ old('settlement_date', $claim ? $claim->settlement_date : '') }}" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{ __('Save changes') }</button>
                    <a href="javascript:history.back()" class="btn btn-light">{ __('Cancel') }</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
