// Calculate holiday duration start //
$(document).ready(function () {
    $(document).on("keyup change", ".holy-start, .holy-end", function () {
        let modal = $(this).closest(".modal");
        let start = modal.find(".holy-start").val();
        let end = modal.find(".holy-end").val();
        let output = modal.find(".holy-duration");

        if (start && end) {
            let diff = new Date(end) - new Date(start);
            if (diff >= 0) {
                let days = diff / (1000 * 60 * 60 * 24) + 1;
                output.val(days + " days");
            } else {
                output.val("Invalid date");
            }
        }
    });

    $("#holidays-create-modal").on("shown.bs.modal", function () {
        $(this).find(".holy-start, .holy-end").trigger("change");
    });

    $("#holidays-edit-modal").on("shown.bs.modal", function () {
        $(this).find(".holy-start, .holy-end").trigger("change");
    });
});
// Calculate holiday duration end //

//Edit Branch
$(document).on("click", ".branches-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var phone = $(this).data("phone");
    var email = $(this).data("email");
    var address = $(this).data("address");
    var openingBalance = $(this).data("opening-balance");
    var remainingBalance = $(this).data("remaining-balance");
    var desc = $(this).data("desc");

    $("#brnch_name").val(name);
    $("#brnch_phone").val(phone);
    $("#brnch_email").val(email);
    $("#brnch_address").val(address);
    $("#opening_balance").val(openingBalance);
    $("#brnch_desc").val(desc);

    if (openingBalance !== remainingBalance) {
        $("#opening_balance").prop("readonly", true);
    } else {
        $("#opening_balance").prop("readonly", false);
    }

    $(".branchUpdateForm").attr("action", url);
});

$(".common-validation-modal").on("shown.bs.modal", function () {
    $(this)
        .find("form.ajaxform_instant_reload")
        .each(function () {
            $(this).validate();
        });
});

$(".view-btn").each(function () {
    let container = $(this);
    let id = container.data("id");

    // User View Modal
    $("#user_view_" + id).on("click", function () {
        $("#user_view_business_category").text(
            $("#user_view_" + id).data("business_category")
        );
        $("#user_view_business_name").text(
            $("#user_view_" + id).data("business_name")
        );

        let imageSrc = $("#user_view_" + id).data("image");
        $("#user_view_image").attr("src", imageSrc);
        $("#user_view_name").text($("#user_view_" + id).data("name"));
        $("#user_view_role").text($("#user_view_" + id).data("role"));
        $("#user_view_email").text($("#user_view_" + id).data("email"));
        $("#user_view_phone").text($("#user_view_" + id).data("phone"));
        $("#user_view_address").text($("#user_view_" + id).data("address"));
        $("#user_view_country_id").text(
            $("#user_view_" + id).data("country_id")
        );
        $("#user_view_statfeatures-listus").text(
            $("#user_view_" + id).data("status") == 1 ? "Active" : "Deactive"
        );
    });

    // Plan View Modal
    $("#plan_view_" + id).on("click", function () {
        let features = $("#plan_view_" + id).data("features");
        let featuresList = $("#features-list");

        featuresList.empty();

        features.forEach((feature) => {
            let featureHtml = `
                <div class="row align-items-center mt-3 feature-entry">
                    <div class="col-md-1">
                        <p id="plan_view_features_yes">
                            ${
                                feature.value == 1
                                    ? '<i class="fas fa-check-circle"></i>'
                                    : '<i class="fas fa-times-circle"></i>'
                            }
                        </p>
                    </div>
                    <div class="col-1">
                        <p>:</p>
                    </div>
                    <div class="col-md-7">
                        <p id="plan_view_features_name">${feature.name}</p>
                    </div>
                </div>
            `;

            featuresList.append(featureHtml);
        });
    });

    // Category View
    $("#category_view_" + id).on("click", function () {
        $("#category_view_name").text($("#category_view_" + id).data("name"));
        $("#category_view_description").text(
            $("#category_view_" + id).data("description")
        );
        $("#category_view_status").text(
            $("#category_view_" + id).data("status") == 1
                ? "Active"
                : "Deactive"
        );
    });
    // Faqs view
    $("#faqs_view_" + id).on("click", function () {
        $("#faqs_view_question").text($("#faqs_view_" + id).data("question"));
        $("#faqs_view_answer").text($("#faqs_view_" + id).data("answer"));
        $("#faqs_view_status").text(
            $("#faqs_view_" + id).data("status") == 1 ? "Active" : "Deactive"
        );
    });
});

//Business view modal
$(".business-view").on("click", function () {
    $(".business_name").text($(this).data("name"));
    $("#image").attr("src", $(this).data("image"));
    $("#name").text($(this).data("name"));
    $("#address").text($(this).data("address"));
    $("#category").text($(this).data("category"));
    $("#phone").text($(this).data("phone"));
    $("#package").text($(this).data("package"));
    $("#last_enroll").text($(this).data("last_enroll"));
    $("#expired_date").text($(this).data("expired_date"));
    $("#created_date").text($(this).data("created_date"));
});

$(document).on("change", ".file-input-change", function () {
    let prevId = $(this).data("id");
    newPreviewImage(this, prevId);
});

// image preview
function newPreviewImage(input, prevId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#" + prevId).attr("src", e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$(".business-upgrade-plan").on("click", function () {
    let url = $(this).data("url");
    let businessName = $(this).data("name");
    let businessId = $(this).data("id");

    // Set form values
    $("#business_name").val(businessName);
    $("#business_id").val(businessId);
    $(".upgradePlan").attr("action", url);
});

$("#plan_id").on("change", function () {
    $(".plan-price").val($(this).find(":selected").data("price"));
});

$(".modal-reject").on("click", function () {
    var url = $(this).data("url");
    $(".modalRejectForm").attr("action", url);
});

$(".modal-approve").on("click", function () {
    var url = $(this).data("url");
    $(".modalApproveForm").attr("action", url);
});

//edit banner
$(".edit-btn").each(function () {
    let container = $(this);
    let service = container.data("id");
    let id = service;
    $("#edit-banner-" + service).on("click", function () {
        $("#checkbox").prop(
            "checked",
            $("#edit-banner-" + service).data("status") == 1
        );
        $(".dynamic-text").text(
            $("#edit-banner-" + service).data("status") == 1
                ? "Active"
                : "Deactive"
        );

        let edit_action_route = $(this).data("url");
        $("#editForm").attr("action", edit_action_route + "/" + id);
    });
});

$(".edit-banner-btn").on("click", function () {
    let status = $(this).data("status");
    $(".edit-status").prop("checked", status);
    $(".edit-imageUrl-form").attr("action", $(this).data("url"));
    $("#edit-imageUrl").attr("src", $(this).data("image"));

    if (status == 1) {
        $(".dynamic-text").text("Active");
    } else {
        $(".dynamic-text").text("Deactive");
    }
});

$(function () {
    $("body").on("click", ".remove-one", function () {
        $(this).closest(".remove-list").remove();
    });
});
/** Subscriptions Plan end */

//Dynamic Tags Setting Start

$(document)
    .off("click", ".add-new-tag")
    .on("click", ".add-new-tag", function () {
        let html = `
    <div class="col-md-6">
        <div class="row row-items">
            <div class="col-sm-10">
                <label for="">Tags</label>
                <input type="text" name="tags[]" class="form-control" required
                    placeholder="Enter tags name">
            </div>
            <div class="col-sm-2 align-self-center mt-3">
                <button type="button" class="btn text-danger trash remove-btn-features"
                    onclick="removeDynamicField(this)"><i
                        class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    `;
        $(".manual-rows .single-tags").append(html);
    });
//Dynamic tag ends

$(document).on("click", ".add-new-item", function () {
    let html = `
    <div class="row row-items">
        <div class="col-sm-5">
            <label for="">Label</label>
            <input type="text" name="manual_data[label][]" value="" class="form-control" placeholder="Enter label name">
        </div>
        <div class="col-sm-5">
            <label for="">Select Required/Optionl</label>
            <select class="form-control" required name="manual_data[is_required][]">
                <option value="1">Required</option>
                <option value="0">Optional</option>
            </select>
        </div>
        <div class="col-sm-2 align-self-center mt-3">
            <button type="button" class="btn text-danger trash remove-btn-features"><i class="fas fa-trash"></i></button>
        </div>
    </div>
    `;
    $(".manual-rows").append(html);
});

$(document).on("click", ".remove-btn-features", function () {
    var $row = $(this).closest(".row-items");
    $row.remove();
});

// Staff view Start
$(".staff-view-btn").on("click", function () {
    var staffName = $(this).data("staff-view-name");
    var staffPhone = $(this).data("staff-view-phone-number");
    var staffemail = $(this).data("staff-view-email-number");
    var staffRole = $(this).data("staff-view-role");

    $("#staff_view_name").text(staffName);
    $("#staff_view_phone_number").text(staffPhone);
    $("#staff_view_email_number").text(staffemail);
    $("#staff_view_role").text(staffRole);
});
// Staff view End

var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// subscription-plan-edit-custom-input size
const inputs = document.querySelectorAll(
    ".subscription-plan-edit-custom-input"
);

function resizeInput() {
    const tempSpan = document.createElement("span");
    tempSpan.style.visibility = "hidden";
    tempSpan.style.position = "absolute";
    tempSpan.style.whiteSpace = "pre";
    tempSpan.style.font = window.getComputedStyle(this).font;
    tempSpan.textContent = this.value || this.placeholder;

    document.body.appendChild(tempSpan);

    this.style.width = tempSpan.offsetWidth + 20 + "px"; // 20 mean by, left + right = 20px. please check css

    document.body.removeChild(tempSpan);
}

inputs.forEach(function (input) {
    input.addEventListener("input", resizeInput);
    resizeInput.call(input);
});

// ------------BUSINESS PANEL START ---------------------------------------------------------

$(document).on("click", ".category-edit-btn", function () {
    var modal = $("#category-edit-modal");

    $("#category_name").val($(this).data("category-name"));
    $("#category_icon").attr("src", $(this).data("category-icon"));

    modal.find("form").attr("action", $(this).data("url"));
});

$(".units-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var unitName = $(this).data("units-name");
    var unitStatus = $(this).data("units-status");

    $("#unit_view_name").val(unitName);
    $("#unit_status").val(unitStatus);

    if (unitStatus == 1) {
        $("#unit_status").prop("checked", true);
        $(".dynamic-text").text("Active");
    } else {
        $("#unit_status").prop("checked", false);
        $(".dynamic-text").text("Deactive");
    }
    $(".unitUpdateForm").attr("action", url);
});

