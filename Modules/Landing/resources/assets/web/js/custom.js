window.addEventListener("scroll", function () {
    var header = document.querySelector(".header-section.home-header");
    var scrollPosition = window.scrollY;

    if (scrollPosition > 5) {
        header.classList.add("bg-0d0d16");
    } else {
        header.classList.remove("bg-0d0d16");
    }
});

/* ----------------------------------------------- */
/* Slick Slider */
/* ----------------------------------------------- */

$(document).ready(function () {
    $(".slick-slider-section .app-slide").slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        rtl: false,
        autoplay: true,
        autoplaySpeed: 1500,
        infinite: true,
        pauseOnFocus: false,
        responsive: [
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
});
$(document).ready(function () {
    $(".customer-slider-section .row").slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: false,
        rtl: false,
        autoplay: true,
        autoplaySpeed: 1500,
        infinite: true,
        pauseOnFocus: false,
        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
});

/* ----------------------------------------------- */
/* Top Scroll */
/* ----------------------------------------------- */
$(document).ready(function () {
    $("#scrollToTopButton").click(function () {
        $("html, body").animate(
            {
                scrollTop: 0,
            },
            "slow"
        );
    });
});
/* ----------------------------------------------- */
/* product-details Slide Image */
/* ----------------------------------------------- */
$(document).ready(function () {
    $(".product-details-small-image").click(function () {
        var newImageSrc = $(this).data("image-src");
        $("#product-details-big-image").attr("src", newImageSrc);
        $(".product-details-small-image")
            .parent()
            .removeClass("product-details-custom-b");
        $(this).parent().addClass("product-details-custom-b");
    });
});

/* ----------------------------------------------- */
/* About Page Read More Button */
/* ----------------------------------------------- */
$(document).ready(function () {
    $("#readMoreButton").click(function () {
        if ($("#moreText").css("display") === "none") {
            $("#moreText").css("display", "block");
            $("#readMoreButton").hide();
            // $("#readMoreButton").text("Read Less");
        } else {
            $("#moreText").css("display", "none");
        }
    });
});
/* ----------------------------------------------- */
/* Blogs page tags active button */
/* ----------------------------------------------- */

/** Tag Start*/
$(document).on("click", ".tags-btn", function () {
    // Highlight selected tag
    $(".tags-btn")
        .removeClass("blogs-tag-btn-selected")
        .addClass("blogs-tag-btn-unselected");
    $(this)
        .removeClass("blogs-tag-btn-unselected")
        .addClass("blogs-tag-btn-selected");

    // Fetch tag and route
    let selectedTag = $(this).data("tag");
    let routeUrl = $(this).data("route");

    // Fetch blogs dynamically
    $.ajax({
        url: routeUrl,
        type: "GET",
        data: { tag: selectedTag },
        success: function (response) {
            // Update blog list
            $("#blogs-container").empty();
            response.blogs.data.forEach((blog) => {
                const createdAt = new Date(blog.created_at).toLocaleString(
                    "en-US",
                    {
                        month: "long", //month name (January)
                        day: "2-digit", // Day with leading zero
                        year: "numeric", // Full year
                    }
                );

                   // Limit description by characters
                   let description = blog.descriptions.length > 80
                   ? blog.descriptions.substring(0, 80) + "..."
                   : blog.descriptions;

                $("#blogs-container").append(`
        <div class="col-lg-6 pb-4 blog-item">
            <div class="blog-shadow rounded">
                <div class="text-center blog-image p-3">
                    <img src="${blog.image}" class="w-100 h-100 object-fit-cover rounded-1" alt="${blog.title}" />
                </div>
                <div class="p-3 pt-0">
                    <div class="d-flex align-items-center mb-2">
                        <img src="/frontend/assets/images/icons/clock.svg" alt="" />
                        <p class="ms-1 mb-0">${createdAt}</p>
                    </div>
                    <h6>${blog.title}</h6>
                    <p>${description}</p>
                    <a href="/blogs/${blog.slug}" class="custom-clr-primary">Read More <span class="font-monospace">></span></a>
                </div>
            </div>
        </div>
    `);
            });
        },
        error: function (error) {
            console.error(error);
        },
    });
});
/** Tag End*/

(function ($) {
    "use strict";

    var $swiperSelector = $(".creative-interface-slider");
    $swiperSelector.each(function (index) {
        var $this = $(this);
        $this.addClass("swiper-sliderone-" + index);

        var dragSize = $this.data("drag-size") ? $this.data("drag-size") : 50;
        var freeMode = $this.data("free-mode")
            ? $this.data("free-mode")
            : false;
        var loop = $this.data("loop") ? $this.data("loop") : true;
        var slidesDesktop = $this.data("slides-desktop")
            ? $this.data("slides-desktop")
            : 4;
        var slideslaptop = $this.data("slides-laptop")
            ? $this.data("slides-laptop")
            : 4;
        var slidesTablet = $this.data("slides-tablet")
            ? $this.data("slides-tablet")
            : 4;
        var slidesMobile = $this.data("slides-mobile")
            ? $this.data("slides-mobile")
            : 2.2;
        var spaceBetween = $this.data("space-between")
            ? $this.data("space-between")
            : 20;

        var swiper7 = new Swiper(".swiper-sliderone-" + index, {
            direction: "horizontal",
            loop: loop,
            freeMode: freeMode,
            spaceBetween: spaceBetween,
            breakpoints: {
                1920: {
                    slidesPerView: slidesDesktop,
                },
                1024: {
                    slidesPerView: slideslaptop,
                },
                767: {
                    slidesPerView: slidesTablet,
                },
                320: {
                    slidesPerView: slidesMobile,
                },
            },
            navigation: {
                nextEl: ".next",
                prevEl: ".prev",
            },
            scrollbar: {
                el: ".swiper-scrollbar",
                draggable: true,
                dragSize: dragSize,
            },
        });
    });

    var $swiperSelectorOne = $(".premium-product-slide");
    $swiperSelectorOne.each(function (index) {
        var $this = $(this);
        $this.addClass("swiper-slidertwo-" + index);

        var dragSize = $this.data("drag-size") ? $this.data("drag-size") : 50;
        var freeMode = $this.data("free-mode")
            ? $this.data("free-mode")
            : false;
        var loop = $this.data("loop") ? $this.data("loop") : true;
        var slidesDesktop = $this.data("slides-desktop")
            ? $this.data("slides-desktop")
            : 3;
        var slideslaptop = $this.data("slides-laptop")
            ? $this.data("slides-laptop")
            : 3;
        var slidesTablet = $this.data("slides-tablet")
            ? $this.data("slides-tablet")
            : 2.5;
        var slidesMobile = $this.data("slides-mobile")
            ? $this.data("slides-mobile")
            : 1.2;
        var spaceBetween = $this.data("space-between")
            ? $this.data("space-between")
            : 20;

        var swiper5 = new Swiper(".swiper-slidertwo-" + index, {
            direction: "horizontal",
            loop: loop,
            freeMode: freeMode,
            spaceBetween: spaceBetween,
            breakpoints: {
                1920: {
                    slidesPerView: slidesDesktop,
                },
                1024: {
                    slidesPerView: slideslaptop,
                },
                767: {
                    slidesPerView: slidesTablet,
                },
                320: {
                    slidesPerView: slidesMobile,
                },
            },
            navigation: {
                nextEl: ".next1",
                prevEl: ".prev1",
            },
            scrollbar: {
                el: ".swiper-scrollbar",
                draggable: true,
                dragSize: dragSize,
            },
        });
    });

    var $swiperSelectorTwo = $(".testimonial-slider");
    $swiperSelectorTwo.each(function (index) {
        var $this = $(this);
        $this.addClass("swiper-sliderthree-" + index);

        var dragSize = $this.data("drag-size") ? $this.data("drag-size") : 50;
        var freeMode = $this.data("free-mode")
            ? $this.data("free-mode")
            : false;
        var loop = $this.data("loop") ? $this.data("loop") : true;
        var slidesDesktop = $this.data("slides-desktop")
            ? $this.data("slides-desktop")
            : 3;
        var slideslaptop = $this.data("slides-laptop")
            ? $this.data("slides-laptop")
            : 3;
        var slidesTablet = $this.data("slides-tablet")
            ? $this.data("slides-tablet")
            : 1.5;
        var slidesMobile = $this.data("slides-mobile")
            ? $this.data("slides-mobile")
            : 1;
        var spaceBetween = $this.data("space-between")
            ? $this.data("space-between")
            : 20;

        var swiper2 = new Swiper(".swiper-sliderthree-" + index, {
            direction: "horizontal",
            loop: loop,
            freeMode: freeMode,
            spaceBetween: spaceBetween,
            breakpoints: {
                1920: {
                    slidesPerView: slidesDesktop,
                },
                1024: {
                    slidesPerView: slideslaptop,
                },
                767: {
                    slidesPerView: slidesTablet,
                },
                320: {
                    slidesPerView: slidesMobile,
                },
            },
            navigation: {
                nextEl: ".next2",
                prevEl: ".prev2",
            },
            scrollbar: {
                el: ".swiper-scrollbar",
                draggable: true,
                dragSize: dragSize,
            },
        });
    });

    var swiper = new Swiper(".admin-panel-slider", {
        slidesPerView: 1,
        spaceBetween: 30,
        autoplay: false,
        loop: true,
        speed: 1400,
        autoplay: {
            delay: 6000,
        },
        navigation: {
            nextEl: ".next5",
            prevEl: ".prev5",
        },
    });
})(jQuery);

// animation counter start
const animateCounter = (counter) => {
    const target = parseFloat($(counter).data("target"));
    if (isNaN(target)) return;

    let count = 0;
    const speed = target / 100;

    const updateCounter = () => {
        if (count < target) {
            count += speed;
            $(counter).text(
                target % 1 === 0 ? Math.ceil(count) : count.toFixed(1)
            );
            requestAnimationFrame(updateCounter);
        } else {
            $(counter).text(target);
        }
    };

    updateCounter();
};

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.5 }
);

