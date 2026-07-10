$(document).ready(function () {
    var $sidebarPlan = $(".lg-sub-plan");
    var $subPlan = $(".sub-plan");
    var isActive = $(window).width() >= 1150;

    $(".side-bar, .section-container").toggleClass("active", isActive);

    if (isActive) {
        $sidebarPlan.hide();
        $subPlan.show();

        $(".side-bar-addon, .side-bar-addon-2, .side-bar-addon-3").hide();
    } else {
        $sidebarPlan.show();
        $subPlan.hide();

        $(".side-bar-addon, .side-bar-addon-2, .side-bar-addon-3").show();
    }
});

// get number only
function getNumericValue(value) {
    return parseFloat(value.replace(/[^0-9.-]+/g, "")) || 0;
}

// Update the cart list and call the callback once complete
function fetchUpdatedCart(callback) {
    let url = $("#purchase-cart").val();
    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#purchase_cart_list").html(response);
            if (typeof callback === "function") callback(); // Call the callback after updating the cart
        },
    });
}

//increase quantity
$(document).on("click", ".plus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    let newQty = currentQty + 1;
    $qtyInput.val(newQty);
    updateCartQuantity(rowId, newQty, updateRoute);
});

//decrease quantity
$(document).on("click", ".minus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    // Ensure quantity does not go below 1
    if (currentQty > 1) {
        let newQty = currentQty - 1;
        $qtyInput.val(newQty);
        updateCartQuantity(rowId, newQty, updateRoute);
    }
});

// Cart quantity input field change event
$(document).on("change", ".cart-qty", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newQty = parseFloat($(this).val());

    // Ensure quantity does not go below 1
    if (newQty >= 0) {
        updateCartQuantity(rowId, newQty, updateRoute);
    }
});

// Remove item from the cart
$(document).on("click", ".remove-btn", function (e) {
    e.preventDefault();
    var $row = $(this).closest("tr");
    var destroyRoute = $row.data("destroy_route");

    $.ajax({
        url: destroyRoute,
        type: "DELETE",
        success: function (response) {
            if (response.success) {
                // Item was successfully removed, fade out and remove the row from DOM
                $row.fadeOut(400, function () {
                    $(this).remove();
                });
                // Recalculate and update cart totals
                fetchUpdatedCart(calTotalAmount);
            } else {
                toastr.error(response.message || "Failed to remove item");
            }
        },
        error: function () {
            toastr.error("Error removing item from cart");
        },
    });
});

// Function to update cart item with the new quantity
function updateCartQuantity(rowId, newQty, updateRoute) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: {
            rowId: rowId,
            qty: newQty,
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // Re-fetch the cart and recalculate the total amount
            } else {
                toastr.error(response.message || "Failed to update quantity");
            }
        },
        error: function () {
            toastr.error("Error updating cart quantity");
        },
    });
}

// Clear the cart and then refresh the UI with updated values
function clearCart(cartType) {
    let route = $("#clear-cart").val();
    $.ajax({
        type: "POST",
        url: route,
        data: {
            type: cartType,
        },
        dataType: "json",
        success: function (response) {
            fetchUpdatedCart(calTotalAmount); // Call calTotalAmount after cart fetch completes
        },
        error: function () {
            console.error("There was an issue clearing the cart.");
        },
    });
}

/** Add to cart start **/

let selectedProduct = {};

$(document).on("click", "#single-product", function () {
    showProductModal($(this));
});

function autoOpenModal(id) {
    let element = $("#products-list").find(".single-product." + id);
    showProductModal(element);
}