$(".model-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var modelName = $(this).data("model-name");
    var modelStatus = $(this).data("model-status");

    $("#model_name").val(modelName);
    $("#model_status").val(modelStatus);

    if (modelStatus == 1) {
        $("#model_status").prop("checked", true);
        $(".dynamic-text").text("Active");
    } else {
        $("#model_status").prop("checked", false);
        $(".dynamic-text").text("Deactive");
    }
    $(".modelUpdateForm").attr("action", url);
});

$(".brand-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var brand_name = $(this).data("brands-name");
    var brand_icon = $(this).data("brands-icon");
    var brand_description = $(this).data("brands-description");

    $("#brand_view_name").val(brand_name);
    $("#edit_icon").attr("src", brand_icon);
    $("#brand_view_description").val(brand_description);

    $(".brandUpdateForm").attr("action", url);
});

// Choices select js start
$(document).ready(function () {
    const choicesMap = new Map();

    $(".choices-select").each(function () {
        const select = this;
        const choicesInstance = new Choices(select, {
            searchEnabled: true,
            itemSelectText: "",
            shouldSort: false,
        });
        choicesMap.set(select.id, choicesInstance);
    });

    $(document).on("keydown", ".choices__input--cloned", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();

            const activeInput = $(this);
            const searchTerm = activeInput.val().trim();

            if (!searchTerm) return;
            const choicesContainer = activeInput.closest(".choices");

            const selectId = choicesContainer
                .attr("class")
                .split(" ")
                .find((cls) => cls.startsWith("choices-"))
                ?.replace("choices-", "");

            let selectElement = selectId
                ? $("#" + selectId)
                : choicesContainer.siblings("select.choices-select");

            if (!selectElement.length) {
                selectElement = $(".choices-select");
            }

            if (!selectElement.length) return;

            const finalSelectId = selectElement.attr("id");

            const isCustomer = finalSelectId === "party_id";
            const isSupplier = finalSelectId === "supplier_id";

            if (!isCustomer && !isSupplier) return;

            let matchFound = false;

            selectElement.find("option").each(function () {
                const optionText = $(this).text().trim().toLowerCase();
                if (optionText.includes(searchTerm.toLowerCase())) {
                    matchFound = true;
                    return false;
                }
            });

            if (!matchFound) {
                const modalId = isCustomer
                    ? "#customer-create-modal"
                    : "#supplier-create-modal";
                const modalNameInput = $(modalId).find('input[name="name"]');
                const modalPhoneInput = $(modalId).find('input[name="phone"]');

                if (!modalNameInput.length) return;

                // Check if search term is a phone number
                const phoneRegex = /^(\+?[0-9]{1,15}|[0-9]{3,})$/;
                const isPhoneNumber = phoneRegex.test(searchTerm);

                selectElement.val("").trigger("change");

                if (isPhoneNumber && modalPhoneInput.length) {
                    modalPhoneInput.val(searchTerm);
                } else {
                    modalNameInput.val(searchTerm);
                }

                new bootstrap.Modal($(modalId)[0]).show();
                activeInput.val("");
            }
        }
    });
});
// Choices select js end

/** Product Start */
// Show product with batch-wise
$(".product-view").on("click", function () {
    $("#product_name").text($(this).data("name"));
    $("#product_code").text($(this).data("code"));
    $("#product_brand").text($(this).data("brand"));
    $("#product_category").text($(this).data("category"));
    $("#product_unit").text($(this).data("unit"));
    $("#product_purchase_price").text($(this).data("purchase-price"));
    $("#product_sale_price").text($(this).data("sale-price"));
    $("#product_wholesale_price").text($(this).data("wholesale-price"));
    $("#product_dealer_price").text($(this).data("dealer-price"));
    $("#product_stock").text($(this).data("stock"));
    $("#product_low_stock").text($(this).data("low-stock"));
    $("#product_expire_date").text($(this).data("product-expire-date"));
    $("#product_manufacturer").text($(this).data("manufacturer"));

    const product_image = $(this).data("image");
    $("#product_image").attr("src", product_image);

    const stocks = $(this).data("stocks");
    const $tableBody = $("#stocks_table");
    $tableBody.empty();

    if (Array.isArray(stocks) && stocks.length > 0) {
        stocks.forEach((batch, index) => {
            const row = `<tr>
                <td>${index + 1}</td>
                <td>${batch.batch_no ?? "N/A"}</td>
                <td>${batch.productStock ?? 0}</td>
                <td>${batch.productSalePrice ?? 0}</td>
                <td>${batch.expire_date ?? "-"}</td>
            </tr>`;
            $tableBody.append(row);
        });
    } else {
        $tableBody.append(
            `<tr><td colspan="4" class="text-center text-muted">No batch data available</td></tr>`
        );
    }
});

$(".stock-view-data").on("click", function () {
    const stocks = $(this).data("stocks");
    const $tableBody = $("#stocks-table-data");
    var canStockPrice = $("#canStockPrice").val() == "1";
    var showExpireDate = $("#show_expire_date").val() == "1";
    const showWarehouse = $("#warehouse_module_check").val() == "1";
    const showWeightModule = $("#show_weight").val() == "1";
    const showWarehouseModule = $("#show_warehouse").val() == "1";
    const showRackModule = $("#show_rack").val() == "1";
    const showShelf = $("#show_shelf").val() == "1";

    $tableBody.empty();

    const anyWeight = stocks.some((batch) => batch.showWeight);
    if (anyWeight) {
        $("#weight-header").removeClass("d-none");
    } else {
        $("#weight-header").addClass("d-none");
    }

    if (Array.isArray(stocks) && stocks.length > 0) {
        stocks.forEach((batch) => {
            let row = `<tr>
                <td>${batch.batch_no ?? "N/A"}</td>
                <td>${batch.productStock ?? 0}</td>`;

            if (canStockPrice) {
                row += `<td>${batch.productPurchasePrice ?? 0}</td>`;
            }

            row += `<td>${batch.productSalePrice ?? 0}</td>
                <td>${batch.productWholeSalePrice ?? 0}</td>
                <td>${batch.productDealerPrice ?? 0}</td>`;

            if (showExpireDate) {
                row += `<td>${batch.expire_date ?? ""}</td>`;
            }

            if (showWarehouse && showWarehouseModule) {
                row += `<td>${batch.warehouse ?? "-"}</td>`;
            }

            if (showWeightModule && batch.showWeight) {
                row += `<td>${batch.weight ?? "-"}</td>`;
            }

            if (showRackModule) {
                row += `<td>${batch.rack ?? "-"}</td>`;
            }

            if (showShelf) {
                row += `<td>${batch.shelf ?? "-"}</td>`;
            }

            row += `</tr>`;

            $tableBody.append(row);
        });
    } else {
        $tableBody.append(
            `<tr><td colspan="10" class="text-center text-muted">No batch data available</td></tr>`
        );
    }

    $("#stock-modal-view").modal("show");
});

/** Product End */