$(".counter").each(function () {
    observer.observe(this);
});

// animation counter End

// See more features start
$(document).ready(function () {
    $(".features-list").each(function () {
        var $list = $(this);
        var $features = $list.find(".feature-item");

        if ($features.length > 8) {
            $features.slice(8).addClass("d-none");
        }
    });

    $(document).on("click", ".see-more-btn", function () {
        var $button = $(this);
        var $featuresList = $button
            .closest(".card-body")
            .find(".features-list");
        $featuresList.find(".feature-item.d-none").removeClass("d-none");
        $button
            .text("See Less")
            .removeClass("see-more-btn")
            .addClass("see-less-btn");
    });

    $(document).on("click", ".see-less-btn", function () {
        var $button = $(this);
        var $featuresList = $button
            .closest(".card-body")
            .find(".features-list");
        $featuresList.find(".feature-item").slice(8).addClass("d-none");
        $button
            .text("See More")
            .removeClass("see-less-btn")
            .addClass("see-more-btn");
    });
});
// See more features End

document.addEventListener("DOMContentLoaded", function () {
    var mySwiper = new Swiper(".swiper-container", {
        loop: true,
        centeredSlides: true,
        spaceBetween: 20,
        grabCursor: true,
        autoplay: {
            delay: 3000,
        },
        breakpoints: {
            0: {
                slidesPerView: 1,
                spaceBetween: 5,
            },
            480: {
                slidesPerView: 1,
                spaceBetween: 10,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 15,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 20,
            },
            1280: {
                slidesPerView: 3,
                spaceBetween: 25,
            },
            1440: {
                slidesPerView: 3,
                spaceBetween: 10,
            },
        },
    });
});

$(document).ready(function () {
    $(".print-window").on("click", function () {
        window.print();
    });
});
