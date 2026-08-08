<section class="footer-container d-print-none">
    <footer class="footer-content container-fluid d-flex align-items-center justify-content-center justify-content-sm-between flex-wrap py-3 mt-4 ms-0">
        <p class="mb-0 me-3 text-secondary">
            {{ Str::words(get_option('general')['copy_right'] ?? '', 10, '...') }}
        </p>
        <p class="mb-0 text-secondary">
            {{ Str::words(get_option('general')['admin_footer_text'] ?? '', 5, '...') }}
            <i class="fas fa-heart text-danger"></i>
            <a class="text-ancor" href="{{ get_option('general')['admin_footer_link'] ?? '' }}" target="_blank">
                {{ Str::words(get_option('general')['admin_footer_link_text'] ?? '', 5, '...') }}
            </a>
        </p>
    </footer>
</section>
