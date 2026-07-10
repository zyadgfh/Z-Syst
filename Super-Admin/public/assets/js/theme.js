(function ($) {
    "use strict";

    sideManu();

    function sideManu() {
        let manuStor = $(".side-bar").html();

        $(".side-bar").html("<div class='overlay'></div>" + manuStor);
        $(".sidebar-opner").on("click ", function () {
            $(".side-bar, .section-container").toggleClass("active");
        });
        $(".side-bar .close-btn, .side-bar .overlay").on("click ", function () {
            $(".side-bar, .section-container").toggleClass("active");
        });

        $("li>ul").toggleClass("dropdown-menu");

        let animationSpeed = 300;

        let subMenuSelector = ".dropdown-menu";

        $(".side-bar-manu > ul").on("click", ".dropdown a", function (e) {
            let $this = $(this);
            let checkElement = $this.next();

            if (
                checkElement.is(subMenuSelector) &&
                checkElement.is(":visible")
            ) {
                checkElement.slideUp(animationSpeed, function () {
                    checkElement.removeClass("menu-open");
                });
                checkElement.parent("li").removeClass("active");
            }

            //If the menu is not visible
            else if (
                checkElement.is(subMenuSelector) &&
                !checkElement.is(":visible")
            ) {
                //Get the parent menu
                let parent = $this.parents("ul").first();
                //Close all open menus within the parent
                let ul = parent.find("ul:visible").slideUp(animationSpeed);
                //Remove the menu-open class from the parent
                ul.removeClass("menu-open");
                //Get the parent li
                let parent_li = $this.parent("li");

                //Open the target menu and add the menu-open class
                checkElement.slideDown(animationSpeed, function () {
                    //Add the class active to the parent li
                    checkElement.addClass("menu-open");
                    parent.find("li.active").removeClass("active");
                    parent_li.addClass("active");
                });
            }
            //if this isn't a link, prevent the page from being redirected
            if (checkElement.is(subMenuSelector)) {
                e.preventDefault();
            }
        });

        // show sidebar in previous menu
        var sidebar = $(".side-bar");

        // Restore scroll position on page load
        var savedScroll = localStorage.getItem("sidebar-scroll");
        if (savedScroll !== null) {
            sidebar.scrollTop(savedScroll);
        }

        // Save scroll position before leaving the page
        $(window).on("beforeunload", function () {
            localStorage.setItem("sidebar-scroll", sidebar.scrollTop());
        });
    }

    // photo upload preview
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $(".image-preview").attr("src", e.target.result);
                $(".image-preview").hide();
                $(".image-preview").fadeIn(650);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#add-profile").on("change", function () {
        readURL(this);
        $(".image-preview-icon").addClass("d-none");
    });

    $("#feature-btn").on("click", function (e) {
        e.preventDefault();

        let value = $(".add-feature").val();
        let featureCount = $(".feature-list").children().length;

        if (value !== "") {
            $(".feature-list").append(`
            <div class="col-lg-6 mt-4 remove-list">
                <div class="feature-wrp">
                    <div class="form-control d-flex justify-content-between align-items-center">
                        <input name="features[features_${featureCount}][]" required class="border-none" type="text" value="${value}">
                        <label class="switch m-0">
                            <input type="checkbox" checked value="1" name="features[features_${featureCount}][]">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <button type="button" class="remove-one d-none"><i class="fal fa-times"></i></button>
                </div>
            </div>
            `);
            $(".add-feature").val("");
        }
    });
})(jQuery);

document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.querySelector(".menu-opener");
    const sidebarPlan = document.querySelector(".lg-sub-plan");
    const subPlan = document.querySelector(".sub-plan");
    const sidebarAddOn = document.querySelector(".side-bar-addon");
    const sidebarAddOn2 = document.querySelector(".side-bar-addon-2");
    const sidebarAddOn3 = document.querySelector(".side-bar-addon-3");

    toggleBtn.addEventListener("click", function () {
        if (sidebarPlan.style.display === "none") {
            sidebarPlan.style.display = "block";
            subPlan.style.display = "none";
            sidebarAddOn.style.display = "block";
            sidebarAddOn2.style.display = "block";
            sidebarAddOn3.style.display = "block";
        } else {
            sidebarPlan.style.display = "none";
            subPlan.style.display = "block";
            sidebarAddOn.style.display = "none";
            sidebarAddOn2.style.display = "none";
            sidebarAddOn3.style.display = "none";
        }
    });
});

document.querySelector(".sidebar-opner").addEventListener("click", function () {
    const sidebar = document.querySelector(".side-bar-addon");
    if (
        sidebar.style.display === "none" ||
        getComputedStyle(sidebar).display === "none"
    ) {
        sidebar.style.display = "block";
    } else {
        sidebar.style.display = "none";
    }
});

$(document).on("click", "#openUserSignupTab", function (e) {
    e.preventDefault();
    var $otpTabTrigger = $("#otp-tab");
    if ($otpTabTrigger.length) {
        var tab = new bootstrap.Tab($otpTabTrigger[0]);
        tab.show();
    }
});

function cartSelectVariant(selectedBox) {
    const allBoxes = document.querySelectorAll(".cart-variant-box");
    allBoxes.forEach((box) => box.classList.remove("cart-active"));
    selectedBox.classList.add("cart-active");

    const radios = document.querySelectorAll('input[name="variant"]');
    radios.forEach((radio) => (radio.checked = false));
    selectedBox.querySelector('input[type="radio"]').checked = true;
}
