@extends('layouts.master')

@section('title')
    {{ __('New Stock Transfer') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('New Stock Transfer') }</h4>
            <form action="{{ route('admin.stock-transfers.store') }}" method="POST" class="row">
    @csrf
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('From warehouse') }</label>
                    <select name="from_warehouse_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($warehouses as $item)
                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('To warehouse') }</label>
                    <select name="to_warehouse_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($warehouses as $item)
                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Quantity') }</label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">{ __('Notes') }</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{ __('Create') }</button>
                    <a href="javascript:history.back()" class="btn btn-light">{ __('Cancel') }</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
