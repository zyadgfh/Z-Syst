@extends('layouts.master')

@section('title')
    {{ __('Edit Goods Received Note') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Edit Goods Received Note') }</h4>
            <form action="{{ route('admin.grn.update', $grn) }}" method="POST" class="row">
    @csrf
    @method('PUT')
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Received date') }</label>
                    <input type="date" name="received_date" value="{{ old('received_date', $grn ? $grn->received_date : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Location') }</label>
                    <input type="text" name="location" value="{{ old('location', $grn ? $grn->location : '') }}" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">{ __('Notes') }</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $grn ? $grn->notes : '') }}</textarea>
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