// Combo Product View Start
$(document).on("click", ".combo-product-view", function () {
    const comboProducts = $(this).data("combo");
    const $tableBody = $("#combo-product-table-data");

    $tableBody.empty();

    if (Array.isArray(comboProducts) && comboProducts.length > 0) {
        comboProducts.forEach((item) => {
            const row = `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>${item.purchase_price}</td>
                    <td>${item.stock}</td>
                </tr>
            `;
            $tableBody.append(row);
        });
    } else {
        $tableBody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted">No combo products available</td>
            </tr>
        `);
    }

    $("#combo-product-view").modal("show");
});
// Combo Product View End

// Sale Pyament View Start
$(document).on("click", ".view-sale-payment-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url");
    let tableBody = $("#view-sale-payment-table-data");

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (response) {
            if (response.data && response.data.length > 0) {
                let rows = "";
                $.each(response.data, function (index, item) {
                    rows += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td>${item.date}</td>
                                <td>${item.receipt_no}</td>
                                <td>${item.amount}</td>
                                <td>${item.payment_type}</td>
                                <td
                                    <div class="dropdown table-action">
                                        <button type="button" data-bs-toggle="dropdown">
                                            <i class="far fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="">
                                                    <i class="fal fa-edit"></i>
                                                    Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a href="" class="confirm-action" data-method="DELETE">
                                                    <i class="fal fa-trash-alt"></i>
                                                    Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;
                });
                tableBody.html(rows);
            } else {
                tableBody.html(
                    `<tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>`
                );
            }
        },
        error: function () {
            tableBody.html(
                `<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>`
            );
        },
    });
});
// Sale Pyament View End

// Purchase Pyament View Start
$(document).on("click", ".view-purchase-payment-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url");
    let tableBody = $("#view-purchase-payment-table-data");

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (response) {
            if (response.data && response.data.length > 0) {
                let rows = "";
                $.each(response.data, function (index, item) {
                    rows += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td>${item.date}</td>
                                <td>${item.receipt_no}</td>
                                <td>${item.amount}</td>
                                <td>${item.payment_type}</td>
                                <td
                                    <div class="dropdown table-action">
                                        <button type="button" data-bs-toggle="dropdown">
                                            <i class="far fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="">
                                                    <i class="fal fa-edit"></i>
                                                    Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a href="" class="confirm-action" data-method="DELETE">
                                                    <i class="fal fa-trash-alt"></i>
                                                    Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;
                });
                tableBody.html(rows);
            } else {
                tableBody.html(
                    `<tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>`
                );
            }
        },
        error: function () {
            tableBody.html(
                `<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>`
            );
        },
    });
});
// Purchase Pyament View End

$(".parties-view-btn").on("click", function () {
    $("#parties_name").text($(this).data("name"));
    $("#parties_phone").text($(this).data("phone"));
    $("#parties_email").text($(this).data("email"));
    $("#parties_type").text($(this).data("type"));
    $("#parties_address").text($(this).data("address"));
    $("#parties_due").text($(this).data("due"));
});

$(".income-categories-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("income-categories-name");
    var description = $(this).data("income-categories-description");

    $("#income_categories_view_name").val(name);
    $("#income_categories_view_description").val(description);

    $(".incomeCategoryUpdateForm").attr("action", url);
});

$(".expense-categories-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var expense_name = $(this).data("expense-categories-name");
    var expense_description = $(this).data("expense-categories-description");

    $("#expense_categories_view_name").val(expense_name);
    $("#expense_categories_view_description").val(expense_description);

    $(".expenseCategoryUpdateForm").attr("action", url);
});

$(".incomes-edit-btn").on("click", function () {
    const url = $(this).data("url");
    const income_category_id = $(this).data("income-category-id");
    const incomeAmount = $(this).data("income-amount");
    const incomeFor = $(this).data("income-for");
    const incomeReferenceNo = $(this).data("income-reference-no");
    const incomeDate = $(this).data("income-date-update");
    const incomeNote = $(this).data("income-note");
    const existingTransactions = $(this).data("existing-transactions") || [];

    $("#income_categoryId").val(income_category_id);
    $("#inc_price").val(incomeAmount);
    $("#inc_for").val(incomeFor);
    $("#incomeReferenceNo").val(incomeReferenceNo);
    const formattedDate = incomeDate ? incomeDate.split(" ")[0] : "";
    $("#inc_date_update").val(formattedDate);

    $("#inc_note").val(incomeNote);

    $(".incomeUpdateForm").attr("action", url);

    loadExistingPayments(existingTransactions);
});

$(".expense-edit-btn").on("click", function () {
    const url = $(this).data("url");
    const expenseCategoryId = $(this).data("expense-category-id");
    const expenseAmount = $(this).data("expense-amount");
    const expenseReferenceNo = $(this).data("expense-reference-no");
    const expenseFor = $(this).data("expense-for");
    const expenseDate = $(this).data("expense-date");
    const expenseNote = $(this).data("expense-note");
    const existingTransactions = $(this).data("existing-transactions") || [];

    // Set the values in the modal's fields
    $("#expenseCategoryId").val(expenseCategoryId);
    $("#expense_amount").val(expenseAmount);
    $("#refeNo").val(expenseReferenceNo);
    $("#expe_for").val(expenseFor);
    const formattedDate = expenseDate ? expenseDate.split(" ")[0] : "";
    $("#edit_date_expe").val(formattedDate);
    $("#expenote").val(expenseNote);

    // Update the form action attribute
    $(".expenseUpdateForm").attr("action", url);

    loadExistingPayments(existingTransactions);
});

$(".warehouse-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var branchId = $(this).data("branch-id");
    var name = $(this).data("name");
    var phone = $(this).data("phone");
    var email = $(this).data("email");
    var address = $(this).data("address");

    $("#name").val(name);
    $("#branch_id").val(branchId);
    $("#phone").val(phone);
    $("#email").val(email);
    $("#address").val(address);

    $(".warehouseUpdateForm").attr("action", url);
});

// warehouse view
$(".warehouse-view-btn").on("click", function () {
    $("#warehouseName").text($(this).data("warehouse-name"));
    $("#branchName").text($(this).data("branch-name"));
    $("#warehousePhone").text($(this).data("warehouse-phone"));
    $("#warehouseEmail").text($(this).data("warehouse-email"));
    $("#warehouseAddress").text($(this).data("warehouse-address"));
});

// function showTab(tabId) {
//     // Activate selected tab
//     document
//         .querySelectorAll(".tab-item")
//         .forEach((tab) => tab.classList.remove("active"));
//     document
//         .querySelectorAll(".tab-content")
//         .forEach((content) => content.classList.remove("active"));

//     document.getElementById(tabId).classList.add("active");
//     document
//         .querySelector(`[onclick="showTab('${tabId}')"]`)
//         .classList.add("active");

//     // Get the base URL from the hidden input fields
//     const csvBaseUrl = document.getElementById("csvBaseUrl").value;
//     const excelBaseUrl = document.getElementById("excelBaseUrl").value;
//     const pdfBaseUrl = document.getElementById("pdfBaseUrl").value;

//     // Set correct export type
//     let type = tabId == "sales" ? "sales" : "purchases";

//     // Update export URLs dynamically
//     const csv = document.getElementById("csvExportLink");
//     const excel = document.getElementById("excelExportLink");
//     const pdf = document.getElementById("pdfExportLink");

//     if (csv) {
//         csv.href = `${csvBaseUrl}?type=${type}`;
//     }
//     if (excel) {
//         excel.href = `${excelBaseUrl}?type=${type}`;
//     }
//     if (pdf) {
//         pdf.href = `${pdfBaseUrl}?type=${type}`;
//     }
// }

// $(document).ready(function () {
//     showTab("sales");
// });

// Multidelete Start

function updateSelectedCount() {
    var selectedCount = $(".delete-checkbox-item:checked").length;
    $(".selected-count").text(selectedCount);

    if (selectedCount > 0) {
        $(".delete-show").removeClass("d-none");
    } else {
        $(".delete-show").addClass("d-none");
    }
}

// Delegated binding for "select all"
$(document).on("click", ".select-all-delete", function () {
    $(".delete-checkbox-item").prop("checked", this.checked);
    updateSelectedCount();
});

// Delegated binding for single checkbox
$(document).on("change", ".delete-checkbox-item", function () {
    updateSelectedCount();
});

// Trigger delete modal
$(document).on("click", ".trigger-modal", function () {
    var dynamicUrl = $(this).data("url");
    $("#dynamic-delete-form").attr("action", dynamicUrl);

    var ids = $(".delete-checkbox-item:checked")
        .map(function () {
            return $(this).val();
        })
        .get();

    if (ids.length === 0) {
        alert("Please select at least one item.");
        return;
    }

    var form = $("#dynamic-delete-form");
    form.find("input[name='ids[]']").remove();

    ids.forEach(function (id) {
        form.append('<input type="hidden" name="ids[]" value="' + id + '">');
    });
});

// Submit form
$(document).on("click", ".create-all-delete", function (event) {
    event.preventDefault();
    $("#dynamic-delete-form").submit();
});

// Multidelete End

// Collects Due Start
$("#invoiceSelect").on("change", function () {
    const selectedOption = $(this).find("option:selected");
    const dueAmount = selectedOption.data("due-amount");
    const openingDue = selectedOption.data("opening-due");

    if (!selectedOption.val()) {
        $("#totalAmount").val(openingDue.toFixed(2));
        $("#dueAmount").val(openingDue.toFixed(2));
    } else {
        $("#totalAmount").val(dueAmount.toFixed(2));
        $("#dueAmount").val(dueAmount.toFixed(2));
    }

    calculateDueChange();
});

$("#paidAmount").on("input", function () {
    calculateDueChange();
});
function calculateDueChange() {
    const payingAmount = parseFloat($("#paidAmount").val()) || 0;
    const totalAmount = parseFloat($("#totalAmount").val()) || 0;

    if (payingAmount > totalAmount) {
        toastr.error("cannot pay more than due.");
    }

    const updatedDueAmount = totalAmount - payingAmount;
    $("#dueAmount").val(
        updatedDueAmount >= 0 ? updatedDueAmount.toFixed(2) : 0
    );
}
// Collects Due End

//Subscriber view modal
$(".subscriber-view").on("click", function () {
    $(".business_name").text($(this).data("name"));
    $("#image").attr("src", $(this).data("image"));
    $("#category").text($(this).data("category"));
    $("#package").text($(this).data("package"));
    $("#gateway").text($(this).data("gateway"));
    $("#enroll_date").text($(this).data("enroll"));
    $("#expired_date").text($(this).data("expired"));
    $("#manul_attachment").attr("src", $(this).data("manul-attachment"));
});

/** barcode: start **/
$("#product-search").on("keyup click", function () {
    const query = $(this).val().toLowerCase();
    const fetchRoute = $("#fetch-products-route").val();
    function assetPath(path) {
        const baseUrl = $("#asset_base_url").val();
        if (!baseUrl) return path;
        return baseUrl.replace(/\/$/, "") + "/" + path.replace(/^\/+/, "");
    }

    // Fetch matching products
    $.ajax({
        url: fetchRoute,
        type: "GET",
        data: { search: query },
        dataType: "json",

        success: function (data) {
            let productList = "";
            if (data.length > 0) {
                data.forEach((product) => {
                    const imagePath = assetPath(
                        product.productPicture ||
                            "assets/images/products/box.svg"
                    );
                    const productColor = product.color
                        ? `, Color: ${product.color}`
                        : "";
                    const hasMultipleStocks =
                        product.stocks && product.stocks.length > 1;

                    if (hasMultipleStocks) {
                        let multiStockHtml = "";
                        product.stocks.forEach((stock) => {
                            const stockQty = stock.productStock ?? 0;
                            const stockPrice = stock.productSalePrice ?? 0;

                            multiStockHtml += `
                        <div class="d-flex align-items-center justify-content-between w-100 multi-items add-batch-item"
                            data-id="${stock.id}"
                            data-product_id="${product.id}"
                            data-name="${product.productName}"
                            data-code="${product.productCode}"
                            data-vat_type="${product.vat_type}"
                            data-stock="${stockQty}"
                            data-stock="${stockQty}"
                            data-batch_no="${stock.batch_no ?? ""}">
                            <div class="product-des">
                                Batch: ${stock.batch_no ?? "N/A"}${productColor}
                                <span class="product-in-stock">In Stock: ${stockQty}</span>
                            </div>
                            <div class="product-price">${stockPrice}</div>
                        </div>`;
                        });

                        productList += `
                    <div class="product-option-item single-product ${product.id}">
                        <div class="product-left">
                            <img src="${imagePath}" alt="">
                            <div class="product-text">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="product-title">${product.productName}</div>
                                    <p>Code: ${product.productCode}</p>
                                </div>
                                ${multiStockHtml}
                            </div>
                        </div>
                    </div>`;
                    } else {
                        const stock = product.stocks[0] ?? {};
                        const stockQty = stock.productStock ?? 0;
                        const stockPrice = stock.productSalePrice ?? 0;

                        productList += `
                    <div class="product-option-item single-product ${
                        product.id
                    } add-batch-item"
                        data-id="${stock.id}"
                        data-product_id="${product.id}"
                        data-name="${product.productName}"
                        data-code="${product.productCode}"
                        data-stock="${stockQty}"
                        data-batch_no="${stock.batch_no ?? ""}">
                        <div class="product-left">
                            <img src="${imagePath}" alt="">
                            <div class="product-text">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="product-title">${
                                        product.productName
                                    }</div>
                                    <p>Code: ${product.productCode}</p>
                                </div>
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="product-des">
                                        Batch: ${
                                            stock.batch_no ?? "N/A"
                                        }${productColor}
                                        <span class="product-in-stock">In Stock: ${stockQty}</span>
                                    </div>
                                    <div class="product-price">${stockPrice}</div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    }
                });
            } else {
                productList =
                    '<li class="list-group-item text-danger">No products found.</li>';
            }

            $("#search-results").html(productList).show();
        },
        error: function () {
            console.log("Unable to fetch products. Please try again later.");
        },
    });
});

// Hide search results when clicking outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#product-search, #search-results").length) {
        $("#search-results").hide();
    }
});

