"use strict";
// product img upload code start ---------------->

$(document).ready(function () {
    // Choices.js initializer

    function initChoices($root) {
        $root.find(".tagSelect").each(function () {
            new Choices(this, {
                removeItemButton: true,
                placeholder: true,
                placeholderValue: "Select variations",
            });
        });
    }

    const $container = $("#productTypeContainer");

    // When product type changes
    $("#product-type").on("change", function () {
        loadComponent($(this).val());
    });

    loadComponent($("#product-type").val());

    // Main function
    function loadComponent(type) {
        // Remove existing component from DOM
        $container.empty();

        // Choose template
        let templateHtml;
        if (type === "combo") {
            $(".serial-code-checkbox").addClass("d-none");
            $(".low-stock-input").addClass("d-none");
            templateHtml = $("#comboTemplate").html();
            loadAllProducts();
        } else if (type === "single") {
            $(".serial-code-checkbox").removeClass("d-none");
            $(".low-stock-input").removeClass("d-none");
            templateHtml = $("#singleTemplate").html();
        } else {
            $(".serial-code-checkbox").removeClass("d-none");
            $(".low-stock-input").removeClass("d-none");
            templateHtml = $("#variationTemplate").html();

            setTimeout(() => {
                resetVariantRows(false);
            }, 100);
        }

        // Append to container
        $container.append($(templateHtml));
        if (!$("#previous-stocks").length || $("#previous-stocks").val() === "[]") {
            $('.batch_no').each(function (index) {
                if (!$(this).val()) {
                    $(this).val($('#productCode').val() + '-' + (index + 1));
                }
            });
        }

        // Re-initialize Choices.js for newly inserted selects
        initChoices($container);
        return;
    }

    $(document).on("change", ".tagSelect[name='variation_ids[]']", function () {
        resetVariantRows(true);
    });

    function resetVariantRows(regenerateRows) {
        const selectedVariationIds = $(".variation_ids").val() || [];
        const variationsData = JSON.parse($("#variations-data").val() || "[]");
        const selectedValuesMap = JSON.parse(
            $("#selected-variation-values").val() || "{}"
        );
        const $variationContainer = $(".variation-values-container");

        $variationContainer.empty(); // clear old variation selects

        selectedVariationIds.forEach(function (variationId) {
            const variation = variationsData.find((v) => v.id == variationId);
            if (!variation) return;

            const selectedValues = selectedValuesMap[variation.name] || [];

            const selectHtml = `
                <div class="col-lg-6 mb-2 variation-field" data-variation-id="${
                    variation.id
                }">
                    <div class="order-form-section">
                        <label>${variation.name}</label>
                        <select class="tagSelect variation-value" data-id="${
                            variation.id
                        }" data-name="${variation.name}" multiple>
                            ${variation.values
                                .map(
                                    (v) =>
                                        `<option value="${v}" ${
                                            selectedValues.includes(v)
                                                ? "selected"
                                                : ""
                                        }>${v}</option>`
                                )
                                .join("")}
                        </select>
                    </div>
                </div>
            `;
            $variationContainer.append(selectHtml);
        });

        // Initialize Choices for newly added selects
        initChoices($variationContainer);

        if (regenerateRows) {
            generateVariantRows();
        }
    }

    // When variation values change (Size, Color, etc.)
    $(document).on("change", ".variation-value", function () {
        generateVariantRows();
    });

    let prevVariantName = "";

    // Function to generate all variant combinations and render table rows
    function generateVariantRows() {
        const $tbody = $("#product-data");
        $tbody.empty();

        const canSeePrice = $("#canSeePrice").val() == "1";
        const permissions = JSON.parse($("#permissions-data").val());
        const previousStocks = JSON.parse($("#previous-stocks").val() || "[]");
        const warehouses = JSON.parse($("#warehouses-data").val());

        // Gather all selected variation values
        const variationSets = [];
        $(".variation-value").each(function () {
            const name = $(this).data("name");
            const values = $(this).val() || [];
            if (values.length > 0) {
                variationSets.push({ name, values });
            }
        });

        if (variationSets.length === 0) {
            $tbody.html(`<tr><td class="text-danger" colspan="100%">Please select a variation.</td></tr>`);
            return;
        }

        // Generate all combinations
        const allCombinations = getCombinations(variationSets.map((v) => v.values));

        const vatType = $("#vat_type").val();
        const vatRate = $("#vatRate").val();
        const productCode = $("#productCode").val();
        const hasSerials = $("#has-serial-code-addon").val();

        let index = 0;

        allCombinations.forEach((combo) => {
            const variantName = combo.join(" - ");
            const batch_no = productCode ? productCode + "-" + (index + 1) : "";

            const existingStockRows = previousStocks.filter(stock => stock.variant_name == variantName);
            const rowsToGenerate = existingStockRows.length > 0 ? existingStockRows : [null];

            const variationJson = variationSets.map((set, i) => ({ [set.name]: combo[i] || "" }));

            rowsToGenerate.forEach((stockItem) => {
                const rowId = generateRowId();
                appendStockRow({
                    rowId,
                    hasSerials,
                    variantName,
                    variationJson,
                    stockItem,
                    batch_no,
                    warehouses,
                    permissions,
                    canSeePrice,
                    vatType,
                    vatRate
                });

                index++;
            });

            prevVariantName = "";
        });
    }

    function appendStockRow({
        rowId,
        hasSerials = false,
        variantName,
        variationJson,
        stockItem = null,
        batch_no = "",
        warehouses = [],
        permissions = {},
        canSeePrice = false,
        vatType = "exclusive",
        vatRate = 0
    }) {

        let newRow = `<tr data-row-id="${rowId}">
                        <input type="hidden" name="stocks[${rowId}][variant_name]" value="${variantName}">
                        <input type="hidden" name="stocks[${rowId}][variation_data]" value='${JSON.stringify(variationJson)}'>`;

        newRow += `<td class="${ prevVariantName === variantName ? '' : 'add-row-icon' }">${ prevVariantName === variantName ? '' : '+' }</td>`;

        newRow += `<td class="variant-name">${ prevVariantName === variantName ? arrowSVG : variantName}</td>`;

        if (permissions.show_batch_no) {
            newRow += `<td><input type="text" name="stocks[${rowId}][batch_no]" class="form-control form-control-sm custom-table-input batch_no" placeholder="25632" value="${stockItem?.batch_no ?? batch_no}"></td>`;
        }

        if (permissions.show_warehouse) {
            let warehouseOptions = '<option value="">Select</option>';
            warehouses.forEach(function (wh) {
                warehouseOptions += `<option ${stockItem?.warehouse_id == wh.id ? "selected" : ""} value="${wh.id}">${wh.name}</option>`;
            });
            newRow += `<td><select name="stocks[${rowId}][warehouse_id]" class="form-control table-select w-100 role">${warehouseOptions}</select></td>`;
        }

        let needSerial = $(".imei-serial").is(":checked");

        if (hasSerials == 1) {
            newRow += serialIconHtml(needSerial);
        }

        if (permissions.show_product_stock) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productStock]" class="form-control form-control-sm custom-table-input productStock" placeholder="0" ${needSerial ? "readonly" : ""} value="${stockItem?.productStock ?? 0}"></td>`;
        }

        if (canSeePrice) {
            if (permissions.show_exclusive_price) {
                const price = stockItem?.productPurchasePrice ?? 0;
                newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][exclusive_price]" class="form-control form-control-sm custom-table-input exclusive_price" placeholder="Ex: 50" value="${vatType == 'exclusive' ? price : price - ((price * (vatRate ?? 0)) / (100 + (vatRate ?? 0)))}"></td>`;
            }

            if (permissions.show_inclusive_price) {
                newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][inclusive_price]" class="form-control form-control-sm custom-table-input inclusive_price" placeholder="Ex: 50" value="${stockItem?.productPurchasePrice ?? 0}"></td>`;
            }

            if (permissions.show_profit_percent) {
                newRow += `<td><input type="number" step="any" name="stocks[${rowId}][profit_percent]" class="form-control form-control-sm custom-table-input profit_percent" placeholder="25%" value="${stockItem?.profit_percent ?? 0}"></td>`;
            }
        }

        if (permissions.show_product_sale_price) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productSalePrice]" class="form-control form-control-sm custom-table-input productSalePrice" placeholder="Ex: 200" value="${stockItem?.productSalePrice ?? 0}"></td>`;
        }

        if (permissions.show_product_wholesale_price) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productWholeSalePrice]" class="form-control form-control-sm custom-table-input" placeholder="Ex: 200" value="${stockItem?.productWholeSalePrice ?? 0}"></td>`;
        }

        if (permissions.show_product_dealer_price) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productDealerPrice]" class="form-control form-control-sm custom-table-input" placeholder="Ex: 200" value="${stockItem?.productDealerPrice ?? 0}"></td>`;
        }

        if (permissions.show_mfg_date) {
            newRow += `<td><input type="date" name="stocks[${rowId}][mfg_date]" class="form-control" value="${stockItem?.mfg_date ?? ""}"></td>`;
        }

        if (permissions.show_expire_date) {
            newRow += `<td><input type="date" name="stocks[${rowId}][expire_date]" class="form-control" value="${stockItem?.expire_date ?? ""}"></td>`;
        }

        if (prevVariantName == variantName) {
            newRow += `<td class="delete-row">${deleteSVG}</td>`;
        }

        newRow += `</tr>`;
        prevVariantName = variantName;
        $("#product-data").append($(newRow));
    }

    $(document).on("click", ".add-row-icon", function () {
        const $currentRow = $(this).closest("tr");
        const oldRowID = $currentRow.attr("data-row-id");
        const $clonedRow = $currentRow.clone(false, false);

        // Assign new unique row ID
        const newRowID = generateRowId(); // Unique value
        $clonedRow.attr("data-row-id", newRowID);

        // Update serial button to use new ID
        $clonedRow.find(".serial-cell-button").attr("data-row-id", newRowID);

        // Visual adjustment
        $clonedRow.find(".add-row-icon").text("");
        $clonedRow.find(".add-row-icon").removeClass("add-row-icon");
        $clonedRow.find(".variant-name").html(arrowSVG);
        $clonedRow.find(".batch_no").val('');

        // Add delete button
        $clonedRow.append(`<td class="delete-row">${deleteSVG}</td>`);

        // Update all input names with newRowID
        $clonedRow.find("input, select, textarea").each(function () {
            const currentName = $(this).attr("name");
            if (currentName) {
                const updatedName = currentName.replace(oldRowID, newRowID);
                $(this).attr("name", updatedName);
            }
        });

        // Insert after parent
        $currentRow.after($clonedRow);

        // reGenerateBatchNo();
    });

    $(document).on("click", ".delete-row", function () {
        let deletedRow = $(this).closest("tr");
        let deletedRowId = deletedRow.data("row-id");
        deletedRow.remove();
        clearAllSerials(deletedRowId);
        // reGenerateBatchNo();
    });

    // function reGenerateBatchNo() {
    //     let productCode = ($('#productCode').val()).toString();
    //     $('.variation-table tr').each(function (i) {
    //         let batch_no = productCode + '-' + i;
    //         $(this).find('.batch_no').val(batch_no);
    //     });
    // }

    // Helper: generate combinations of variation values
    function getCombinations(arrays) {
        if (arrays.length === 0) return [];
        if (arrays.length === 1) return arrays[0].map((v) => [v]);

        const result = [];
        const allCasesOf = (arrays, prefix = []) => {
            const first = arrays[0];
            const rest = arrays.slice(1);
            for (let value of first) {
                const newPrefix = [...prefix, value];
                if (rest.length === 0) {
                    result.push(newPrefix);
                } else {
                    allCasesOf(rest, newPrefix);
                }
            }
        };
        allCasesOf(arrays);
        return result;
    }

    $(".upload-box").each(function () {
        if ($(this).find(".preview-wrapper").length > 0) {
            $(this).find("svg").first().hide();
            $(this).find(".upload-text").hide();
        }
    });
});

