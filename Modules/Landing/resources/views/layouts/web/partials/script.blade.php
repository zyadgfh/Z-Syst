<script src="{{ asset('assets/web/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('assets/web/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/web/js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/web/js/lity.min.js') }}"></script>
<script src="{{ asset('assets/web/js/custom.js') }}"></script>
<script src="{{ asset('assets/web/js/slick.min.js') }}"></script>
<script src="{{ asset('assets/web/js/type.js') }}"></script>
<script src="{{ asset('assets/web/js/aos.js') }}"></script>
<script src="{{ asset('assets/web/js/toastr.min.js') }}"></script>
<script src="{{ asset('assets/web/js/typed.min.js') }}"></script>

<script src="{{ asset('assets/plugins/custom/notification.js') }}"></script>
<script src="{{ asset('assets/plugins/validation-setup/validation-setup.js') }}"></script>
<script src="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
<script src="{{ asset('assets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/form.js') }}"></script>
<script src="{{ asset('assets/js/custom-ajax.js') }}"></script>

@stack('js')

@if (Session::has('message'))
    <script>
        toastr.success("{{ Session::get('message') }}");
    </script>
@endif

@if (Session::has('error'))
    <script>
        toastr.error("{{ Session::get('error') }}");
    </script>
@endif

@if (Session::has('error'))
    <script>
        toastr.error("{{ Session::get('error') }}");
    </script>
@endif

<script>
    AOS.init({
    //   duration: 1000,
    //   delay: 200,
    //   offset: 120,
    //   once: true
    duration: 1000, // Animation duration in milliseconds
    once: false
    });
  </script>