// When a product is selected from the list
$(document).on("click", ".add-batch-item", function () {
    const productId = $(this).data("product_id");
    const stockId = $(this).data("id");
    const productName = $(this).data("name");
    const productCode = $(this).data("code");
    const vatType = $(this).data("vat_type");
    const productStock = $(this).data("stock");
    const batchNo = $(this).data("batch_no");

    // Add the item to the table
    if (!$('#product-list tr[data-id="' + stockId + '"]').length) {
        const newRow = `
        <tr data-id="${stockId}">
            <td class="text-start">${productName}</td>
            <td>${productCode}</td>
            <td>${batchNo}</td>
            <td>${productStock}</td>
            <td class="large-td">
                <div class="d-flex align-items-center justify-content-center">
                    <button class="incre-decre sub-btn"><i class="fas fa-minus icon"></i></button>
                    <input type="number" name="qty[]" value="1" class="custom-number-input pint-qty" placeholder="0">
                    <button class="incre-decre add-btn"><i class="fas fa-plus icon"></i></button>
                </div>
            </td>
            <td class="large-td">
                <input type="date" name="preview_date[]" class="form-control input-date">
            </td>
            <td>
                <button class="x-btn remove-btn text-danger">
                    <i class="far fa-times"></i>
                </button>
            </td>
        </tr>`;
        $("#product-list").append(newRow);
    } else {
        const qtyInput = $('#product-list tr[data-id="' + stockId + '"]').find(
            ".pint-qty"
        );
        let currentQty = parseInt(qtyInput.val(), 10) || 0;
        qtyInput.val(currentQty + 1);
    }

    $("#search-results").hide();
    $("#product-search").val("");
});

$(document).on("click", ".remove-btn", function () {
    $(this).closest("tr").remove();
});

// Increase quantity
$(document).on("click", ".add-btn", function (e) {
    e.preventDefault();
    const qtyInput = $(this).siblings(".pint-qty");
    let currentQty = parseInt(qtyInput.val(), 10) || 0;
    qtyInput.val(currentQty + 1);
});

// Decrease quantity
$(document).on("click", ".sub-btn", function (e) {
    e.preventDefault();
    const qtyInput = $(this).siblings(".pint-qty");
    let currentQty = parseInt(qtyInput.val(), 10) || 1;
    if (currentQty > 1) {
        qtyInput.val(currentQty - 1);
    }
});

let $savingLoader1 =
        '<div class="spinner-border spinner-border-sm custom-text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
    $barcodeForm = $(".barcodeForm");

$barcodeForm.initFormValidation(),
    $(document).on("submit", ".barcodeForm", function (e) {
        e.preventDefault();
        let t = $(this).find("#barcode-preview-btn"),
            a = t.html();

        if ($barcodeForm.valid()) {
            let formData = new FormData(this);

            // Collect stock_ids
            $("#product-list tr").each(function () {
                let stockId = $(this).data("id");
                formData.append("stock_ids[]", stockId);
            });

            $.ajax({
                type: "POST",
                url: this.action,
                data: formData,
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function () {
                    t.html($savingLoader1).attr("disabled", true);
                },
                success: function (e) {
                    t.html(a).removeClass("disabled").attr("disabled", false);

                    if (e.secondary_redirect_url) {
                        let printWindow = window.open(
                            e.secondary_redirect_url,
                            "_blank"
                        );
                        if (printWindow) {
                            printWindow.onload = function () {
                                printWindow.print();
                            };
                        }
                    }

                    if (e.redirect) {
                        location.href = e.redirect;
                    }
                },
                error: function (e) {
                    t.html(a).attr("disabled", false);
                    Notify("error", e);
                },
            });
        }
    });

/** Barcode: end **/
// Loss/Profit view
$(document).on("click", ".loss-profit-view", function () {
    let lossProfitId = $(this).data("id");
    let url = $("#loss-profit-id").val();

    $.ajax({
        url: url.replace(":id", lossProfitId),
        type: "GET",
        success: function (data) {
            let tbody = $("#loss-profit-view tbody");
            tbody.empty();

            $("#loss-profit-view .modal-title").text(
                `Invoice: ${data.invoiceNumber} - ${data.party?.name || "N/A"}`
            );

            let sl = 1;
            let totalQty = 0;
            let totalPurchase = 0;
            let totalSale = 0;
            let totalProfit = 0;
            let totalLoss = 0;

            data.details.forEach((detail) => {
                let quantity = detail.quantities || 0;
                let purchasePrice = detail.productPurchasePrice || 0;
                let salePrice = detail.price || 0;
                let profit = detail.lossProfit > 0 ? detail.lossProfit : 0;
                let loss =
                    detail.lossProfit < 0 ? Math.abs(detail.lossProfit) : 0;
                let batchNo = detail.stock?.batch_no || "-";

                totalQty += quantity;
                totalPurchase += purchasePrice;
                totalSale += salePrice;
                totalProfit += profit;
                totalLoss += loss;

                let row = `
                    <tr>
                        <td>${sl++}</td>
                        <td class="text-start">${
                            detail.product?.productName || "-"
                        }</td>
                        <td class="text-start">${batchNo}</td>
                        <td class="text-start">${quantity}</td>
                        <td class="text-start">${currencyFormat(
                            purchasePrice
                        )}</td>
                        <td class="text-start">${currencyFormat(salePrice)}</td>
                        <td class="text-start text-success">${currencyFormat(
                            profit
                        )}</td>
                        <td class="text-start text-danger">${currencyFormat(
                            loss
                        )}</td>
                    </tr>
                `;
                tbody.append(row);
            });

            let income = totalProfit - totalLoss;

            // Append summary rows
            let summary = `
                <tr class="fw-bold bg-light">
                    <td colspan="3" class="text-end">Total:</td>
                    <td class="text-start">${totalQty}</td>
                    <td class="text-start">${currencyFormat(totalPurchase)}</td>
                    <td class="text-start">${currencyFormat(totalSale)}</td>
                    <td class="text-start text-success">${currencyFormat(
                        totalProfit
                    )}</td>
                    <td class="text-start text-danger">${currencyFormat(
                        totalLoss
                    )}</td>
                </tr>
                <tr>
                    <td colspan="8" class="border-0 pt-3"><strong class="text-success">Total Profit: ${currencyFormat(
                        totalProfit
                    )}</strong></td>
                </tr>
                <tr>
                    <td colspan="8" class="border-0"><strong class="text-danger">Total Loss: ${currencyFormat(
                        totalLoss
                    )}</strong></td>
               </tr>
                <tr>
                    <td colspan="8" class="border-0"><strong class="text-primary">Net Profit: ${currencyFormat(
                        income
                    )}</strong></td>
                </tr>
            `;
            tbody.append(summary);
        },
        error: function (xhr) {
            console.error(
                "Failed to load sale data:",
                xhr.status,
                xhr.responseText
            );
        },
    });
});

//Vat start
$(".vat-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("vat-name");
    var rate = $(this).data("vat-rate");
    var newrate = $(this).data("new-vat-rate");
    var status = $(this).data("vat-status");

    $("#vat_name").val(name);
    $("#vat_rate").val(rate);
    $("#new_vat_rate").val(newrate);
    $("#vat_status").val(status);
    $(".updateVatForm").attr("action", url);
});
//Vat End

/** Report Filter: Start **/

// Handle Custom Date Selection
$(".custom-days").on("change", function () {
    let selected = $(this).val();
    let dateFilters = $(".date-filters");

    // Show or hide the date filters based on selection
    if (selected === "custom_date") {
        dateFilters.removeClass("d-none");
    } else {
        dateFilters.addClass("d-none");
    }

    // Trigger the form submission to apply the filters
    $(".report-filter-form").trigger("input");
});

// Auto-show custom date fields when page loads
$(document).ready(function () {
    if ($(".custom-days").val() === "custom_date") {
        $(".date-filters").removeClass("d-none");
    }
});

// Vat Report Filter Start
$(document).on('change', '.vat-report-filter-form :input', function () {

    let form = $(this).closest('form');
    let url = form.attr('action');

    $.ajax({
        url: url,
        data: form.serialize(),
        success: function (res) {
            $('#sale-vat-reports-data').html(res.sale_data);
            $('#purchase-vat-reports-data').html(res.purchase_data);
        }
    });
});
// Vat Report Filter Start

// Report Filter Form Submission
$(".report-filter-form").on("input change", function (e) {
    e.preventDefault();
    let form = $(this);
    let table = form.attr("table");

    $.ajax({
        type: "GET",
        url: form.attr("action"),
        data: form.serialize(),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            $(table).html(res.data);
            if (res.total_sale !== undefined) {
                $("#total_sale").text(res.total_sale);
            }
            if (res.total_sale_return !== undefined) {
                $("#total_sale_return").text(res.total_sale_return);
            }
            if (res.total_purchase !== undefined) {
                $("#total_purchase").text(res.total_purchase);
            }
            if (res.total_purchase_return !== undefined) {
                $("#total_purchase_return").text(res.total_purchase_return);
            }
            if (res.total_income !== undefined) {
                $("#total_income").text(res.total_income);
            }
            if (res.total_expense !== undefined) {
                $("#total_expense").text(res.total_expense);
            }
            if (res.total_loss !== undefined) {
                $("#total_loss").text(res.total_loss);
            }
            if (res.total_profit !== undefined) {
                $("#total_profit").text(res.total_profit);
            }
            if (res.total_sale_count !== undefined) {
                $("#total_sale_count").text(res.total_sale_count);
            }
            if (res.total_sale_amount !== undefined) {
                $("#total_sale_amount").text(res.total_sale_amount);
            }
            if (res.total_due !== undefined) {
                $("#total_due").text(res.total_due);
            }
            if (res.total_paid !== undefined) {
                $("#total_paid").text(res.total_paid);
            }

            if (res.total_tr_amount !== undefined) {
                $("#total_tr_amount").text(res.total_tr_amount);
            }

            if (res.total_tr_money_in !== undefined) {
                $("#total_tr_money_in").text(res.total_tr_money_in);
            }

            if (res.total_tr_money_out !== undefined) {
                $("#total_tr_money_out").text(res.total_tr_money_out);
            }
            //

            if (res.opening_stock_by_purchase !== undefined) {
                $("#opening_stock_by_purchase").text(
                    res.opening_stock_by_purchase
                );
            }

            if (res.closing_stock_by_purchase !== undefined) {
                $("#closing_stock_by_purchase").text(
                    res.closing_stock_by_purchase
                );
            }

            if (res.total_purchase_price !== undefined) {
                $("#total_purchase_price").text(res.total_purchase_price);
            }

            if (res.total_purchase_shipping_charge !== undefined) {
                $("#total_purchase_shipping_charge").text(
                    res.total_purchase_shipping_charge
                );
            }

            if (res.total_purchase_discount !== undefined) {
                $("#total_purchase_discount").text(res.total_purchase_discount);
            }

            if (res.all_purchase_return !== undefined) {
                $("#all_purchase_return").text(res.all_purchase_return);
            }

            if (res.all_sale_return !== undefined) {
                $("#all_sale_return").text(res.all_sale_return);
            }

            if (res.opening_stock_by_sale !== undefined) {
                $("#opening_stock_by_sale").text(res.opening_stock_by_sale);
            }

            if (res.closing_stock_by_sale !== undefined) {
                $("#closing_stock_by_sale").text(res.closing_stock_by_sale);
            }

            if (res.total_sale_price !== undefined) {
                $("#total_sale_price").text(res.total_sale_price);
            }

            if (res.total_sale_shipping_charge !== undefined) {
                $("#total_sale_shipping_charge").text(
                    res.total_sale_shipping_charge
                );
            }

            if (res.total_sale_discount !== undefined) {
                $("#total_sale_discount").text(res.total_sale_discount);
            }

            if (res.total_sale_rounding_off !== undefined) {
                $("#total_sale_rounding_off").text(res.total_sale_rounding_off);
            }

            if (res.total_asset !== undefined) {
                $("#total_asset_box").text(res.total_asset);
            }
        },
    });
});