$(document).on("change", ".handle-image-Upload", function (e) {
    const input = this;
    const file = input.files[0];
    if (!file) return;

    const $box = $(input).closest(".upload-box");

    $box.find("svg").first().hide();
    $box.find(".upload-text").hide();

    $box.find(".preview-wrapper").remove();

    const previewURL = URL.createObjectURL(file);
    const previewHTML = `
        <div class="preview-wrapper">
            <img class="preview-img" src="${previewURL}">
            <span class="close-icon">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4L4 12" stroke="#C52127" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4 4L12 12" stroke="#C52127" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
    `;

    $box.append(previewHTML).addClass("uploaded");
});

$(document).on("click", ".close-icon", function () {
    const $box = $(this).closest(".upload-box");
    $box.find(".preview-wrapper").remove();
    $box.find("input[type='file']").val("");
    $box.find("svg").first().show();
    $box.find(".upload-text").show();

    $box.removeClass("uploaded");
});

// product img upload code end---------------->

const arrowSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:20px;height:20px;"><path d="M409.4 57.4C421.9 44.9 442.2 44.9 454.7 57.4L534.7 137.4C547.2 149.9 547.2 170.2 534.7 182.7L454.7 262.7C442.2 275.2 421.9 275.2 409.4 262.7C396.9 250.2 396.9 229.9 409.4 217.4L434.7 192L224 192C188.7 192 160 220.7 160 256L160 288C160 305.7 145.7 320 128 320C110.3 320 96 305.7 96 288L96 256C96 185.3 153.3 128 224 128L434.7 128L409.3 102.6C396.8 90.1 396.8 69.8 409.3 57.3zM313.4 313.4C325.9 300.9 346.2 300.9 358.7 313.4L438.7 393.4C451.2 405.9 451.2 426.2 438.7 438.7L358.7 518.7C346.2 531.2 325.9 531.2 313.4 518.7C300.9 506.2 300.9 485.9 313.4 473.4L338.7 448L192 448C174.3 448 160 462.3 160 480L160 512C160 529.7 145.7 544 128 544C110.3 544 96 529.7 96 512L96 480C96 427 139 384 192 384L338.7 384L313.3 358.6C300.8 346.1 300.8 325.8 313.3 313.3z"/></svg>`;
const deleteSVG = `<svg style="width:20px;height:20px;cursor:pointer;" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.625 4.125L14.1602 11.6438C14.0414 13.5648 13.9821 14.5253 13.5006 15.2159C13.2625 15.5573 12.956 15.8455 12.6005 16.062C11.8816 16.5 10.9192 16.5 8.99452 16.5C7.06734 16.5 6.10372 16.5 5.38429 16.0612C5.0286 15.8443 4.722 15.5556 4.48401 15.2136C4.00266 14.5219 3.94459 13.5601 3.82846 11.6364L3.375 4.125" stroke="#C62828" stroke-width="1.2" stroke-linecap="round"/><path d="M6.75 8.80078H11.25" stroke="#C62828" stroke-width="1.2" stroke-linecap="round"/><path d="M7.875 11.7422H10.125" stroke="#C62828" stroke-width="1.2" stroke-linecap="round"/><path d="M2.25 4.125H15.75M12.0416 4.125L11.5297 3.0688C11.1896 2.3672 11.0195 2.01639 10.7261 1.79761C10.6611 1.74908 10.5922 1.7059 10.5201 1.66852C10.1953 1.5 9.80542 1.5 9.02572 1.5C8.22645 1.5 7.82685 1.5 7.49662 1.67559C7.42343 1.71451 7.35359 1.75943 7.28783 1.80988C6.99109 2.03753 6.82533 2.40116 6.49381 3.12844L6.03955 4.125" stroke="#C62828" stroke-width="1.2" stroke-linecap="round"/></svg>`;

