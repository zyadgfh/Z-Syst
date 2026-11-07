@extends('layouts.master')

@section('title')
    {{ __('Edit Language') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Edit Language') }</h4>
            <form action="{{ route('admin.website-setting.languages.update', $language) }}" method="POST" class="row">
    @csrf
    @method('PUT')
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Name') }</label>
                    <input type="text" name="name" value="{{ old('name', $language ? $language->name : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Icon') }</label>
                    <input type="text" name="icon" value="{{ old('icon', $language ? $language->icon : '') }}" class="form-control">
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