// live search on inputs
$(".custom-report-filter input").on("input", function (e) {
    e.preventDefault();
    ajaxFilter($(this).closest("form"));
});

// change on selects
$(".custom-report-filter select").on("change", function (e) {
    e.preventDefault();
    ajaxFilter($(this).closest("form"));
});

function ajaxFilter(form) {
    let table = form.attr("table");
    $.ajax({
        type: "GET",
        url: form.attr("action"),
        data: form.serialize(),
        dataType: "json",
        success: function (res) {
            $(table).html(res.data);
        },
    });
}

/** Report Filter: End **/

$(document).ready(function () {
    // Accordion logic
    const $accordionButtons = $(".accordion-button");

    $accordionButtons.on("click", function () {
        const $this = $(this);
        const $icon = $this.find(".icon");
        const expanded = $this.attr("aria-expanded") === "true";

        $icon.text(expanded ? "−" : "+");

        // Reset others
        $accordionButtons.not($this).each(function () {
            $(this).find(".icon").text("+");
            $(this).removeClass("text-primary").addClass("text-dark");
        });

        // Toggle active color
        if (expanded) {
            $this.addClass("text-primary").removeClass("text-dark");
        } else {
            $this.removeClass("text-primary").addClass("text-dark");
        }
    });

    // File upload logic
    const $uploadBox = $("#uploadBox");
    const $fileInput = $("#fileInput");
    const $previewArea = $("#previewArea");

    const defaultContent = $previewArea.html();

    // Browse click
    $uploadBox.on("click", function () {
        $fileInput.click();
    });

    // Drag & Drop
    $uploadBox.on("dragover", function (e) {
        e.preventDefault();
        $uploadBox.css("border-color", "#999");
    });

    $uploadBox.on("dragleave", function () {
        $uploadBox.css("border-color", "#ccc");
    });

    $uploadBox.on("drop", function (e) {
        e.preventDefault();
        $uploadBox.css("border-color", "#ccc");
        handleFile(e.originalEvent.dataTransfer.files[0]);
    });

    // File input change
    $fileInput.on("change", function (e) {
        handleFile(e.target.files[0]);
    });

    // Handle file + preview
    function handleFile(file) {
        if (!file || !file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            $previewArea.html(`
                <img src="${e.target.result}" alt="preview"/>
                <button class="img-remove-btn">×</button>
            `);

            // Attach remove button event
            $previewArea.find(".img-remove-btn").on("click", function (event) {
                event.stopPropagation();
                $previewArea.html(defaultContent);
                $fileInput.val("");
            });
        };
        reader.readAsDataURL(file);
    }
});

// When the user clicks on the show/hide icon
$(".hide-show-icon").click(function () {
    let input = $(this).siblings("input");
    let showIcon = $(this).find(".showIcon");
    let hideIcon = $(this).find(".hideIcon");

    input.attr("type", input.attr("type") === "password" ? "text" : "password");

    showIcon.toggleClass("d-none");
    hideIcon.toggleClass("d-none");
});

$(".designations-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var designations_name = $(this).data("designations-name");
    var designations_description = $(this).data("designations-description");

    $("#designations_view_name").val(designations_name);
    $("#designations_view_description").val(designations_description);

    $(".designationUpdateForm").attr("action", url);
});

$(".department-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var department_name = $(this).data("department-name");
    var department_description = $(this).data("department-description");

    $("#department_view_name").val(department_name);
    $("#department_view_description").val(department_description);

    $(".departmentUpdateForm").attr("action", url);
});

$(".shifts-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("shifts-name");
    var shiftStart = $(this).data("shifts-start");
    var shiftEnd = $(this).data("shifts-end");
    var start_break = $(this).data("start-break-time");
    var end_break = $(this).data("end-break-time");
    var break_status = $(this).data("shifts-break-status");

    $("#shift_eidt_name").val(name);
    $("#shift_start_time").val(shiftStart);
    $("#shift_end_time").val(shiftEnd);
    $("#edit_start_break_time").val(start_break);
    $("#edit_end_break_time").val(end_break);
    $("#break_status").val(break_status);
    $(".editShiftForm").attr("action", url);
});
//Shift End

$(".employees-view").on("click", function () {
    $("#employees_name").text($(this).data("employees-name"));
    $("#employees_gender").text($(this).data("employees-gender"));
    $("#employees_phone").text($(this).data("employees-phone"));
    $("#employees_amount").text($(this).data("employees-amount"));
    $("#employees_email").text($(this).data("employees-email"));
    $("#employees_country").text($(this).data("employees-country"));
    $("#employees_birth_date").text($(this).data("employees-birth-date"));
    $("#employees_join_date").text($(this).data("employees-join-date"));
    $("#employees_designation").text($(this).data("employees-designation"));
    $("#employees_department").text($(this).data("employees-department"));
    $("#employees_shift").text($(this).data("employees-shift"));
    $("#employees_status").text($(this).data("employees-status"));

    const employees_image = $(this).data("employees-image");
    $("#employees_image").attr("src", employees_image);
});

$(".leave-types-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("leave-types-name");
    var description = $(this).data("leave-types-description");
    $("#leave_types_name").val(name);
    $("#leave_types_description").val(description);
    $(".leaveTypeEditForm").attr("action", url);
});

// Date Count
$(document).ready(function () {
    function calculateLeaveDuration() {
        let startDateInput = $(".leave_start_date").val();
        let endDateInput = $(".leave_end_date").val();

        if (startDateInput && endDateInput) {
            let startDate = new Date(startDateInput);
            let endDate = new Date(endDateInput);

            // Check if the dates are valid
            if (!isNaN(startDate) && !isNaN(endDate)) {
                if (startDate <= endDate) {
                    let timeDifference =
                        endDate.getTime() - startDate.getTime();
                    let daysDifference =
                        timeDifference / (1000 * 3600 * 24) + 1; // Include end date in duration
                    $(".leave_duration_cal").val(daysDifference);
                } else {
                    $(".leave_duration_cal").val("");
                    toastr.error(
                        "Start date must be before or equal to end date."
                    );
                }
            } else {
                $(".leave_duration_cal").val("");
                toastr.error("Please enter valid dates.");
            }
        } else {
            $(".leave_duration_cal").val("");
        }
    }

    $(".leave_start_date, .leave_end_date").on("change", function () {
        calculateLeaveDuration();
    });

    calculateLeaveDuration();
});

// date count start
function calculateLeaveDuration() {
    let startDate = new Date($(".leave_edit_start_date").val());
    let endDate = new Date($(".leave_edit_end_date").val());

    if (startDate && endDate && startDate <= endDate) {
        let timeDifference = endDate.getTime() - startDate.getTime();
        let daysDifference = timeDifference / (1000 * 3600 * 24) + 1; // Include end date in duration
        $(".leave_edit_duration").val(daysDifference);
    } else {
        $(".leave_edit_duration").val("");
        if (startDate > endDate) {
            alert("Start date must be before or equal to the end date.");
        }
    }
}

$(document).on(
    "change",
    ".leave_edit_start_date, .leave_edit_end_date",
    function () {
        calculateLeaveDuration();
    }
);

$(document).on("show.bs.modal", "#edit-leave", function () {
    calculateLeaveDuration();
});

calculateLeaveDuration();

$(".leaves-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var employeeId = $(this).data("employee-id");
    var month = $(this).data("month");
    var leaveTypeId = $(this).data("leave-type-id");
    var startDate = $(this).data("start-date");
    var endDate = $(this).data("end-date");
    var leaveDuration = $(this).data("leave-duration");
    var status = $(this).data("status");
    var description = $(this).data("description");

    $("#employee_id").val(employeeId);
    $("#month").val(month);
    $("#leave_type_id").val(leaveTypeId);
    $("#start_date").val(startDate);
    $("#end_date").val(endDate);
    $("#leave_duration").val(leaveDuration);
    $("#status").val(status);
    $("#description").val(description);
    $(".editLeaveForm").attr("action", url);
});

$(".holidays-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("holidays-name");
    var holiday_start_date = $(this).data("holidays-start-date");
    var holiday_end_date = $(this).data("holidays-end-date");
    var description = $(this).data("holidays-description");

    $("#name").val(name);
    $("#start_date").val(holiday_start_date);
    $("#end_date").val(holiday_end_date);
    $("#description").val(description);
    $(".editHoldayForm").attr("action", url);
});

$(".attendances-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var employee_id = $(this).data("employee-id");
    var month = $(this).data("month");
    var date = $(this).data("date");
    var time_in = $(this).data("time-in");
    var time_out = $(this).data("time-out");
    var note = $(this).data("note");

    $("#employee_id").val(employee_id);
    $("#month").val(month);
    $("#date").val(date);
    $("#time_in").val(time_in);
    $("#time_out").val(time_out);
    $("#note").val(note);
    $(".editAttendanceForm").attr("action", url);
});

$(".payrolls-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var employee_id = $(this).data("employee-id");
    var payment_type_id = $(this).data("payment-type-id") || "cash";
    var date = $(this).data("date");
    var month = $(this).data("month");
    var amount = $(this).data("amount");
    var note = $(this).data("note");
    var payment_year = $(this).data("payment-year");

    $("#employee_id").val(employee_id);
    $("#payment_type_id").val(payment_type_id);
    $(".salary-month").val("october");
    $("#date").val(date);
    $("#amount").val(amount);
    $("#note").val(note);
    $("#payment_year").val(payment_year);
    $(".editPayrollForm").attr("action", url);
});