function showProductModal(element) {
    selectedProduct = {
        product_id: element.data("product_id"),
        product_code: element.data("product_code"),
        product_unit_id: element.data("product_unit_id"),
        product_unit_name: element.data("product_unit_name"),
        product_image: element.data("product_image"),
        product_name: element.find(".product_name").text(),
        brand: element.data("brand"),
        stock: element.data("stock"),
        purchase_price: element.data("purchase_price"),
        sales_price: element.data("sales_price"),
        whole_sale_price: element.data("whole_sale_price"),
        dealer_price: element.data("dealer_price"),
        product_type: element.data("product_type"),
    };

    // Set modal display values
    $("#product_name").text(selectedProduct.product_name);
    $("#brand").text(selectedProduct.brand);
    $("#stock").text(selectedProduct.stock);
    $("#purchase_price").val(selectedProduct.purchase_price);
    $("#sales_price").val(selectedProduct.sales_price);
    $("#whole_sale_price").val(selectedProduct.whole_sale_price);
    $("#dealer_price").val(selectedProduct.dealer_price);
    $("#batch_no").val("");

    // show/hide variant field according to Product Type
    if (selectedProduct.product_type === "variant") {
        $("#variant_name").closest(".col-lg-6").removeClass("d-none");
        $("#variant_name").prop("disabled", false);
        loadVariantOptions(selectedProduct.product_id);
    } else {
        $("#variant_name").closest(".col-lg-6").addClass("d-none");
        $("#variant_name").prop("disabled", true);
        $("#variant_name").html('<option value="">Select Variant</option>');
    }

    // Read expire_date_type safely from hidden input
    const settingExpireType = $("#setting_expire_type").val() || "dmy";
    const $expireInput = $("#expire_date");

    if ($expireInput.length) {
        if (settingExpireType === "my") {
            $expireInput.attr("type", "month");
        } else {
            $expireInput.attr("type", "date");
        }
    }

    $("#product-modal").modal("show");
}

$(".product-filter").on("submit", function (e) {
    e.preventDefault();
});

let savingLoader = '<div class="spinner-border spinner-border-sm custom-text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
    $purchase_modal_reload = $("#purchase_modal");
    $purchase_modal_reload.initFormValidation(),
    // item modal action
    $("#purchase_modal").on("submit", function (e) {
        e.preventDefault();
        let t = $(this).find(".submit-btn"),
            a = t.html();
        let url = $(this).data("route");
        let quantity = parseFloat($("#product_qty").val());
        $purchase_modal_reload.valid() &&
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    type: "purchase",
                    id: selectedProduct.product_id,
                    name: selectedProduct.product_name,
                    quantity: quantity,
                    price: parseFloat($("#purchase_price").val()) || selectedProduct.purchase_price,
                    sales_price: parseFloat($("#sales_price").val()),
                    whole_sale_price: parseFloat($("#whole_sale_price").val()),
                    dealer_price: parseFloat($("#dealer_price").val()),
                    batch_no: $("#batch_no").val(),
                    expire_date: $("#expire_date").val(),
                    product_code: selectedProduct.product_code,
                    product_unit_id: selectedProduct.product_unit_id,
                    product_unit_name: selectedProduct.product_unit_name,
                    product_image: selectedProduct.product_image,
                    warehouse_id: $("#warehouse_id").val(),
                    variant_name: $("#variant_name").val()
                },
                beforeSend: function () {
                    t.html(savingLoader).attr("disabled", !0);
                },
                success: function (response) {
                    t.html(a).removeClass("disabled").attr("disabled", !1);

                    if (response.success) {
                        fetchUpdatedCart(calTotalAmount); // Update totals after cart fetch completes
                        $("#product-modal").modal("hide");
                        $("#product_qty").val("");
                        $("#variant_name").val("");
                        $("#warehouse_id").val("");
                        $("#expire_date").val("");
                    } else {
                        toastr.error(
                            response.message || "Failed to add product to cart."
                        );
                    }
                },
                error: function (xhr) {
                    toastr.error(
                        "An error occurred while adding product to cart."
                    );
                },
            });
    });

/** Add to cart End **/