function generateRowId() {
    return 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
}

function serialIconHtml(needSerial) {
    return `<td class="serial-cell ${ needSerial ? "" : "d-none" } serial-option">
                <button type="button" class="serial-cell-button" data-bs-toggle="modal" data-bs-target="#serialModal">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 7H18" stroke="#C52127" stroke-width="2" stroke-linecap="round"/>
                        <path d="M23 7H23.0105" stroke="#C52127" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 14H23.0105" stroke="#C52127" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21H23.0105" stroke="#C52127" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 14H18" stroke="#C52127" stroke-width="2" stroke-linecap="round"/>
                        <path d="M4 21H18" stroke="#C52127" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </td>`
}

$(document).on("click", ".add-variant-btn", function (e) {
    e.preventDefault();

    var canSeePrice = $("#canSeePrice").val() == "1";
    const permissions = JSON.parse($("#permissions-data").val());
    const warehouses = JSON.parse(
        document.getElementById("warehouses-data").value
    );

    let rowId = generateRowId();
    let newRow = "<tr data-row-id='" + rowId + "'>";

    if (permissions.show_batch_no) {
        newRow += `<td><input type="text" name="stocks[${rowId}][batch_no]" class="form-control form-control-sm custom-table-input" placeholder="25632" value="${$('#productCode').val() + "-" + ($(".single-product-table tbody tr").length + 1)}"></td>`;
    }

    if (permissions.show_warehouse) {
        let warehouseOptions = '<option value="">Select</option>';
        warehouses.forEach(function (wh) {
            warehouseOptions += `<option value="${wh.id}">${wh.name}</option>`;
        });

        newRow += `<td>
            <select name="stocks[${rowId}][warehouse_id]" class="form-control table-select w-100 role">
                ${warehouseOptions}
            </select>
            <span></span>
        </td>`;
    }

    let needSerial = $(".imei-serial").is(":checked");
    if ($("#has-serial-code-addon").val() == 1) {
        newRow += serialIconHtml(needSerial);
    }

    if (permissions.show_product_stock) {
        newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productStock]" class="form-control form-control-sm custom-table-input productStock" placeholder="0" ${needSerial ? "readonly" : ""}></td>`;
    }

    if (canSeePrice) {
        if (permissions.show_exclusive_price) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][exclusive_price]" class="form-control form-control-sm custom-table-input exclusive_price" placeholder="Ex: 50"></td>`;
        }
        if (permissions.show_inclusive_price) {
            newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][inclusive_price]" class="form-control form-control-sm custom-table-input inclusive_price" placeholder="Ex: 50"></td>`;
        }
        if (permissions.show_profit_percent) {
            newRow += `<td><input type="number" step="any" name="stocks[${rowId}][profit_percent]" class="form-control form-control-sm custom-table-input profit_percent" placeholder="25%"></td>`;
        }
    }

    if (permissions.show_product_sale_price) {
        newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productSalePrice]" class="form-control form-control-sm custom-table-input productSalePrice" placeholder="Ex: 200"></td>`;
    }

    if (permissions.show_product_wholesale_price) {
        newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productWholeSalePrice]" class="form-control form-control-sm custom-table-input" placeholder="Ex: 200"></td>`;
    }

    if (permissions.show_product_dealer_price) {
        newRow += `<td><input type="number" step="any" min="0" name="stocks[${rowId}][productDealerPrice]" class="form-control form-control-sm custom-table-input" placeholder="Ex: 200"></td>`;
    }

    if (permissions.show_mfg_date) {
        newRow += `<td><input type="date" name="stocks[${rowId}][mfg_date]" class="form-control"></td>`;
    }

    if (permissions.show_expire_date) {
        newRow += `<td><input type="date" name="stocks[${rowId}][expire_date]" class="form-control"></td>`;
    }

    if (permissions.show_action) {
        newRow += `
        <td>
            <a href="#" class="text-danger remove-row">
                <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 5.5L19.3803 15.5251C19.2219 18.0864 19.1428 19.3671 18.5008 20.2879C18.1833 20.7431 17.7747 21.1273 17.3007 21.416C16.3421 22 15.059 22 12.4927 22C9.92312 22 8.6383 22 7.67905 21.4149C7.2048 21.1257 6.796 20.7408 6.47868 20.2848C5.83688 19.3626 5.75945 18.0801 5.60461 15.5152L5 5.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                    <path d="M9.5 11.7344H15.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                    <path d="M11 15.6543H14" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                    <path d="M3.5 5.5H21.5M16.5555 5.5L15.8729 4.09173C15.4194 3.15626 15.1926 2.68852 14.8015 2.39681C14.7148 2.3321 14.6229 2.27454 14.5268 2.2247C14.0937 2 13.5739 2 12.5343 2C11.4686 2 10.9358 2 10.4955 2.23412C10.3979 2.28601 10.3048 2.3459 10.2171 2.41317C9.82145 2.7167 9.60044 3.20155 9.15842 4.17126L8.55273 5.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                </svg>
            </a>
        </td>
        `;
    }

    newRow += "</tr>";

    $("#product-data").append(newRow);
});