$(".shelf-edit-btn").on("click", function () {
    var url = $(this).data("url");
    var name = $(this).data("shelf-name");

    $("#name").val(name);
    $(".shelfUpdateForm").attr("action", url);
});

// Bank edit start
$(document).on("click", ".bank-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var opening_balance = $(this).data("opening-balance");
    var opening_date = $(this).data("opening-date");
    var account_number = $(this).data("account-number");
    var ifsc_code = $(this).data("ifsc-code");
    var upi_id = $(this).data("upi-id");
    var bank_name = $(this).data("bank-name");
    var account_holder = $(this).data("account-holder");
    var show_in_invoice = $(this).data("show-in-invoice");

    $("#name").val(name);
    $("#opening_balance").val(opening_balance);
    $("#opening_date").val(opening_date);
    $("#account_number").val(account_number);
    $("#ifsc_code").val(ifsc_code);
    $("#upi_id").val(upi_id);
    $("#bank_name").val(bank_name);
    $("#account_holder").val(account_holder);
    $("#show_in_invoice").prop("checked", show_in_invoice === 1);

    $(".bankUpdateForm").attr("action", url);
});
// Bank edit end

// Bank view start
$(document).on("click", ".bank-view-btn", function () {
    var name = $(this).data("name");
    var balance = $(this).data("balance");
    var opening_date = $(this).data("opening-date");
    var account_number = $(this).data("account-number");
    var bank_name = $(this).data("bank-name");
    var ifsc_code = $(this).data("ifsc-code");
    var upi_id = $(this).data("upi-id");
    var account_holder = $(this).data("account-holder");
    var show_in_invoice = $(this).data("show-in-invoice");
    var branch = $(this).data("branch");

    $("#view_account_name").text(name);
    $("#view_balance").text(balance);
    $("#view_opening_date").text(opening_date);
    $("#view_account_number").text(account_number);
    $("#view_bank_name").text(bank_name);
    $("#view_ifsc_code").text(ifsc_code);
    $("#view_upi_id").text(upi_id);
    $("#view_account_holder").text(account_holder);
    $("#view_show_in_invoice").text(show_in_invoice == 1 ? "Yes" : "No");
    $("#view_branch").text(branch);
});
// Bank view end

// Bank Delete Start
$(document).on("click", ".delete-bank-action", function (event) {
    event.preventDefault();

    let url = $(this).data("action") ?? $(this).attr("href");
    let method = $(this).data("method") ?? "DELETE";
    let icon = $(this).data("icon") ?? "fas fa-trash-alt";

    $.confirm({
        title: "Are you sure?",
        icon: icon,
        theme: "modern",
        closeIcon: true,
        animation: "scale",
        type: "red",
        buttons: {
            confirm: {
                btnClass: "btn-red",
                action: function () {
                    $.ajax({
                        type: method,
                        url: url,
                        success: function (response) {
                            if (response === false) {
                                $("#warning-modal").modal("show");
                            } else if (response.redirect) {
                                window.sessionStorage.hasPreviousMessage = true;
                                window.sessionStorage.previousMessage =
                                    response.message ?? null;
                                location.href = response.redirect;
                            } else {
                                Notify("success", response.message || response);
                            }
                        },
                        error: function (xhr) {
                            Notify(
                                "error",
                                xhr.responseJSON?.message ||
                                    "Something went wrong!"
                            );
                        },
                    });
                },
            },
            close: {
                action: function () {
                    this.buttons.close.hide();
                },
            },
        },
    });
});
// Bank Delete Start

function loadEmployeeShift(employee_id, shiftSelect) {
    var url = $("#get-shift").val();

    if (employee_id) {
        $.ajax({
            url: url,
            type: "GET",
            data: { employee_id: employee_id },
            success: function (response) {
                var shift = response.data;

                if (shift) {
                    $(shiftSelect).html(
                        `<option value="${shift.id}" selected>${shift.name}</option>`
                    );
                } else {
                    $(shiftSelect).html(
                        '<option value="">No shift found</option>'
                    );
                }
            },
            error: function () {
                $(shiftSelect).html(
                    '<option value="">Please assign a shift to the employee</option>'
                );
            },
        });
    }
}

$(document).ready(function () {
    $(document).on("change", ".get-employee-shift", function () {
        var employee_id = $(this).val();
        var shiftSelect = $(this).closest("form").find(".shift-select");
        loadEmployeeShift(employee_id, shiftSelect);
    });

    // When edit modal is opened
    $(".editModal").on("shown.bs.modal", function () {
        var modal = $(this);
        var employee_id = modal.find(".get-employee-shift").val();
        var shiftSelect = modal.find(".shift-select");

        loadEmployeeShift(employee_id, shiftSelect);
    });
});

function loadEmployeeAmount(employee_id, targetInput) {
    var url = $("#get-empAmount").val();

    if (employee_id) {
        $.ajax({
            url: url,
            type: "GET",
            data: { employee_id: employee_id },
            success: function (response) {
                var emp = response.data;
                targetInput.val(emp.amount); // Set amount to target input
            },
        });
    }
}

$(document).ready(function () {
    $(document).on("change", ".empAmount", function () {
        var employee_id = $(this).val();
        var amountInput = $(this).closest(".modal").find(".amountInput");
        loadEmployeeAmount(employee_id, amountInput);
    });

    // For both create and edit modals
    $(".modal").on("shown.bs.modal", function () {
        var modal = $(this);
        var employee_id = modal.find(".empAmount").val();
        var amountInput = modal.find(".amountInput");
        loadEmployeeAmount(employee_id, amountInput);
    });
});

$(document).ready(function () {
    // Store the original values
    let originalStartBreak = $('input[name="start_break_time"]').val();
    let originalEndBreak = $('input[name="end_break_time"]').val();

    $(".break-status").on("change", function () {
        if ($(this).val() === "yes") {
            $(".start-break-time").removeClass("d-none");
            $(".end-break-time").removeClass("d-none");

            // Restore the original values if empty
            if ($('input[name="start_break_time"]').val() === "") {
                $('input[name="start_break_time"]').val(originalStartBreak);
            }
            if ($('input[name="end_break_time"]').val() === "") {
                $('input[name="end_break_time"]').val(originalEndBreak);
            }
        } else {
            $(".start-break-time").addClass("d-none").find("input").val("");
            $(".end-break-time").addClass("d-none").find("input").val("");
        }
    });

    $(".editShiftModal").on("shown.bs.modal", function () {
        // Re-fetch the original values when modal is shown
        originalStartBreak = $('input[name="start_break_time"]').val();
        originalEndBreak = $('input[name="end_break_time"]').val();

        $(".break-status").trigger("change");
    });
});

function loadEmployeeDepartment(employee_id, departmentSelect) {
    var url = $("#get-department").val();

    if (employee_id) {
        $.ajax({
            url: url,
            type: "GET",
            data: { employee_id: employee_id },
            success: function (response) {
                var department = response.data;

                if (department) {
                    $(departmentSelect).html(
                        `<option value="${department.id}" selected>${department.name}</option>`
                    );
                } else {
                    $(departmentSelect).html(
                        '<option value="">No Department found</option>'
                    );
                }
            },
            error: function () {
                $(departmentSelect).html(
                    '<option value="">Please assign a department to the employee</option>'
                );
            },
        });
    }
}

$(document).ready(function () {
    $(document).on("change", ".get-employee-department", function () {
        var employee_id = $(this).val();
        var departmentSelect = $(this)
            .closest("form")
            .find(".department-select");
        loadEmployeeDepartment(employee_id, departmentSelect);
    });

    // When edit modal is opened
    $(".editModal").on("shown.bs.modal", function () {
        var modal = $(this);
        var employee_id = modal.find(".get-employee-department").val();
        var departmentSelect = modal.find(".department-select");

        loadEmployeeDepartment(employee_id, departmentSelect);
    });
});

//Affiliate view modal
$(".affiliate-view").on("click", function () {
    $("#date").text($(this).data("date"));
    $("#name").text($(this).data("name"));
    $("#email").text($(this).data("email"));
    $("#plan").text($(this).data("plan"));
    $("#duration").text($(this).data("duration"));
    $("#expire_date").text($(this).data("expire-date"));
    $("#total_earn").text($(this).data("total-earn"));
});

// Affiliate approve modal start
$(".affiliate-modal-approve").on("click", function () {
    var url = $(this).data("url");
    $(".affiliateModalApproveForm").attr("action", url);
});

$(document).ready(function () {
    $(".affiliate-modal-approve").on("click", function (e) {
        e.preventDefault();

        $("#payment-view-modal").modal("show");

        var actionUrl = $(this).data("url");
        $("#withdrawal-payment-modal form").attr("action", actionUrl);

        $("#date").text($(this).data("date"));
        $("#name").text($(this).data("name"));
        $("#amount").text($(this).data("amount"));
        $("#status").text($(this).data("status"));
    });

    $("#payment-view-modal .submit-btn").on("click", function () {
        $("#payment-view-modal").modal("hide");

        $("#payment-view-modal").on("hidden.bs.modal", function () {
            $("#withdrawal-payment-modal").modal("show");
            $(this).off("hidden.bs.modal");
        });
    });
});

$(document).ready(function () {
    var hash = window.location.hash;

    if (hash) {
        $(".nav-link.settings-link, .tab-pane").removeClass("active show");

        $('.nav-link.settings-link[data-bs-target="' + hash + '"]').addClass(
            "active"
        );

        $(hash).addClass("active show");
    } else {
        $(".nav-link.settings-link:first").addClass("active");
        $(".tab-pane:first").addClass("active show");
    }
});

// Affiliate approve modal end
document.addEventListener("DOMContentLoaded", function () {
    const customerDropdownElement = document.querySelector(
        "#searchableCustomerDropdown"
    );

    if (customerDropdownElement) {
        const searchableCustomerDropdown = new Choices(
            customerDropdownElement,
            {
                searchEnabled: true,
                itemSelectText: "",
                shouldSort: false,
            }
        );
    }
});

$(".date-type-selector").on("change", function () {
    const target = $(this).data("target");
    const selectedType = $(this).val();

    if (selectedType === "dmy") {
        $("#" + target + "_dmy").show();
        $("#" + target + "_my").hide();
    } else if (selectedType === "my") {
        $("#" + target + "_my").show();
        $("#" + target + "_dmy").hide();
    } else {
        $("#" + target + "_my, #" + target + "_dmy").hide();
    }
});

// Jquery for handling tagify data

let tagifyCreateInstance;
let tagifyEditInstance;

