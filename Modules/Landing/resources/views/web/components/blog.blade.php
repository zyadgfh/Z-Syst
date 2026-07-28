<div class="blogs-section">
    <div class="container">
        <div class="row pb-3">
            <div class="col-xl-8">
                <div class="row" id="blogs-container">
                    @foreach ($blogs as $index => $blog)
                        <div data-aos="zoom-in-up" data-aos-delay="{{ $index * 300 }}" class="col-lg-6 pb-4">
                            <div class="blog-shadow rounded-16">
                                <div class="text-center blog-image pb-3">
                                    <img src="{{ asset($blog->image) }}" alt="product-image"
                                        class="w-100 h-100 object-fit-cover  blog-img-1" />
                                </div>
                                <div class="p-3 pt-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_1051_22674)">
                                                <path
                                                    d="M7.99967 14.6654C11.6816 14.6654 14.6663 11.6806 14.6663 7.9987C14.6663 4.3168 11.6816 1.33203 7.99967 1.33203C4.31778 1.33203 1.33301 4.3168 1.33301 7.9987C1.33301 11.6806 4.31778 14.6654 7.99967 14.6654Z"
                                                    stroke="#7B7C84" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path d="M8 4V8L10.6667 9.33333" stroke="#7B7C84" stroke-width="1.5"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_1051_22674">
                                                    <rect width="16" height="16" fill="white" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                        <p class="ms-1 mb-0 blog-data">{{ formatted_date($blog->updated_at) }}</p>
                                    </div>
                                    <h6 class="h6-line-clamp blog-title">{{ Str::words($blog->title ?? '', 12, '...') }}</h6>
                                    <p> {{ Str::words($blog->descriptions ?? '', 13, '...') }}</p>
                                    <a href="{{ route('blogs.show', $blog->slug) }}"
                                        class="custom-clr-primary">{{ Str::words($page_data['headings']['blog_btn_text'] ?? '', 3, '...') }}
                                        <span class="font-monospace">></span></a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-xl-4">
                @foreach ($recent_blogs as $index => $blog)
                    <div data-aos="zoom-in-up" data-aos-delay="{{ $index * 300 }}" class="blog-shadow rounded-16 mb-4">
                        <div class="d-flex align-items-center ">
                            <img src="{{ asset($blog->image) }}"
                                class="object-fit-cover rounded home-blog-small-image blog-img-1" alt="..." />
                            <div class="mx-3">
                                <div class="d-flex align-items-center">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_1051_22674)">
                                            <path
                                                d="M7.99967 14.6654C11.6816 14.6654 14.6663 11.6806 14.6663 7.9987C14.6663 4.3168 11.6816 1.33203 7.99967 1.33203C4.31778 1.33203 1.33301 4.3168 1.33301 7.9987C1.33301 11.6806 4.31778 14.6654 7.99967 14.6654Z"
                                                stroke="#7B7C84" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M8 4V8L10.6667 9.33333" stroke="#7B7C84" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_1051_22674">
                                                <rect width="16" height="16" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <p class="ms-1 mb-0 blog-data">{{ formatted_date($blog->updated_at) }}</p>
                                </div>
                                <p class="p-2nd-line-clamp my-2">
                                    <strong class="blog-title">{{ $blog->title }}</strong>
                                </p>
                                <a href="{{ route('blogs.show', $blog->slug) }}"
                                    class="custom-clr-primary">{{ Str::words($page_data['headings']['blog_btn_text'] ?? '', 3, '...') }}
                                    <span class="font-monospace">></span></a>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if (Request::routeIs('blogs.index'))
                    <h6>{{ __('Tags') }}</h6>
                    <div class="tags-btns">
                        @foreach ($blogs as $blog)
                            @foreach ($blog['tags'] ?? [] as $tag)
                                <a href="javascript:void(0);"
                                    class="btn btn-secondary ps-custom-btn mb-1 tags-btn blogs-tag-btn-selected"
                                    data-tag="{{ $tag }}" data-route="{{ route('frontend.tag.filter') }}">
                                    {{ $tag }}
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>


