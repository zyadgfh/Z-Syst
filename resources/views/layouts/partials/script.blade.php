<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}"></script>
{{-- jquery confirm --}}
<script src="{{asset('assets/plugins/jquery-confirm/jquery-confirm.min.js')}}"></script>
{{-- jquery validation --}}
<script src="{{asset('assets/plugins/jquery-validation/jquery.validate.min.js')}}"></script>
{{-- Custom --}}
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}"></script>
{{-- Status --}}
<script src="{{ asset('assets/js/custom-ajax.js') }}"></script>
{{-- Toaster --}}
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/custom/custom.js') }}"></script>
<script src="{{ asset('assets/js/apexcharts.min.js') }}"></script>


@stack('js')

@stack('modal-view')

{{-- Toaster Message --}}
@if(Session::has('message'))
    <script>
        toastr.success( "{{ Session::get('message') }}");
    </script>
@endif
@if(Session::has('error'))
    <script>
        toastr.error( "{{ Session::get('error') }}");
    </script>
@endif
@if($errors->any())
<script>
    toastr.warning('Error some occurs!');
</script>
@endif
{{-- Apple design layer (admin): theme, live strip, POS keys, offline queue --}}
<script src="{{ asset('assets/js/ap-theme.js') }}" defer></script>
<script src="{{ asset('assets/js/ap-live.js') }}" defer></script>
<script src="{{ asset('assets/js/pos-keys.js') }}" defer></script>
<script src="{{ asset('assets/js/pos-offline.js') }}" defer></script>
@if (auth()->check() && auth()->user()?->business_id)
<meta name="api-token" content="{{ request()->session()->get('api_token') ?? '' }}">
@endif
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset("sw.js") }}').catch(function () {});
        });
    }
</script>