// Get VAT rate
function getVatRate() {
    return (
        parseFloat($("#vat_id").find("option:selected").data("vat_rate")) || 0
    );
}

// Get profit calculation type (markup/margin)
function getProfitOption() {
    return $("#profit_option").val();
}

// Update inclusive_price field based on VAT
function updateInclusiveFromExclusive($row) {
    const vatRate = getVatRate();
    const vatType = $("#vat_type").val();

    const exclusiveInput = $row.find(".exclusive_price");
    const inclusiveInput = $row.find(".inclusive_price");

    let exclusive = parseFloat(exclusiveInput.val()) || 0;

    // inclusive = exclusive + VAT%
    if (vatType && vatRate) {
        inclusiveInput.val(
            (exclusive + (exclusive * vatRate) / 100).toFixed(2)
        );
    } else {
        inclusiveInput.val(exclusive.toFixed(2));
    }
}

// Calculate MRP from cost and profit
function calculateMrpRow($row) {
    const vatRate = getVatRate();
    const vatType = $("#vat_type").val();
    const profitOption = getProfitOption();

    const costInput = $row.find(".exclusive_price");
    const profitInput = $row.find(".profit_percent");
    const saleInput = $row.find(".productSalePrice");

    let cost = parseFloat(costInput.val()) || 0;
    let profit = parseFloat(profitInput.val()) || 0;

    updateInclusiveFromExclusive($row);

    if (cost > 0) {
        let basePrice = cost;

        if (vatType === "inclusive") {
            basePrice += (cost * vatRate) / 100;
        }

        let mrp = 0;
        if (profitOption === "margin") {
            mrp = basePrice / (1 - profit / 100);
        } else {
            mrp = basePrice + (basePrice * profit) / 100;
        }

        saleInput.val(mrp.toFixed(2));
    }
}