// Function to load variant options
function loadVariantOptions(product_id) {
    let url = $("#get-product-variants").val() + '/' + product_id;
    $.ajax({
        url: url,
        type: "GET",
        success: function (res) {
            let options = '<option value="">Select Variant</option>';
            res.forEach(function (variant) {
                // Store batch_no as data attribute
                options += `<option value="${variant.variant_name}" data-batch="${variant.batch_no || ''}" data-expire_date="${variant.expire_date || ''}">
                               ${variant.variant_name} (${variant.batch_no || '-'})
                            </option>`;
            });
            $("#variant_name").html(options);

            // Clear field when product modal opens
            $("#batch_no").val('');
            $("#expire_date").val('');

        },
    });
}

// variant options on change event
$(document).on("change", "#variant_name", function () {
    let selectedOption = $(this).find(":selected");

    let batchNo = selectedOption.data("batch") || '';
    $("#batch_no").val(batchNo);

    let expireDate = selectedOption.data("expire_date") || '';

    // Adjust format if type=month
    const $expireInput = $("#expire_date");
    if ($expireInput.attr("type") === "month" && expireDate) {
        expireDate = expireDate.slice(0, 7); // yyyy-MM
    }

    $expireInput.val(expireDate);
});

// Trigger calculation whenever Discount, or Receive Amount fields change
$("#discount_amount, #receive_amount, #shipping_charge").on(
    "input",
    function () {
        calTotalAmount();
    }
);

// vat calculation
$(".vat_select").on("change", function () {
    let vatRate = parseFloat($(this).find(":selected").data("rate")) || 0;
    let subtotal = getNumericValue($("#sub_total").text()) || 0;

    let vatAmount = (subtotal * vatRate) / 100;

    $("#vat_amount").val(vatAmount.toFixed(2));
    calTotalAmount();
});

// discount calculation
$(".discount_type").on("change", function () {
    calTotalAmount();
});

// Function to calculate the total amount
function calTotalAmount() {
    let subtotal = 0;

    // Calculate subtotal from cart list
    $("#purchase_cart_list tr").each(function () {
        let cart_subtotal =
            getNumericValue($(this).find(".cart-subtotal").text()) || 0;
        subtotal += cart_subtotal;
    });

    $("#sub_total").text(currencyFormat(subtotal));

    // VAT
    let vat_rate =
        parseFloat($(".vat_select option:selected").data("rate")) || 0;
    let vat_amount = (subtotal * vat_rate) / 100;
    $("#vat_amount").val(vat_amount.toFixed(2));

    // Subtotal with VAT
    let subtotal_with_vat = subtotal + vat_amount;

    // Discount
    let discount_amount = getNumericValue($("#discount_amount").val()) || 0;
    let discount_type = $(".discount_type").val();

    if (discount_type == "percent") {
        discount_amount = (subtotal_with_vat * discount_amount) / 100;

        if (discount_amount > subtotal_with_vat) {
            toastr.error(
                "Discount cannot be more than 100% of the amount after VAT!"
            );
            discount_amount = subtotal_with_vat;
            $("#discount_amount").val(100);
        }
    } else {
        if (discount_amount > subtotal_with_vat) {
            toastr.error("Discount cannot be more than the amount after VAT!");
            discount_amount = subtotal_with_vat;
            $("#discount_amount").val(discount_amount);
        }
    }

    // Shipping Charge
    let shipping_charge = getNumericValue($("#shipping_charge").val()) || 0;

    // Total Amount (after VAT + shipping, then discount)
    let total_amount = subtotal_with_vat + shipping_charge - discount_amount;
    $("#total_amount").text(currencyFormat(total_amount));

    // Receive Amount
    let receive_amount = getNumericValue($("#receive_amount").val()) || 0;
    if (receive_amount < 0) {
        toastr.error("Receive amount cannot be less than 0!");
        receive_amount = 0;
        $("#receive_amount").val(receive_amount);
    }

    // Change Amount
    let change_amount =
        receive_amount > total_amount ? receive_amount - total_amount : 0;
    $("#change_amount").val(change_amount.toFixed(2));

    // Due Amount
    let due_amount =
        total_amount > receive_amount ? total_amount - receive_amount : 0;
    $("#due_amount").val(due_amount.toFixed(2));
}
calTotalAmount();

