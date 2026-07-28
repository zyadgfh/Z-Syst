@extends('landing::layouts.master')

@section('title')
    {{ __('Comments') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="table-header p-16">
                        <h4 class="mt-2">{{ __('Comment List') }}</h4>
                    </div>
                    <div class="table-top-form p-16">
                        <form action="{{ route('admin.comments.filter', $blog->id) }}" method="post" class="filter-form"
                        table="#comments-data">
                            @csrf
                            <div class="table-top-left d-flex gap-3 margin-l-16 ">
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option value="10">{{ __('Show- 10') }}</option>
                                        <option value="25">{{ __('Show- 25') }}</option>
                                        <option value="50">{{ __('Show- 50') }}</option>
                                        <option value="100">{{ __('Show- 100') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <div class="table-search position-relative">
                                    <input class="form-control searchInput" type="text" name="search"
                                        placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="responsive-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="striped-th">{{ __('SL') }}.</th>
                                    <th class="striped-th">{{ __('Name') }}</th>
                                    <th class="striped-th">{{ __('Email') }}</th>
                                    <th class="striped-th">{{ __('Comment') }}</th>
                                    <th class="print-d-none striped-th">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="comments-data">
                                @include('landing::admin.blogs.comment.datas')
                            </tbody>
                        </table>
                    </div>
                    <nav>
                        <ul class="pagination">
                            <li class="page-item">{{ $comments->links('pagination::bootstrap-5') }}</li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
@endpush

