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

{{-- Clerk UserButton mounting --}}
@if(env('VITE_CLERK_PUBLISHABLE_KEY'))
<script>
    window.addEventListener('load', function () {
        function mountUserButton() {
            if (typeof Clerk === 'undefined' || !Clerk.loaded) return;
            var userButtonEl = document.getElementById('clerk-user-button');
            if (userButtonEl && !userButtonEl.hasChildNodes()) {
                Clerk.mountUserButton(userButtonEl);
            }
        }
        var interval = setInterval(function () {
            if (typeof Clerk !== 'undefined' && Clerk.loaded) {
                clearInterval(interval);
                mountUserButton();
            }
        }, 100);
        setTimeout(function () { clearInterval(interval); }, 5000);
    });
</script>
@endif