$(document).ready(function () {
    const createInput = document.querySelector(".tagify-values");
    if (createInput) {
        tagifyCreateInstance = new Tagify(createInput);

        $("form.ajaxform_instant_reload")
            .not(".variationUpdateForm")
            .on("submit", function (e) {
                const tagValues = tagifyCreateInstance.value.map(
                    (item) => item.value
                );
                $(this)
                    .find("input[name='values']")
                    .val(JSON.stringify(tagValues));
            });
    }

    $(document).on("click", ".variations-edit-btn", function () {
        const url = $(this).data("url");
        const name = $(this).data("variation-name");
        const values = $(this).data("variation-values");

        $("#variation-name").val(name);
        $(".variationUpdateForm").attr("action", url);

        const editInput = document.querySelector("#edit-variation-values");

        if (tagifyEditInstance) tagifyEditInstance.destroy();

        tagifyEditInstance = new Tagify(editInput);
        tagifyEditInstance.addTags(values);
    });

    $("form.variationUpdateForm").on("submit", function (e) {
        const tagValues = tagifyEditInstance.value.map((item) => item.value);
        $(this).find("input[name='values']").val(JSON.stringify(tagValues));
    });
});

function toggleExpiration() {
    if ($(".otp-status-on").is(":checked")) {
        $(".otp-expiration-field").show();
    } else {
        $(".otp-expiration-field").hide();
    }
}

toggleExpiration();

$('input[name="otp_status"]').on("change", function () {
    toggleExpiration();
});

// multiple select code start ---------->
document.addEventListener("DOMContentLoaded", function () {
    const multipleSelects = document.querySelectorAll(".choices-multiple");
    multipleSelects.forEach((select) => {
        new Choices(select, {
            removeItemButton: true,
        });
    });
});

// create shelf select start
$(document).ready(function () {
    if ($("#shelf-select").length) {
        const shelfChoices = new Choices("#shelf-select", {
            removeItemButton: true,
            searchEnabled: true,
            shouldSort: false,
        });
    }
});
// create shelf select end

$(document).ready(function () {
    // Cache modal and select
    const modal = $("#rack-edit-modal");
    const shelfSelect = modal.find(".shelf-select")[0];

    // Cache all shelves from the select at the start
    const allShelves = [];
    $(shelfSelect)
        .find("option")
        .each(function () {
            allShelves.push({
                id: $(this).val(),
                name: $(this).text(),
            });
        });

    let choicesInstance; // Keep Choices instance reference

    $(".rack-edit-btn").on("click", function () {
        const url = $(this).data("url");
        const rack = $(this).data("rack-name");
        const selectedShelves = JSON.parse($(this).attr("data-shelf-datas")); // array of IDs

        modal.find("#rack_name").val(rack);

        modal.find(".rackUpdateForm").attr("action", url);

        $(shelfSelect).empty();

        // Populate select with all shelves
        allShelves.forEach(function (shelf) {
            const option = $("<option></option>")
                .val(shelf.id)
                .text(shelf.name);
            if (selectedShelves.includes(parseInt(shelf.id))) {
                option.prop("selected", true);
            }
            $(shelfSelect).append(option);
        });

        if (choicesInstance) {
            choicesInstance.destroy();
        }

        choicesInstance = new Choices(shelfSelect, {
            removeItemButton: true,
            searchEnabled: true,
            shouldSort: false,
        });

        modal.modal("show");
    });
});

$(document).ready(function () {
    function loadShelves(rackId, selectedShelfId = null) {
        var shelfSelect = $("#shelf_id");
        var getShelfUrl = $("#getShelfRoute").val();

        shelfSelect
            .prop("disabled", true)
            .html('<option value="">Loading...</option>');

        if (rackId) {
            $.ajax({
                url: getShelfUrl,
                type: "GET",
                data: { rack_id: rackId },
                success: function (data) {
                    shelfSelect.html('<option value="">Select one</option>');
                    $.each(data, function (key, shelf) {
                        var selected =
                            shelf.id == selectedShelfId ? "selected" : "";
                        shelfSelect.append(
                            '<option value="' +
                                shelf.id +
                                '" ' +
                                selected +
                                ">" +
                                shelf.name +
                                "</option>"
                        );
                    });
                    shelfSelect.prop("disabled", false);
                },
            });
        } else {
            shelfSelect.html('<option value="">Select one</option>');
            shelfSelect.prop("disabled", true);
        }
    }

    // When rack changes
    $(document).on("change", "#rack_id", function () {
        loadShelves($(this).val());
    });

    // On edit page load (preselect)
    var rackId = $("#rack_id").val();
    var selectedShelfId = $("#selectedShelfId").val();

    if (rackId) {
        loadShelves(rackId, selectedShelfId);
    }
});

const settingsBtn = document.querySelector(".product-settings");
const popup = document.getElementById("productSettingsPopup");
const closeBtn = document.querySelector(".close-popup");

if (settingsBtn && popup && closeBtn) {
    settingsBtn.addEventListener("click", () => {
        popup.classList.toggle("hidden");
    });

    closeBtn.addEventListener("click", () => {
        popup.classList.add("hidden");
    });

    // Optional: click outside to close
    window.addEventListener("click", function (e) {
        if (!popup.contains(e.target) && !settingsBtn.contains(e.target)) {
            popup.classList.add("hidden");
        }
    });
}

/** MULTIPLE PAYMENT TYPES START **/
function paymentGridTemplate(optionsHtml, index, transaction = null) {
    // Determine type and values for edit
    const type = transaction
        ? transaction.transaction_type === "cash_payment"
            ? "cash"
            : transaction.transaction_type === "cheque_payment"
            ? "cheque"
            : transaction.transaction_type === "wallet_payment"
            ? "wallet"
            : transaction.transaction_type === "bank_payment"
            ? transaction.payment_type_id
            : ""
        : "";

    const amount = transaction ? transaction.amount : 0;
    const chequeNumber = transaction
        ? transaction.meta?.cheque_number ?? ""
        : "";

    return `
    <div class="payment-wrapper mt-2">
        <div class="payment-grid d-flex gap-2 align-items-center">
            <select name="payments[${index}][type]" class="form-select payment-type-select">
                ${optionsHtml}
            </select>
            <input name="payments[${index}][amount]" required class="amount form-control amount-input" type="number" step="any" min="0" value="${amount}" placeholder="Amount">
            <button type="button" class="delete-btn">
                <svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">--}}
               <path d="M15.4519 4.12476L14.9633 11.6436C14.8384 13.5646 14.7761 14.5251 14.2699 15.2157C14.0195 15.5571 13.6974 15.8452 13.3236 16.0618C12.5678 16.4998 11.5561 16.4998 9.53271 16.4998C7.50669 16.4998 6.49366 16.4998 5.73733 16.0609C5.3634 15.844 5.04108 15.5554 4.79088 15.2134C4.28485 14.5217 4.2238 13.5598 4.10171 11.6362L3.625 4.12476" stroke="#C52127" stroke-width="1.25" stroke-linecap="round"></path>
               <path d="M7.1731 8.80115H11.9039" stroke="#C52127" stroke-width="1.25" stroke-linecap="round"></path>
               <path d="M8.35571 11.7405H10.7211" stroke="#C52127" stroke-width="1.25" stroke-linecap="round"></path>
               <path d="M2.44238 4.12524H16.6347M12.7361 4.12524L12.1979 3.06904C11.8404 2.36744 11.6615 2.01663 11.3532 1.79785C11.2848 1.74932 11.2124 1.70615 11.1366 1.66877C10.7951 1.50024 10.3853 1.50024 9.56558 1.50024C8.72532 1.50024 8.30522 1.50024 7.95806 1.67583C7.88112 1.71475 7.8077 1.75967 7.73856 1.81012C7.4266 2.03777 7.25234 2.40141 6.90383 3.12869L6.42627 4.12524" stroke="#C52127" stroke-width="1.25" stroke-linecap="round"></path>
            </svg>
            </button>
        </div>
        <div class="cheque-input mt-2 ${type === "cheque" ? "" : "d-none"}">
            <input type="text" name="payments[${index}][cheque_number]" class="form-control" placeholder="Enter Cheque Number" value="${chequeNumber}" ${
        type === "cheque" ? "" : "disabled"
    }>
        </div>
    </div>
    `;
}

let paymentIndex = 0;

// Preload existing payments
function loadExistingPayments(passedTransactions = null) {
    const $form = $(".payment-main-container").closest("form");
    const $container = $form.find(".payment-main-container");

    const existingPayments =
        passedTransactions || $container.data("existing-transactions") || [];

    $container.empty();

    if (!existingPayments.length) return;

    const $mainSelect = $form.find(".main-select");
    const $receiveAmount = $form.find(".receive_amount");
    const optionsHtml = $mainSelect.html();

    existingPayments.forEach((transaction, index) => {
        let options = optionsHtml;

        if (transaction.transaction_type === "cash_payment") {
            options = options.replace('value="cash"', 'value="cash" selected');
        } else if (transaction.transaction_type === "cheque_payment") {
            const typeLabel = transaction.type ? ` (${transaction.type})` : "";

            // added type
            options = options.replace(
                /(<option\s+value="cheque")(>)([^<]*)/i,
                `$1 selected$2Cheque${typeLabel}`
            );
        } else if (transaction.transaction_type === "wallet_payment") {
            options = options.replace(
                'value="wallet"',
                'value="wallet" selected'
            );
        } else if (transaction.transaction_type === "bank_payment") {
            // For bank payment, match by id
            const regex = new RegExp(
                `value="${transaction.payment_type_id}"`,
                "g"
            );
            options = options.replace(
                regex,
                `value="${transaction.payment_type_id}" selected`
            );
        }

        // Append the payment row
        $container.append(paymentGridTemplate(options, index));

        // Set amount
        $container
            .find(`.payment-wrapper`)
            .last()
            .find(".amount")
            .val(transaction.amount);

        // Show cheque input if transaction is cheque_payment
        if (transaction.transaction_type === "cheque_payment") {
            const $wrapper = $container.find(`.payment-wrapper`).last();
            $wrapper
                .find(".cheque-input")
                .removeClass("d-none")
                .find("input")
                .prop("disabled", false)
                .val(transaction.meta?.cheque_number || "");
        }

        // increment global paymentIndex
        paymentIndex++;
    });

    // hide main select and set receive amount readonly
    if (existingPayments.length) {
        $mainSelect.addClass("d-none");
        $receiveAmount.prop("readonly", true);
    }

    updateTotalReceiveAmount($form);
}

// Only load if container exists
if ($(".payment-main-container").length) {
    $(document).ready(function () {
        loadExistingPayments();
    });
}