// Calculate profit from MRP
function calculateProfitFromMrp($row) {
    const vatRate = getVatRate();
    const vatType = $("#vat_type").val();
    const profitOption = getProfitOption();

    const costInput = $row.find(".exclusive_price");
    const profitInput = $row.find(".profit_percent");
    const saleInput = $row.find(".productSalePrice");

    let cost = parseFloat(costInput.val()) || 0;
    let mrp = parseFloat(saleInput.val()) || 0;

    updateInclusiveFromExclusive($row);

    if (cost > 0 && mrp > 0) {
        let basePrice = cost;

        if (vatType === "inclusive") {
            basePrice += (cost * vatRate) / 100;
        }

        let profit = 0;
        if (profitOption === "margin") {
            profit = ((mrp - basePrice) / mrp) * 100;
        } else {
            profit = ((mrp - basePrice) / basePrice) * 100;
        }

        profitInput.val(profit.toFixed(2));
    }
}

function updateExclusiveFromInclusive($row) {
    const vatRate = getVatRate();

    const inclusiveInput = $row.find(".inclusive_price");
    const exclusiveInput = $row.find(".exclusive_price");

    let inclusive = parseFloat(inclusiveInput.val()) || 0;

    // Reverse VAT: exclusive = inclusive / (1 + VAT%)
    let exclusive = inclusive / (1 + vatRate / 100);
    exclusiveInput.val(exclusive.toFixed(2));

    // Recalculate MRP and profit
    calculateMrpRow($row);
}

