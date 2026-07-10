<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}"></script>
<script src="{{ asset('assets/js/math.js') }}"></script>
{{-- jquery confirm --}}
<script src="{{asset('assets/plugins/jquery-confirm/jquery-confirm.min.js')}}"></script>
{{-- jquery validation --}}
<script src="{{asset('assets/plugins/jquery-validation/jquery.validate.min.js')}}"></script>
{{-- Custom --}}
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}?v={{ time() }}"></script>
{{-- Status --}}
<script src="{{ asset('assets/js/custom-ajax.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/slick.min.js') }}"></script>
{{-- Toaster --}}
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/custom/custom.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/custom/tagify.js') }}"></script>

{{-- choice --}}
<script src="{{ asset('assets/js/choices.min.js') }}"></script>

<script src="{{ asset('assets/js/custom/custome-dropdown.js') }}"></script>

@php
    $currency = business_currency();
@endphp
{{-- Hidden input fields to store currency details --}}
<input type="hidden" id="currency_symbol" value="{{ $currency->symbol }}">
<input type="hidden" id="currency_position" value="{{ $currency->position }}">
<input type="hidden" id="currency_code" value="{{ $currency->code }}">

@stack('js')

@stack('modal-view')

{{-- Toaster Message --}}
@if(Session::has('message'))
    <script>
        toastr.success( "{{ Session::get('message') }}");
    </script>
@endif
@if(Session::has('warning'))
    <script>
        toastr.warning( "{{ Session::get('warning') }}");
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