// Add Payment Row
$(".add-payment-button").on("click", function () {
    const $form = $(this).closest("form");
    const $mainSelect = $form.find(".main-select");
    const $paymentContainer = $form.find(".payment-main-container");
    const $receiveAmount = $form.find(".receive_amount");
    const optionsHtml = $mainSelect.html();

    // remove existing cheque input
    $form.find(".main-cheque-input").remove();

    // hide main select when in multi-payment mode
    if (!$mainSelect.hasClass("d-none")) {
        $mainSelect.addClass("d-none");
    }

    // receive readonly when multi-payment mode
    $receiveAmount.prop("readonly", true);

    // add new payment row
    $paymentContainer.append(paymentGridTemplate(optionsHtml, paymentIndex));
    paymentIndex++;

    updateTotalReceiveAmount($form);
});

// Remove payment row
$(document).on("click", ".delete-btn", function () {
    const $wrapper = $(this).closest(".payment-wrapper");
    const $form = $wrapper.closest("form");
    const $paymentContainer = $form.find(".payment-main-container");
    const $mainSelect = $form.find(".main-select");
    const $receiveAmount = $form.find(".receive_amount");

    // remove the current row
    $wrapper.remove();

    // restore main select if no payment rows
    if ($paymentContainer.children().length === 0) {
        $mainSelect.removeClass("d-none");
        $receiveAmount.prop("readonly", false);

        // if main select is cheque, show its input
        if ($mainSelect.val() === "cheque") {
            if ($(".main-cheque-input").length === 0) {
                $mainSelect.closest(".p-0").append(`
                    <div class="main-cheque-input mt-2">
                        <input type="text" name="payments[main][cheque_number]" class="form-control" placeholder="Enter Cheque Number">
                    </div>
                `);
            }
        }
    }

    updateTotalReceiveAmount($form);
});

// Show cheque input dynamically
$(document).on("change", ".payment-type-select, .main-select", function () {
    const $this = $(this);
    const selected = $this.val();
    const $form = $this.closest("form");

    if (selected === "cheque") {
        if ($this.hasClass("main-select")) {
            // for main select
            if ($form.find(".main-cheque-input").length === 0) {
                $this.closest(".p-0").append(`
                    <div class="main-cheque-input mt-2">
                        <input type="text" name="payments[main][cheque_number]" class="form-control" placeholder="Enter Cheque Number">
                    </div>
                `);
            }
        } else {
            // for multi-payment row
            const $wrapper = $this.closest(".payment-wrapper");
            $wrapper
                .find(".cheque-input")
                .removeClass("d-none")
                .find("input")
                .prop("disabled", false);
        }
    } else {
        if ($this.hasClass("main-select")) {
            // remove main cheque input if type is not cheque
            $form.find(".main-cheque-input").remove();
        } else {
            const $wrapper = $this.closest(".payment-wrapper");
            $wrapper
                .find(".cheque-input")
                .addClass("d-none")
                .find("input")
                .prop("disabled", true);
        }
    }
});

// Update total receive amount
$(document).on("input", ".amount", function () {
    updateTotalReceiveAmount();
});

function updateTotalReceiveAmount($form = null) {
    if (!$form) $form = $(".payment-main-container").closest("form");

    const $paymentContainer = $form.find(".payment-main-container");
    const $receiveAmount = $form.find(".receive_amount");

    if ($paymentContainer.children().length > 0) {
        let total = 0;
        $paymentContainer.find(".amount").each(function () {
            total += parseFloat($(this).val()) || 0;
        });
        $receiveAmount.val(total.toFixed(2));
    }

    updateDueAmount($form);
}

// Disable hidden inputs before form submit
$("form").on("submit", function () {
    // cheque inputs
    $(".cheque-input input").each(function () {
        $(this).prop("disabled", !$(this).is(":visible"));
    });

    // main select
    $(".main-select").each(function () {
        $(this).prop("disabled", !$(this).is(":visible"));
    });
});

function updateDueAmount($form = null) {
    if (!$form) $form = $(".payment-main-container").closest("form");

    const $dueInput = $form.find("#due_amount");

    //  If due field does not exist
    if (!$dueInput.length) return;

    const totalText = $form.find("#total_amount").text() || 0;
    const totalAmount = parseFloat(totalText.replace(/[^0-9.-]+/g, "")) || 0;

    const paidAmount = parseFloat($form.find(".receive_amount").val()) || 0;

    let due = totalAmount - paidAmount;
    if (due < 0) due = 0;

    $dueInput.val(due.toFixed(2));
}

/** MULTIPLE PAYMENT TYPES END **/

// currency format
function currencyFormat(amount, type = "icon", decimals = 2) {
    let symbol = $("#currency_symbol").val();
    let position = $("#currency_position").val();
    let code = $("#currency_code").val();

    let formatted_amount = formattedAmount(amount, decimals);

    // Apply currency format based on the position and type
    if (type === "icon" || type === "symbol") {
        if (position === "right") {
            return formatted_amount + symbol;
        } else {
            return symbol + formatted_amount;
        }
    } else {
        if (position === "right") {
            return formatted_amount + " " + code;
        } else {
            return code + " " + formatted_amount;
        }
    }
}
// Format the amount
function formattedAmount(amount, decimals) {
    return Number.isInteger(+amount)
        ? parseInt(amount)
        : (+amount).toFixed(decimals);
}

// Insert placeholder at cursor position
function insertAtCursor(field, text) {
    if (document.selection) {
        field.focus();
        let sel = document.selection.createRange();
        sel.text = text;
    } else if (field.selectionStart || field.selectionStart === 0) {
        let startPos = field.selectionStart;
        let endPos = field.selectionEnd;
        field.value =
            field.value.substring(0, startPos) +
            text +
            field.value.substring(endPos, field.value.length);
        field.selectionStart = field.selectionEnd = startPos + text.length;
    } else {
        field.value += text;
    }
}

// When clicking any placeholder button
$(".placeholder-btn").on("click", function () {
    let value = $(this).data("value");
    let elementId = $(this).data("id");
    let textarea = document.getElementById(elementId);
    insertAtCursor(textarea, value);
    textarea.focus();
});

// For gateway type dropdown
$(".gateway-type").on("change", function () {
    $(".removable-field").remove();
    var names = $(this).find("option:selected").data("names");
    var labels = $(this).find("option:selected").data("labels");

    let inputHtml = "";

    $.each(names, function (key, name) {
        var label = labels[key] || "";

        inputHtml += `
            <div class="col-sm-6 mb-2 removable-field">
                <label>${label}</label>
                <input type="text" name="params[${name}]" class="form-control" placeholder="${label}">
            </div>
        `;
    });

    $("#input-container").append(inputHtml);
});

// Due Payment View Start
$(document).on("click", ".view-due-payment-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url");
    let tableBody = $("#view-due-payment-table-data");

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (response) {
            if (response.data.length > 0) {
                let rows = "";

                $.each(response.data, function (index, item) {
                    rows += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.date}</td>
                            <td>${item.receipt_no}</td>
                            <td>${item.amount}</td>
                            <td>${item.payment_type}</td>
                        </tr>
                    `;
                });

                tableBody.html(rows);
            } else {
                tableBody.html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No due payments found
                        </td>
                    </tr>
                `);
            }
        },
        error: function () {
            tableBody.html(`
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Failed to load data
                    </td>
                </tr>
            `);
        },
    });
});
// Due Payment View End

// Common Payment View
$(document).on("click", ".view-payment-btn", function (e) {
    e.preventDefault();

    let url = $(this).data("url");
    let tableBody = $("#view-payment-table-data");
    let baseDeleteRoute = $("#payment-delete-route").data("url");

    tableBody.html(
        `<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>`
    );

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (response) {
            if (response.data && response.data.length > 0) {
                let rows = "";
                $.each(response.data, function (index, item) {
                    // Extract type from the URL (from data-url attribute)
                    let urlParts = url.split("/");
                    let type = urlParts[urlParts.length - 2]; // Get type from URL (second last part)

                    // Replace both :type and :id in the delete route
                    let deleteUrl = baseDeleteRoute
                        .replace(":type", type)
                        .replace(":id", item.id);

                    rows += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.date}</td>
                            <td>${item.receipt_no}</td>
                            <td>${item.amount}</td>
                            <td>${item.payment_type}</td>
                        </tr>`;
                });
                tableBody.html(rows);
            } else {
                tableBody.html(
                    `<tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>`
                );
            }
        },
        error: function () {
            tableBody.html(
                `<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>`
            );
        },
    });
});

// Party wise loss profit view start
$(document).on("click", ".view-party-loss-profit-btn", function (e) {
    e.preventDefault();
    let url = $(this).data("url");

    $.ajax({
        url: url,
        method: "GET",
        success: function (res) {
            let html = "";
            let sl = 1;

            res.sales.forEach(function (sale) {
                sale.details.forEach(function (detail) {
                    let profit = detail.lossProfit > 0 ? detail.lossProfit : 0;
                    let loss =
                        detail.lossProfit < 0 ? Math.abs(detail.lossProfit) : 0;

                    html += `
                        <tr>
                            <td>${sl++}</td>
                            <td class="text-start">${
                                detail.product.productName
                            }</td>
                            <td>${detail.quantities}</td>
                            <td>${currencyFormat(
                                detail.productPurchasePrice
                            )}</td>
                            <td>${currencyFormat(detail.price)}</td>
                            <td class="text-success">${currencyFormat(
                                profit
                            )}</td>
                            <td class="text-danger">${currencyFormat(loss)}</td>
                        </tr>
                    `;
                });
            });

            if (html === "") {
                html =
                    '<tr><td colspan="7" class="text-center text-muted">No Data Found</td></tr>';
            }

            $("#view-party-loss-profit-data").html(html);
        },
        error: function (err) {
            $("#view-party-loss-profit-data").html(
                '<tr><td colspan="7" class="text-center text-danger">Something went wrong!</td></tr>'
            );
        },
    });
});
// Party wise loss profit view end

function loadFilteredData(form) {
    let url = form.attr("action");
    let data = form.serialize();
    let target = form.attr("table");

    $.ajax({
        url: url,
        method: "GET",
        data: data,
        beforeSend: function () {
            $(target).addClass("loading");
        },
        success: function (response) {
            let html = $(response).find(target).html();
            $(target).html(html);
        },
        complete: function () {
            $(target).removeClass("loading");
        },
    });
}

$(document).on("change", ".ajax-filter-form .custom-days", function () {
    let form = $(this).closest("form");
    let value = $(this).val();

    if (value === "custom_date") {
        $(".date-filters").removeClass("d-none");
    } else {
        $(".date-filters").addClass("d-none");
        loadFilteredData(form);
    }
});

$(document).on("change", ".filter-from-date, .filter-to-date", function () {
    let form = $(this).closest("form");
    loadFilteredData(form);
});
