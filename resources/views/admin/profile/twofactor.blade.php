@extends('layouts.master')
@section('title') {{ __('التحقق بخطوتين') }} @endsection
@section('main_content')
<div class="container-fluid" style="max-width: 560px" x-data="twofa('{{ $secret }}')">
    <div class="card"><div class="card-body">
        <h5>🔐 {{ __('التحقق بخطوتين (TOTP)') }}</h5>
        @if($enabled)
            <div class="alert alert-success">{{ __('مفعّل — كل تسجيل دخول سيطلب كود من التطبيق') }}</div>
            <form @submit.prevent="disable()" class="d-flex gap-2">
                <input type="password" class="form-control" x-model="password" placeholder="{{ __('كلمة المرور للإلغاء') }}">
                <button class="btn btn-outline-danger">{{ __('إلغاء') }}</button>
            </form>
        @else
            <ol class="small">
                <li>{{ __('حمّل Google Authenticator أو Authy') }}</li>
                <li>{{ __('اضغط «توليد المفتاح» وأدخله يدويًا (أو افتح رابط otpauth)') }}</li>
                <li>{{ __('أدخل الكود المكوّن من 6 أرقام') }}</li>
            </ol>
            <button class="btn btn-primary mb-2" @click="enable()" x-show="!secret">{{ __('توليد المفتاح') }}</button>
            <template x-if="secret">
                <div>
                    <div class="alert alert-light border"><code x-text="secret" style="font-size: 1.1em; letter-spacing: 2px"></code></div>
                    <p class="small text-break"><a :href="uri" x-text="uri"></a></p>
                    <form @submit.prevent="verify()" class="d-flex gap-2">
                        <input class="form-control" x-model="code" maxlength="6" placeholder="123456" inputmode="numeric">
                        <button class="btn btn-success">{{ __('تفعيل') }}</button>
                    </form>
                </div>
            </template>
        @endif
        <p class="msg small mt-2" x-text="msg"></p>
    </div></div>
</div>
<script>
function twofa(initialSecret) {
    return {
        secret: initialSecret, uri: '', code: '', password: '', msg: '',
        async enable() {
            const r = await fetch('{{ route('admin.two-factor.enable') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
            const j = await r.json();
            this.secret = j.secret; this.uri = j.uri;
        },
        async verify() {
            const r = await fetch('{{ route('admin.two-factor.verify') }}', {method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({code: this.code})});
            const j = await r.json();
            this.msg = j.message || '';
            if (j.success) setTimeout(() => location.reload(), 800);
        },
        async disable() {
            const r = await fetch('{{ route('admin.two-factor.disable') }}', {method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({password: this.password})});
            const j = await r.json();
            this.msg = j.message || '';
            if (j.success) setTimeout(() => location.reload(), 800);
        }
    }
}
</script>
@endsection