// Bind events for real-time calculation
function bindMrpCalculation() {
    $(document).on(
        "input change",
        ".exclusive_price, .profit_percent",
        function () {
            const $row = $(this).closest("tr").length
                ? $(this).closest("tr")
                : $(this).closest(".row");
            calculateMrpRow($row);
        }
    );

    $(document).on("input change", ".productSalePrice", function () {
        const $row = $(this).closest("tr").length
            ? $(this).closest("tr")
            : $(this).closest(".row");
        calculateProfitFromMrp($row);
    });

    $("#vat_id, #vat_type").on("change", function () {
        $(".exclusive_price").each(function () {
            const $row = $(this).closest("tr").length
                ? $(this).closest("tr")
                : $(this).closest(".row");
            calculateMrpRow($row);
        });
    });

    // On inclusive price change, update exclusive and MRP after edit
    $(document).on("blur", ".inclusive_price", function () {
        const $row = $(this).closest("tr").length
            ? $(this).closest("tr")
            : $(this).closest(".row");
        updateExclusiveFromInclusive($row);
    });
}

bindMrpCalculation();

/** INVENTORY SALE START **/

// Safely builds asset URLs
function assetPath(path) {
    const baseUrl = $("#asset_base_url").val() || "";
    return `${baseUrl.replace(/\/$/, "")}/${(path || "").replace(/^\/+/, "")}`;
}

// Render product dropdown list with batch-level stock/pricing info
function populateProducts(products = []) {
    const $dropdownList = $("#dropdownList");
    $dropdownList.empty();

    if (!Array.isArray(products) || products.length === 0) {
        $dropdownList.html(
            '<div class="product-option-item">No products available</div>'
        );
        return;
    }

    const html = products
        .map((product) => {
            const imageUrl = assetPath(
                product.productPicture ?? "assets/images/products/box.svg"
            );

            let batchesHtml = "";
            if (Array.isArray(product.stocks) && product.stocks.length > 0) {
                batchesHtml = product.stocks
                    .map((stock) => {
                        const warehouseText = stock.warehouse?.name
                            ? `, Warehouse: ${stock.warehouse.name}`
                            : "";

                        return `
                            <div class="d-flex align-items-center justify-content-between w-100 multi-items add-batch-item"
                                data-stock_id="${stock.id}"
                                data-batch_no="${stock.batch_no}"
                                data-product_id="${product.id}"
                                data-product_name="${product.productName}"
                                data-product_code="${product.productCode}"
                                data-product_unit_name="${
                                    product.unit?.unitName ?? ""
                                }"
                                data-purchase_price="${
                                    stock.productPurchasePrice
                                }">
                                <div class="product-des">
                                    Batch: ${stock.batch_no ?? "N/A"}
                                    ${
                                        product.color
                                            ? ", Color: " + product.color
                                            : ""
                                    }
                                    ${warehouseText}
                                    <span class="product-in-stock">In Stock: ${
                                        stock.productStock ?? 0
                                    }</span>
                                </div>
                                <div class="product-price product_price">
                                    ${currencyFormat(stock.productSalePrice)}
                                </div>
                            </div>`;
                    })
                    .join("");
            } else {
                batchesHtml = `<div class="text-muted small">No batch info available</div>`;
            }

            return `
                <div class="product-option-item single-product"
                    data-product_id="${product.id}"
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
                                <p>Code: ${product.productCode}</p>
                            </div>
                            ${batchesHtml}
                        </div>
                    </div>
                </div>`;
        })
        .join("");

    $dropdownList.html(html);
}

// Load all products initially
function loadAllProducts() {
    const allProductsUrl = $("#all-products").val();
    $.get(allProductsUrl)
        .done(populateProducts)
        .fail(() =>
            $("#dropdownList").html(
                '<div class="product-option-item text-danger">Failed to load products</div>'
            )
        );
}

// Delegated event: toggle dropdown (works for dynamic content)
$(document).on("click", "#productDropdown .product-selected", function (e) {
    e.stopPropagation();

    const $dropdown = $("#productDropdown").find("#dropdownList");
    const $searchContainer = $("#productDropdown").find("#searchContainer");
    const $arrow = $("#productDropdown").find("#arrow");

    $dropdown.toggle();
    $searchContainer.toggleClass("hidden");
    $arrow.toggleClass("product-rotate");
});

// Add to cart on batch click
$(document).on("click", "#dropdownList .add-batch-item", function (e) {
    e.stopPropagation();

    const $item = $(this);
    const $selectedText = $("#selectedValue");
    const $selectedValueInput = $("#selectedProductValue");

    const productName = $item.data("product_name") || "Unknown Product";
    $selectedText.text(productName);
    $selectedValueInput.val($item.data("product_stock_id") || "");

    $("#dropdownList").hide();
    $("#searchContainer").addClass("hidden");
    $("#arrow").removeClass("product-rotate");
    addItem($item);
});

