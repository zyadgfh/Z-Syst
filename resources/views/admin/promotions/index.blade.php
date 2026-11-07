@extends('layouts.master')
@section('title') {{ __('العروض والخصومات') }} @endsection
@section('main_content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5>🎁 {{ __('عرض جديد') }}</h5>
                <form method="POST" action="{{ route('admin.promotions.store') }}">
                    @csrf
                    <input name="name" class="form-control mb-2" placeholder="{{ __('الاسم — اشترِ 2 واحصل على 1') }}" required>
                    <select name="type" class="form-select mb-2" required>
                        <option value="percent">{{ __('خصم %') }}</option>
                        <option value="fixed">{{ __('خصم ثابت') }}</option>
                        <option value="qty_bonus">{{ __('اشترِ X وخد Y') }}</option>
                        <option value="near_expiry">{{ __('قرب انتهاء الصلاحية') }}</option>
                    </select>
                    <div class="row g-2 mb-2">
                        <div class="col"><input name="value" type="number" step="0.01" class="form-control" placeholder="{{ __('القيمة') }}"></div>
                        <div class="col"><input name="min_qty" type="number" class="form-control" placeholder="{{ __('أقل كمية') }}" value="1"></div>
                        <div class="col"><input name="bonus_qty" type="number" class="form-control" placeholder="{{ __('كمية مجانية') }}" value="0"></div>
                    </div>
                    <select name="applies_to" class="form-select mb-2" onchange="document.getElementById('promo-scope-{{ \Illuminate\Support\Str::uuid() }}').hidden = false">
                        <option value="all">{{ __('كل الأصناف') }}</option>
                        <option value="product">{{ __('صنف محدد') }}</option>
                        <option value="category">{{ __('تصنيف محدد') }}</option>
                    </select>
                    <select name="product_id" class="form-select mb-2">
                        <option value="">{{ __('— صنف —') }}</option>
                        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                    <select name="category_id" class="form-select mb-2">
                        <option value="">{{ __('— تصنيف —') }}</option>
                        @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <input name="coupon_code" class="form-control mb-2" placeholder="{{ __('كوبون (اختياري) — SALE10') }}">
                    <div class="row g-2 mb-3">
                        <div class="col"><input name="starts_at" type="date" class="form-control"></div>
                        <div class="col"><input name="ends_at" type="date" class="form-control"></div>
                    </div>
                    <button class="btn btn-success w-100">{{ __('إنشاء') }}</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card"><div class="card-body table-responsive">
                <table class="table table-sm align-middle">
                    <tr><th>{{ __('الاسم') }}</th><th>{{ __('النوع') }}</th><th>{{ __('القيمة') }}</th><th>{{ __('النطاق') }}</th><th>{{ __('كوبون') }}</th><th>{{ __('الاستخدام') }}</th><th>{{ __('الحالة') }}</th><th></th></tr>
                    @forelse($promotions as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->type }}</td>
                        <td>{{ $p->value }} {{ $p->bonus_qty ? "+{$p->bonus_qty} مجانًا" : '' }}</td>
                        <td>{{ $p->applies_to }}</td>
                        <td><code>{{ $p->coupon_code ?? '—' }}</code></td>
                        <td>{{ $p->used_count }}{{ $p->max_uses ? '/'.$p->max_uses : '' }}</td>
                        <td><span class="badge {{ $p->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $p->is_active ? __('مفعّل') : __('موقوف') }}</span></td>
                        <td class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.promotions.toggle', $p) }}">@csrf
                                <button class="btn btn-sm btn-outline-secondary">{{ $p->is_active ? __('إيقاف') : __('تفعيل') }}</button></form>
                            <form method="POST" action="{{ route('admin.promotions.destroy', $p) }}">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">✕</button></form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted">{{ __('لا عروض — الكاشير سيطبقها تلقائيًا عند توفر الشروط') }}</td></tr>
                    @endforelse
                </table>
                {{ $promotions->links() }}
            </div></div>
        </div>
    </div>
</div>
@endsection
