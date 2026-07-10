"use strict";

/** confirm modal start */
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

$(document).on("click", ".confirm-action", function (event) {
    event.preventDefault();
    let url = $(this).data("action") ?? $(this).attr("href");
    let method = $(this).data("method") ?? "POST";
    let icon = $(this).data("icon") ?? "fas fa-warning";

    $.confirm({
        title: "Are you sure?",
        icon: icon,
        theme: "modern",
        closeIcon: true,
        animation: "scale",
        type: "red",
        scrollToPreviousElement: false,
        scrollToPreviousElementAnimate: false,
        buttons: {
            confirm: {
                btnClass: "btn-red",
                action: function () {
                    event.preventDefault();
                    $.ajax({
                        type: method,
                        url: url,
                        success: function (response) {
                            if (response.redirect) {
                                window.sessionStorage.hasPreviousMessage = true;
                                window.sessionStorage.previousMessage =
                                    response.message ?? null;

                                location.href = response.redirect;
                            } else {
                                Notify("success", response);
                            }
                        },
                        error: function (xhr, status, error) {
                            Notify("error", xhr);
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

/**confirm modal end */

$('.filter-form').on('input', function (e) {
    e.preventDefault();

    let $form = $(this);
    let table = $form.attr('table');
    let method = $form.attr('method') || "GET";
    let url = $form.attr('action') + '?' + $form.serialize();

    $.ajax({
        type: method,
        url: url,
        dataType: "json",
        success: function (res) {
            $(table).html(res.data);
        }
    });
});


/** filter all from  end */

/** DELETE ACTION */
$(document).on("click", ".delete-confirm", function (e) {
    e.preventDefault();
    let t = $(this),
        o = t.attr("href") ?? t.data("action"),
        i = t.html();

    // Create modal dynamically
    let confirmationModal = `
        <div class="modal fade" id="delete-confirmation-modal" tabindex="-1" aria-labelledby="delete-confirmation-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="text-end">
                        <button type="button" class="btn-close m-3 mb-0" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="delete-modal text-center mb-lg-4">
                            <h5>Are You Sure?</h5>
                            <p>You won't be able to revert this!</p>
                        </div>
                         <div class="d-flex justify-content-center">
                            <div class="button-group">
                                <button class="btn reset-btn border rounded" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn theme-btn border rounded delete-confirmation-button">Yes, Delete It!</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $("body").append(confirmationModal); // Append modal to the body
    $("#delete-confirmation-modal").modal("show");

    // handle dynamic modal
    $(".delete-confirmation-button").on("click", function () {
        $.ajax({
            url: o,
            data: { _token: CSRF_TOKEN },
            type: "DELETE",
            beforeSend: function () {
                t.html($savingLoader).attr("disabled", true);
            },
            success: function (e) {
                t.html(i).removeClass("disabled").attr("disabled", false);
                ajaxSuccess(e, t);
            },
            error: function (e) {
                t.html(i).removeClass("disabled").attr("disabled", false);
                Notify(e);
            },
        });

        // Hide and remove modal
        $("#delete-confirmation-modal").modal("hide");
        $("#delete-confirmation-modal").remove();
    });
});

// Listen for input changes and update feature values accordingly
$("#words_limit").on("input", function () {
    $("#word_feature").val($("#words_limit").val() + " Word Limit");
});

$("#images_limit").on("input", function () {
    $("#image_feature").val($("#images_limit").val() + " images Limit");
});

$(".exit-branch-btn").on("click", function () {
    $(".exit-title").text($(this).data("title"));
    $(".exit-branch").attr("href", $(this).data("exit-url"));
    $("#exitModal").modal("show");
});

$(document).ready(function () {
    $(".logoutButton").on("click", function () {
        document.getElementById("logoutForm").submit();
    });

    $(document).on('change', '.custom-reports-checkbox', function() {
        if ($('.custom-reports-checkbox:checked').length === $('.custom-reports-checkbox').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
    });

    var reportType = $("#reportType").val();
    var reportFields = JSON.parse($("#reportFields").val() || "[]");
    var prevFields = JSON.parse($("#prevFields").val() || "[]");

    $("#type").on("change", function () {
        var type = $(this).val();
        generateCheckboxes(type);
    });

    if (reportType) {
        generateCheckboxes(reportType);
    }

    // Recursive function to flatten nested fields
    function flattenFields(obj, prefix = "") {
        var flat = {};
        $.each(obj, function (key, value) {
            if (value.label !== undefined) {
                flat[prefix + key] = value;
            } else {
                $.extend(flat, flattenFields(value, prefix + key + "."));
            }
        });
        return flat;
    }

    function generateCheckboxes(type) {
        if (!type) {
            $(".select-type-text").text("Select a report type to load fields");
            $("#fieldsContainer").empty();
            return;
        }

        var fields = reportFields[type] || {};
        var multibranch_active = $('#multibranch_active').val();

        // 🔹 Remove branch if multibranch is not active
        if (multibranch_active != 1 && fields.branch) {
            delete fields.branch;
        }

        var flatFields = flattenFields(fields);

        // Create lookup map from prevFields
        var prevMap = {};
        prevFields.forEach(function (item) {
            prevMap[item.field] = item.order;
        });

        var entries = Object.entries(flatFields);
        var half = Math.ceil(entries.length / 2);

        // Helper to build checkbox grid
        function buildColumn(items) {
            var html = `<div class="col-lg-6">`;
            $.each(items, function (_, [key, row]) {
                var checked = prevMap[key] !== undefined ? "checked" : "";
                var orderVal = prevMap[key] !== undefined ? prevMap[key] : "";
                html += `
                    <div class="custom-report-grid custom-report-check">
                        <div class="d-flex align-items-center mb-2 mt-2">
                            <input type="checkbox" id="field_${key}" class="delete-checkbox-item multi-delete custom-reports-checkbox" name="fields[]" value="${key}" ${checked}>
                            <label for="field_${key}" class="custom-top-label">${row.label}</label>
                        </div>
                        <div>
                            <input type="text" name="order[]" placeholder="Enter order no" class="custom-input" value="${orderVal}">
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
            return html;
        }

        var html =
            `<div class="row">` +
            buildColumn(entries.slice(0, half)) +
            buildColumn(entries.slice(half)) +
            `</div>`;

        $(".select-type-text").text("");
        $("#fieldsContainer").html(html);

        if ($('.custom-reports-checkbox:checked').length === $('.custom-reports-checkbox').length && $('.custom-reports-checkbox').length > 0) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
    }
});

$("#selectAll").on("change", function () {
    const isChecked = $(this).is(":checked");
    $(".delete-checkbox-item").prop("checked", isChecked);
});