// Close dropdown and search if clicked outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#productDropdown").length) {
        $("#dropdownList").hide();
        $("#searchContainer").addClass("hidden");
        $("#arrow").removeClass("product-rotate");
    }
});

// Product search with debounce
let searchTimeout;
$(document).on("keyup", "#productSearch", function () {
    clearTimeout(searchTimeout);
    const searchTerm = $(this).val().toLowerCase();

    searchTimeout = setTimeout(() => {
        $("#dropdownList .single-product").each(function () {
            const $this = $(this);
            const name = ($this.data("product_name") || "").toLowerCase();
            const code = ($this.data("product_code") || "").toLowerCase();
            $this.toggle(
                name.includes(searchTerm) || code.includes(searchTerm)
            );
        });
    }, 150);
});

function addItem(element) {
    let product_id = element.data("product_id");
    let product_name = element.data("product_name");
    let product_code = element.data("product_code");
    let stock_id = element.data("stock_id");
    let product_unit_name = element.data("product_unit_name");
    let purchase_price = parseFloat(element.data("purchase_price")) || 0;
    let batch_no = element.data("batch_no") || "N/A"; // Handle null batch

    let $tableBody = $("#combo-product-rows");

    // 🔍 Check if same product & same batch already exists
    let $existingRow = $tableBody.find(
        `.combo-row[data-product-id="${product_id}"][data-batch-no="${batch_no}"]`
    );

    if ($existingRow.length) {
        // 🔁 If exists, increase quantity instead of adding new row
        let $qtyInput = $existingRow.find(".quantity");
        let currentQty = parseFloat($qtyInput.val()) || 0;
        $qtyInput.val(currentQty + 1);
        updateComboTotal();
        return;
    }

    // 🧩 Create new row if not exists
    let newRow = `
        <tr class="combo-row" data-product-id="${product_id}" data-batch-no="${batch_no}">
            <td class="text-start products-name">
                <h6>${product_name}</h6>
                <p>Code: ${product_code}, Batch No: ${batch_no}</p>
                <input type="hidden" name="combo_products[${product_id}][stock_id]" value="${stock_id}">
            </td>

            <td>
                <input type="number" step="any" name="combo_products[${product_id}][quantity]" class="form-control form-control-sm custom-table-input quantity" value="1">
            </td>

            <td>${product_unit_name}</td>

            <td>
                <input type="number" step="any" class="form-control form-control-sm custom-table-input purchase_price" name="combo_products[${product_id}][purchase_price]" value="${purchase_price}" placeholder="Ex: 50">
            </td>

            <td class="sub-total">${purchase_price.toFixed(2)}</td>

            <td class="text-center">
                <a href="javascript:void(0)" class="remove-combo-item" data-id="${product_id}" data-batch="${batch_no}">
                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 5.5L19.3803 15.5251C19.2219 18.0864 19.1428 19.3671 18.5008 20.2879C18.1833 20.7431 17.7747 21.1273 17.3007 21.416C16.3421 22 15.059 22 12.4927 22C9.92312 22 8.6383 22 7.67905 21.4149C7.2048 21.1257 6.796 20.7408 6.47868 20.2848C5.83688 19.3626 5.75945 18.0801 5.60461 15.5152L5 5.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                        <path d="M9.5 11.7344H15.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                        <path d="M11 15.6543H14" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                        <path d="M3.5 5.5H21.5M16.5555 5.5L15.8729 4.09173C15.4194 3.15626 15.1926 2.68852 14.8015 2.39681C14.7148 2.3321 14.6229 2.27454 14.5268 2.2247C14.0937 2 13.5739 2 12.5343 2C11.4686 2 10.9358 2 10.4955 2.23412C10.3979 2.28601 10.3048 2.3459 10.2171 2.41317C9.82145 2.7167 9.60044 3.20155 9.15842 4.17126L8.55273 5.5" stroke="#FF3B30" stroke-width="1.25" stroke-linecap="round"/>
                    </svg>
                </a>
            </td>
        </tr>
    `;

    // Append new row
    let $totalRow = $tableBody.find(".total-row");
    if ($totalRow.length) {
        $(newRow).insertBefore($totalRow);
    } else {
        $tableBody.append(newRow);
    }

    updateComboTotal();
}

// Update totals
function updateComboTotal() {
    let total = 0;

    $("#combo-product-rows .combo-row").each(function () {
        let qty = parseFloat($(this).find(".quantity").val()) || 0;
        let price = parseFloat($(this).find(".purchase_price").val()) || 0;
        let rowTotal = qty * price;

        $(this).find(".sub-total").text(rowTotal.toFixed(2));
        total += rowTotal;
    });

    let $tbody = $("#combo-product-rows");
    $tbody.find(".total-row").remove();
    $tbody.append(`
        <tr class="total-row">
            <td class="text-end" colspan="4"><h2 class="total-amount">Net Total Amount: </h2></td>
            <td colspan="2"><h2 class="total-amount">$${total.toFixed(
                2
            )}</h2></td>
        </tr>
    `);

    calculateSellAmount(total);
}

