{{-- Admin Layout — extends master with admin-specific CSS (code-split) --}}
@extends('layouts.master')

@push('admin_css')
    <link rel="stylesheet" href="{{ asset('css/admin-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-utilities.css') }}">
@endpush
