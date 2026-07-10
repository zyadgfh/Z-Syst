"use strict";

let allProducts = [];

// Asset path helper
function assetPath(path) {
    let baseUrl = $("#asset_base_url").val() || "";
    return baseUrl.replace(/\/$/, "") + "/" + path.replace(/^\/+/, "");
}

// renders product dropdown list
function populateProducts(products) {
    const $dropdownList = $("#dropdownList");
    $dropdownList.empty();

    if (products.length === 0) {
        $dropdownList.append(
            '<div class="product-option-item">No products available</div>'
        );
        return;
    }

    const cartRoute = $("#cart-store-url").val();

    $.each(products, function (index, product) {
        const imageUrl = assetPath(
            product.productPicture ?? "assets/images/products/box.svg"
        );
        let html = "";

        // if multiple stocks
        if (product.stocks && product.stocks.length > 1) {
            html += `<div class="product-option-item single-product"
                data-product_id="${product.id}"
                data-route="${cartRoute}"
                data-product_name="${product.productName}"
                data-product_code="${product.productCode}"
                data-product_unit_id="${product.unit_id}"
                data-product_unit_name="${product.unit?.unitName ?? ""}"
                data-product_image="${product.productPicture}">
                <div class="product-left">
                    <img src="${imageUrl}" alt="">
                    <div class="product-text">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="product-title">${
                                product.productName
                            }</div>
                            <p>Code : ${product.productCode}</p>
                        </div>`;

            product.stocks.forEach((stock) => {
                html += `<div class="d-flex align-items-center justify-content-between w-100 multi-items add-batch-item"
                        data-product_stock_id="${stock.id}"
                        data-product_id="${product.id}"
                        data-product_expire_date="${stock.expire_date ?? ""}"
                        data-product_name="${product.productName}"
                        data-product_code="${product.productCode}"
                        data-default_price="${stock.productSalePrice}"
                        data-product_unit_id="${product.unit_id}"
                        data-product_unit_name="${product.unit?.unitName ?? ""}"
                        data-purchase_price="${stock.productPurchasePrice}"
                        data-product_image="${product.productPicture}"
                        data-route="${cartRoute}"
                        data-batch_no="${stock.batch_no ?? ''}"
                        >

                        <div class="product-des">
                            Batch: ${stock.batch_no ?? "N/A"} ${
                    product.color ? ", Color: " + product.color : ""
                }
                            <span class="product-in-stock">In Stock: ${
                                stock.productStock
                            }</span>
                        </div>
                        <div class="product-price product_price">${currencyFormat(
                            stock.productSalePrice
                        )}</div>
                    </div>`;
            });

            html += `</div></div></div>`;
        } else {
            const singleStock =
                Array.isArray(product.stocks) && product.stocks.length > 0
                    ? product.stocks[0]
                    : {};

            html += `<div class="product-option-item single-product ${
                product.id
            } add-batch-item"
                data-product_stock_id="${singleStock.id ?? ""}"
                data-product_id="${product.id}"
                data-default_price="${singleStock.productSalePrice ?? 0}"
                data-product_name="${product.productName}"
                data-product_code="${product.productCode}"
                data-product_unit_id="${product.unit_id}"
                data-product_unit_name="${product.unit?.unitName ?? ""}"
                data-purchase_price="${singleStock.productPurchasePrice ?? 0}"
                data-product_image="${product.productPicture}"
                data-product_expire_date="${singleStock.expire_date ?? ""}"
                data-route="${cartRoute}"
                data-batch_no="${singleStock.batch_no ?? ''}">

                <div class="product-left">
                    <img src="${imageUrl}" alt="">
                    <div class="product-text">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="product-title">${
                                product.productName
                            }</div>
                            <p>Code : ${product.productCode}</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="product-des">
                                Batch: ${singleStock.batch_no ?? "N/A"} ${
                product.color ? ", Color: " + product.color : ""
            }
                                <span class="product-in-stock">In Stock: ${
                                    Array.isArray(product.stocks)
                                        ? product.stocks.reduce(
                                              (sum, stock) =>
                                                  sum + stock.productStock,
                                              0
                                          )
                                        : 0
                                }</span>
                            </div>
                            <div class="product-price product_price">${currencyFormat(
                                singleStock.productSalePrice ?? 0
                            )}</div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        $dropdownList.append(html);
    });
}

// load all products initially
const allProductsUrl = $("#all-products").val();
if (allProductsUrl) {
    $.get(allProductsUrl, function (products) {
        allProducts = products; // store globally
        populateProducts(allProducts);
    });
}

// Filter products based on branch & warehouse
function filterProducts() {
    const branchId = $("#from_branch").val();
    const warehouseId = $("#from_warehouse").val();

    const filtered = allProducts
        .map((product) => {
            if (!product.stocks || product.stocks.length === 0) return null;

            // filter stocks by selected branch & warehouse
            const filteredStocks = product.stocks.filter((stock) => {
                const branchMatch = !branchId || stock.branch_id == branchId;
                const warehouseMatch = !warehouseId || stock.warehouse_id == warehouseId;
                return branchMatch && warehouseMatch && stock.productStock > 0;
            });

            if (filteredStocks.length === 0) return null;

            return {
                ...product, // copy product data, keep only filtered stocks
                stocks: filteredStocks,
            };
        })
        .filter(Boolean); // remove nulls

    populateProducts(filtered);
}

// toggle product dropdown visibility
const $dropdown = $("#dropdownList");
const $searchContainer = $("#searchContainer");
const $productSearch = $("#productSearch");
const $arrow = $("#arrow");

$("#productDropdown .product-selected").on("click", function (e) {
    e.stopPropagation();
    $dropdown.toggle();
    $searchContainer.toggleClass("hidden");
    $arrow.toggleClass("product-rotate");
});

// Close dropdown if clicked outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#productDropdown").length) {
        $dropdown.hide();
        $searchContainer.addClass("hidden");
        $arrow.removeClass("product-rotate");
    }
});

// product search also respects filter
$productSearch.on("keyup", function () {
    const searchTerm = $(this).val().toLowerCase();
    $("#dropdownList .product-option-item").each(function () {
        const name = String($(this).data("product_name") || "").toLowerCase();
        const code = String($(this).data("product_code") || "").toLowerCase();
        $(this).toggle(name.includes(searchTerm) || code.includes(searchTerm));
    });
});

// Add to cart on batch click
$("#dropdownList").on("click", ".add-batch-item", function (e) {
    e.stopPropagation();

    const $item = $(this);
    $dropdown.hide();
    $searchContainer.addClass("hidden");
    $arrow.removeClass("product-rotate");

    let stockId = $item.data("product_stock_id");
    let productId = $item.data("product_id");

    let name = $item.data("product_name");
    let code = $item.data("product_code");
    let price = parseFloat($item.data("default_price") || 0);
    let batchNo = $item.data("batch_no") || "";
    let image = $item.data("product_image") || assetPath("assets/images/products/box.svg");

    // If row exists increment qty
    let existingRow = $("#product-row-" + stockId);
    if (existingRow.length) {
        let qtyInput = existingRow.find(".dynamic-qty");
        qtyInput.val((parseInt(qtyInput.val()) || 1) + 1).trigger("input");
        return;
    }

    let rowHtml = `
        <tr id="product-row-${stockId}">
            <td><img src="${assetPath(image)}" width="40"></td>
            <td>${name}</td>
            <td>${code}</td>
            <td>${batchNo}</td>
            <td class="text-start">
                <div class="d-flex align-items-center justify-content-center">
                    <button type="button" class="incre-decre subtract-btn btn btn-sm btn-outline-secondary"><i class="fas fa-minus"></i></button>
                    <input type="number" name="products[${stockId}][quantity]" class="custom-number-input dynamic-qty form-control form-control-sm mx-1 text-center" value="1" min="1" step="1" style="width: 60px;">
                    <button type="button" class="incre-decre adding-btn btn btn-sm btn-outline-secondary"><i class="fas fa-plus"></i></button>
                </div>
            </td>
            <td>
                <input type="number" name="products[${stockId}][unit_price]" class="form-control unit-price text-center" value="${price}" min="0">
            </td>
            <td><input type="number" name="products[${stockId}][tax]" class="form-control tax text-center" value="0" min="0"></td>
            <td><input type="number" name="products[${stockId}][discount]" class="form-control discount text-center" value="0" min="0"></td>
            <td class="sub-total text-center">${price.toFixed(2)}</td>
            <td>
                <button type="button" class="x-btn remove-btn" data-id="${stockId}">
                  <svg width="25" height="24" viewBox="0 0 25 24" fill="none">
                      <path d="M18.5 6L6.5 18" stroke="#E13F3D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M6.5 6L18.5 18" stroke="#E13F3D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
            </td>
            <input type="hidden" name="products[${stockId}][product_id]" value="${productId}">
        </tr>
    `;
    $("#product-list").append(rowHtml);
    updateTotals();
});

// Cart qty, price, discount, tax change
$("#product-list").on(
    "input",
    ".dynamic-qty, .unit-price, .discount, .tax",
    function () {
        let $row = $(this).closest("tr");
        let qty = parseFloat($row.find(".dynamic-qty").val()) || 0;
        let price = parseFloat($row.find(".unit-price").val()) || 0;
        let discount = parseFloat($row.find(".discount").val()) || 0;
        let tax = parseFloat($row.find(".tax").val()) || 0;

        let subtotal = qty * price + tax - discount;
        $row.find(".sub-total").text(subtotal.toFixed(2));
        updateTotals();
    }
);

// increment / decrement buttons
$("#product-list").on("click", ".adding-btn", function () {
    let $row = $(this).closest("tr");
    let $qty = $row.find(".dynamic-qty");
    $qty.val((parseInt($qty.val()) || 0) + 1).trigger("input");
});
$("#product-list").on("click", ".subtract-btn", function () {
    let $row = $(this).closest("tr");
    let $qty = $row.find(".dynamic-qty");
    let current = parseInt($qty.val()) || 1;
    if (current > 1) $qty.val(current - 1).trigger("input");
});

// remove row
$("#product-list").on("click", ".remove-btn", function () {
    $(this).closest("tr").remove();
    updateTotals();
});

// update totals function
function updateTotals() {
    let subTotal = 0,
        totalDiscount = 0,
        totalTax = 0,
        grandTotal = 0;

    $("#product-list tr").each(function () {
        let qty = parseFloat($(this).find(".dynamic-qty").val()) || 0;
        let price = parseFloat($(this).find(".unit-price").val()) || 0;
        let discount = parseFloat($(this).find(".discount").val()) || 0;
        let tax = parseFloat($(this).find(".tax").val()) || 0;

        let lineBase = qty * price;
        let lineWithTax = lineBase + tax;
        let lineSubtotal = lineWithTax - discount;

        $(this).find(".sub-total").text(lineSubtotal.toFixed(2));

        subTotal += lineBase;
        totalTax += tax;
        totalDiscount += discount;
        grandTotal += lineSubtotal;
    });

    let shipping = parseFloat($("#shipping_amount").val()) || 0;
    grandTotal += shipping;

    $("#total_amount").text(subTotal.toFixed(2));
    $("#tax_amount").text(totalTax.toFixed(2));
    $("#discount_amount").text(totalDiscount.toFixed(2));
    $("#grand_total_amount").text(grandTotal.toFixed(2));
}

// Trigger totals update on shipping change
$("#shipping_amount").on("input", function () {
    updateTotals();
});

const allWarehousesUrl = $("#branch_wise_warehouses").val();

function loadWarehouses(branchId, warehouseSelect) {
    if (!allWarehousesUrl) return;

    $.ajax({
        url: allWarehousesUrl,
        type: "GET",
        data: { branch_id: branchId },
        success: function (warehouses) {
            let options = '<option value="">Select one</option>';

            if (warehouses.length > 0) {
                $.each(warehouses, function (i, wh) {
                    options += `<option value="${wh.id}">${wh.name}</option>`;
                });
            } else {
                options += `<option value="">No warehouse available</option>`;
            }

            $(warehouseSelect).html(options);
        },
        error: function () {
            $(warehouseSelect).html('<option value="">Error loading warehouses</option>');
        }
    });
}

// Load all warehouses initially
loadWarehouses("", "#from_warehouse");
loadWarehouses("", "#to_warehouse");

// When "from_branch" changes
$("#from_branch").on("change", function () {
    let branchId = $(this).val();

    // Clear cart and update totals
    $("#product-list").empty();
    updateTotals();

    // Reload "from_warehouse" options based on selected branch
    loadWarehouses(branchId, "#from_warehouse");

    // Filter products based on new branch & warehouse
    filterProducts();
});

// When "to_branch" changes
$("#to_branch").on("change", function () {
    let branchId = $(this).val();
    let hasActiveBranch = $("#hasActiveBranch").val() == "1";

    // Only reload "to_warehouse" if no active branch
    if (!hasActiveBranch) {
        loadWarehouses(branchId, "#to_warehouse");
    }
});

// When "from_warehouse" changes
$("#from_warehouse").on("change", function () {
    // Clear cart and update totals
    $("#product-list").empty();
    updateTotals();

    // Filter products based on new warehouse
    filterProducts();
});