// Update total live
$(document).on(
    "input",
    ".quantity, .purchase_price, #profit_percent, #vat_type, #vat_id",
    function () {
        if ($("#product-type").val() == "combo") {
            updateComboTotal();
        }
    }
);

// Remove row
$(document).on("click", ".remove-combo-item", function () {
    $(this).closest("tr").remove();
    updateComboTotal();
});

function calculateSellAmount(total) {
    let vat_type = $("#vat_type").val();
    let vat_percent =
        parseFloat($("#vat_id").find("option:selected").data("vat_rate")) || 0;
    let vat_amount = 0;
    if (vat_type == "inclusive") {
        vat_amount = (total * vat_percent) / 100;
    }

    let total_amount = total + vat_amount;
    let profit_percent = parseFloat($("#profit_percent").val()) || 0;
    let profit_amount = (total_amount * profit_percent) / 100;
    let grand_total = total_amount + profit_amount;

    $("#productSalePrice").val(grand_total.toFixed(2));
}

$("#serial").on("change", function () {
    if ($(this).is(":checked")) {
        $(".serial-option").removeClass("d-none");
        $(".productStock").attr("readonly", true);
    } else {
        $(".serial-option").addClass("d-none");
        $(".productStock").attr("readonly", false);
    }
});



/** INVENTORY SALE END **/

// SERIAL CODES START FROM HERE
let currentRowId = 0;

// When clicking the serial icon on a row
$(document).on("click", ".serial-cell-button", function () {
    $(".serial-input").val("");
    $('#serialModal').modal("show");
    currentRowId = $(this).closest("tr").data("row-id");
    loadSerials();
});

// Save/Add serial
$(document).on("click", "#saveSerialBtn", function () {
    const $input = $(".serial-input");
    const serial = $.trim($input.val());
    $input.val("");

    if (!serial) return;

    let existingSerials = getAllSerials();
    if (existingSerials.includes(serial)) {
        Notify("error", null, "Serial already exists");
        return;
    }

    const input = $(`<input type="hidden" name="stocks[${currentRowId}][serial_numbers][]" value="${serial}">`);
    $("#serial-inputs").append(input);

    appendSerial(serial);

    updateSerialCount();
});

// Get serials from hidden inputs
function getAllSerials() {
    let serials = [];

    $(`input[name="stocks[${currentRowId}][serial_numbers][]"]`).each(function () {
        serials.push($(this).val());
    });

    return serials;
}

// Append a serial inside modal + hidden input
function appendSerial(serial) {
    const $item = $(`
        <div class="serial-item" data-rowId="${currentRowId}">
            <span>${serial}</span>
            <span class="serial-remove-btn">&times;</span>
        </div>
    `);

    $("#serialList").append($item);
}

// Load serials for current row (when modal opens)
function loadSerials() {
    $("#serialList").html("");
    let serials = getAllSerials();

    serials.forEach(serial => {
        appendSerial(serial);
    });

    updateSerialCount();
}

// Update stock count in the row
function updateSerialCount() {
    const count = $(`input[name="stocks[${currentRowId}][serial_numbers][]"]`).length;
    $(`#enteredCount`).text(count);
    $(`tr[data-row-id="${currentRowId}"]`).find('.productStock').val(count);
}

// Remove serial
$(document).on("click", ".serial-remove-btn", function () {
    const $item = $(this).closest(".serial-item");
    const serial = $item.find("span").first().text();
    const rowId = $item.data("rowid");

    $(`input[name="stocks[${rowId}][serial_numbers][]"][value="${serial}"]`).remove();
    $item.remove();

    updateSerialCount();
});

$(document).on("click", ".remove-row", function (e) {
    e.preventDefault();
    const rowId = $(this).closest("tr").data('row-id');
    $(`input[name="stocks[${rowId}][serial_numbers][]"]`).remove();
    $('.' + rowId).remove();
    $(this).closest("tr").remove();
});

$(document).on('click', '.remove-all-serial', function() {
    clearAllSerials(currentRowId);
});

function clearAllSerials(clearRowId) {
    $(`input[name="stocks[${clearRowId}][serial_numbers][]"]`).remove();
    $(`[data-rowid="${clearRowId}"]`).remove();
    updateSerialCount();
}

$(document).on('keydown', function (e) {
    if (e.key === "Enter") {
        if ($('#serialModal').is(':visible')) {
            e.preventDefault();
            $('#serialModal').find('#saveSerialBtn').trigger('click');
        }
    }
});

$(document).ready(function () {
    // If edit page already has combo rows, calculate once
    if ($("#combo-product-rows .combo-row").length) {
        updateComboTotal();
    }
});
