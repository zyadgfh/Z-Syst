<section class="terms-policy-section">
    <div class="container">
        <h2 class="langing-section-title pb-4">
            {{ Str::words($page_data['headings']['term_of_service_title'] ?? '', 5, '...') }}
        </h2>
        <div>
            {!! $term_condition->value['description'] ?? '' !!}
        </div>
    </div>
</section>