// Cancel btn action
$(".cancel-sale-btn").on("click", function (e) {
    e.preventDefault();
    clearCart("purchase");
});

$(".product-filter").on("input", ".search-input", function (e) {
    fetchProducts();
});

let autoOpenedForSearch = false; // Prevent repeated modal open per search
function fetchProducts() {
    var form = $("form.product-filter")[0];

    $.ajax({
        type: "POST",
        url: $(form).attr("action"),
        data: new FormData(form),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            // Show products
            $("#products-list").html(res.data);

            // Open modal if one product found and not opened yet
            const searchTerm = $("#purchase_product_search").val().trim();
            if (
                res.total_products &&
                res.product_id &&
                searchTerm !== "" &&
                !autoOpenedForSearch
            ) {
                autoOpenedForSearch = true;
                autoOpenModal(res.product_id);
                $("#purchase_product_search").val("");

                // Reset when modal closes and reload list
                $("#product-modal").one("hidden.bs.modal", function () {
                    autoOpenedForSearch = false;
                    fetchProducts();
                });
            }
        },
    });
}

$(document).on("click", ".category-content", function () {
    let url = $("#category-filter").val();
    const categoryId = $(this).data("id");
    $.ajax({
        type: "POST",
        url: url,
        data: { category_id: categoryId },
        success: function (response) {
            $(".product-list-container").html(response.data);
        },
    });
});

$(document).on("click", ".brand-search", function () {
    let url = $("#brand-filter").val();
    const brandId = $(this).data("id");

    $.ajax({
        type: "POST",
        url: url,
        data: { brand_id: brandId },
        success: function (response) {
            $(".product-list-container").html(response.data);
        },
    });
});

// Category Filter
$(".category-search").on("input", function (e) {
    e.preventDefault();
    // Get search query
    const search = $(this).val();
    const route = $(this).closest("form").data("route");

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: search,
        },
        success: function (response) {
            $("#category-data").html(response.categories);
        },
    });
});

// brand Filter
$(".brand-search").on("input", function (e) {
    e.preventDefault();

    // Get search query
    const search = $(this).val();

    const route = $(this).closest("form").data("route");

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: search,
        },
        success: function (response) {
            $("#brand-data").html(response.brands);
        },
    });
});

$(document).on("submit", "#categorySearchForm, #brandSearchForm", function (e) {
    e.preventDefault();
});

// When select brand or product
$(document).on("click", ".category-list, .brand-list", function () {
    const isCategory = $(this).hasClass("category-list");
    const filterType = isCategory ? "category_id" : "brand_id";
    const filterId = $(this).data("id");
    const route = $(this).data("route"); // product filter route

    const searchTerm = $("#purchase_product_search").val();

    $.ajax({
        type: "POST",
        url: route,
        data: {
            search: searchTerm,
            [filterType]: filterId, // Dynamically set category_id or brand_id
        },
        success: function (response) {
            $("#products-list").html(response.data);
            $("#category-list").html(response.categories);
            $("#brand-list").html(response.brands);
        },
    });
});

// Update cart
$(document).on("change", ".batch_no, .expire_date, .price", function () {
    let $row = $(this).closest("tr");
    let updateRoute = $row.data("update_route");

    // Get values
    let expire_date = $row.find(".expire_date").val();
    let priceVal = $row.find(".price").val();
    let batch_no = $row.find(".batch_no").val();

    // Parse and validate price
    let price = parseFloat(priceVal);
    if (isNaN(price) || price < 0) {
        toastr.error("Price cannot be negative or empty.");
        return;
    }

    // Optional: clean batch_no
    if (batch_no === null || batch_no === undefined) {
        batch_no = "";
    }

    // Prepare data
    let data = {
        batch_no: batch_no,
        expire_date: expire_date,
        price: price,
    };

    // Call update function
    updateCart(data, updateRoute);
});

// Function to update cart item with the new quantity
function updateCart(data, updateRoute) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: data,
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // Refresh the cart and recalculate totals
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
    });
}
