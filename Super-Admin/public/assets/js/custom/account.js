"use strict";

// Toggle extra input fields in the "Add New Bank" modal
$(".more-field-btn").on("click", function () {
    const $extraFields = $(".extra-fields");
    const $toggleBtn = $(this);

    if ($extraFields.hasClass("hidden-fields")) {
        $extraFields.removeClass("hidden-fields");
        $toggleBtn.text("- Hide fields");
    } else {
        $extraFields.addClass("hidden-fields");
        $toggleBtn.text("+ Add more fields");
    }
});

/** Dynamically Bank Transaction Modal Open start */
// Fetch banks
function fetchBanks(callback) {
    var url = $('#get_banks').val();
    $.get(url, function(data) {
        var options = '<option value="">Select one</option>';
        data.forEach(function(bank) {
            options += `<option value="${bank.id}">${bank.name}</option>`;
        });
        callback(options);
    });
}

// Open modal dynamically & build From/To fields based on type
function openTransactionModal(type, transaction = null, route = null) {
    var $modal = $('#bankTransactionModal');
    var $title = $('#transactionModalTitle');
    var $modalBody = $modal.find('.modal-body .row');
    var $form = $modal.find('form');

    // Remove old dynamic fields
    $modalBody.find('.dynamic-field').remove();

    // transaction_type input field
    $modal.find('input[name="transaction_type"]').remove();
    $form.append(`<input type="hidden" name="transaction_type" value="${type}">`);

    // Set form action & method
    if (transaction && route) {
        // Edit mode
        $form.attr('action', route);
        if ($form.find('input[name="_method"]').length === 0) {
            $form.append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $form.find('input[name="_method"]').val('PUT');
        }
    } else {
        // Create mode
        $form.attr('action', route);
        $form.find('input[name="_method"]').remove();
    }

    // Fetch banks and build fields
    fetchBanks(function(options){
        var content = '';
        var titleText = '';

        if (type === 'bank_to_bank') {
            titleText = 'Bank to Bank Transfer';
            content = `
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>From</label>
                    <select name="from" class="form-control table-select w-100 from" required>${options}</select>
                </div>
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>To</label>
                    <select name="to" class="form-control table-select w-100 to" required>${options}</select>
                </div>`;
        }

        if (type === 'bank_to_cash') {
            titleText = 'Bank to Cash Transfer';
            content = `
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>From</label>
                    <select name="from" class="form-control table-select w-100 from" required>${options}</select>
                </div>
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>To</label>
                    <input type="text" readonly value="Cash" class="form-control to">
                </div>`;
        }

        if (type === 'adjust_bank') {
            titleText = 'Adjust Bank Balance';
            content = `
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>Account Name</label>
                    <select name="from" class="form-control table-select w-100 from" required>${options}</select>
                </div>
                <div class="col-lg-6 mb-2 dynamic-field">
                    <label>Type</label>
                    <select name="type" class="form-control table-select w-100 type" required>
                        <option value="credit">Increase balance</option>
                        <option value="debit">Decrease balance</option>
                    </select>
                </div>`;
        }

        if (type === 'cash_to_bank') {
            titleText = 'Cash To Bank Transfer';
            content = `
        <div class="col-lg-6 mb-2 dynamic-field">
            <label>From</label>
            <input type="text" readonly name="from" value="Cash" class="form-control from">
        </div>
        <div class="col-lg-6 mb-2 dynamic-field">
            <label>To</label>
            <select name="to" class="form-control table-select w-100 to" required>${options}</select>
        </div>`;
        }

        if (type === 'adjust_cash') {
            titleText = 'Adjust Cash Balance';

            var creditChecked = transaction && transaction.type === 'credit' ? 'checked' : '';
            var debitChecked = transaction && transaction.type === 'debit' ? 'checked' : '';

            content = `
               <div class="col-lg-12 mb-3 dynamic-field">
                   <div class="form-check">
                       <input class="form-check-input" type="radio" name="type" id="addCash" value="credit" ${creditChecked}>
                       <label class="form-check-label cash-label" for="addCash">
                           Add Cash
                       </label>
                   </div>
                   <div class="form-check">
                       <input class="form-check-input" type="radio" name="type" id="reduceCash" value="debit" ${debitChecked}>
                       <label class="form-check-label cash-label" for="reduceCash">
                           Reduce Cash
                       </label>
                   </div>
               </div>`;
        }

        // Insert dynamic fields
        $modalBody.prepend(content);

        // Set modal title
        $title.text(titleText);

        if (transaction) {
            $modal.find('.from').val(transaction.from);
            $modal.find('.type').val(transaction.type);
            $modal.find('.amount').val(transaction.amount);
            $modal.find('.date').val(transaction.date);
            $modal.find('.note').val(transaction.note);

            // Special handling for To field
            if (type === 'bank_to_cash') {
                $modal.find('.to').val('Cash');
            } else if (type === 'cash_to_bank') {
                $modal.find('.to').val(transaction.to);
            } else {
                $modal.find('.to').val(transaction.to);
            }

            if(transaction.image) {
                $('#customPreviewImg').attr('src', transaction.image).show();
                $('#customUploadContent').hide();
            } else {
                $('#customPreviewImg').hide();
                $('#customUploadContent').show();
    }
        }

        // Show modal
        new bootstrap.Modal($modal[0]).show();
    });
}

// Create buttons
$('#bank_to_bank, #bank_to_cash, #adjust_bank, #cash_to_bank, #adjust_cash').on('click', function() {
    var type = this.id;
    var route = $(this).data('route');

    openTransactionModal(type, null, route);
});

// Edit buttons
$(document).on('click', '.edit-transaction', function() {
    var transactionType = $(this).data('transaction_type');
    var transaction = {
        amount: $(this).data('amount'),
        date: $(this).data('date'),
        note: $(this).data('note'),
        type: $(this).data('type'),
        image: $(this).data('image')
    };

    //  handling for cash transactions
    if(transactionType === 'cash_to_bank') {
        transaction.from = 'Cash';
        transaction.to = $(this).data('to'); // bank id
    } else if(transactionType === 'adjust_cash') {
        transaction.type = $(this).data('type'); // credit/debit
    } else {
        // bank transactions
        transaction.from = $(this).data('from');
        transaction.to = $(this).data('to');
    }

    var route = $(this).data('route');

    openTransactionModal(transactionType, transaction, route);
});

/** Dynamically Bank Transaction Modal Open end */

// Cheque transfer start
$(document).on("click", ".cheque-transfer-btn", function () {
    var received_from = $(this).data("received-from");
    var cheque_amount = $(this).data("cheque-amount");
    var cheque_no = $(this).data("cheque-no");
    var cheque_date = $(this).data("cheque-date");
    var ref_no = $(this).data("ref-no");
    var transaction_id = $(this).data("transaction-id");

    $("#received_from").text(received_from);
    $("#cheque_amount").text(cheque_amount);
    $("#cheque_no").text(cheque_no);
    $("#cheque_date").text(cheque_date);
    $("#ref_no").text(ref_no);
    $("#deposit_transaction_id").val(transaction_id);
});
// Cheque transfer end

// When clicking Re-Open button
$(document).on("click", ".reopen-btn", function () {

    let transaction_id = $(this).data("transaction-id");

    $("#reopen_transaction_id").val(transaction_id);

    let $modalEl = $("#reopen");
    let modal = new bootstrap.Modal($modalEl[0]);
    modal.show();
});

// Bank Accounts Img upload ----------->
// Bank Accounts Img upload ----------->
$(document).ready(function () {
    const $fileInput = $("#customFileInput");
    const $previewImg = $("#customPreviewImg");
    const $content = $("#customUploadContent");

    $fileInput.on("change", function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $previewImg.attr("src", e.target.result).show();
                $content.hide();
            };
            reader.readAsDataURL(file);
        }
    });
});

// Bank edit start
$(document).on("click", ".bank-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var opening_balance = $(this).data("opening-balance");
    var opening_date = $(this).data("opening-date");
    var account_number = $(this).data("account-number");
    var routing_number = $(this).data("routing-number");
    var upi_id = $(this).data("upi-id");
    var bank_name = $(this).data("bank-name");
    var branch_name = $(this).data("branch-name");
    var account_holder = $(this).data("account-holder");
    var show_in_invoice = $(this).data("show-in-invoice");

    $("#name").val(name);
    $("#opening_balance").val(opening_balance);
    $("#opening_date").val(opening_date);
    $("#account_number").val(account_number);
    $("#routing_number").val(routing_number);
    $("#upi_id").val(upi_id);
    $("#bank_name").val(bank_name);
    $("#branch_name").val(branch_name);
    $("#account_holder").val(account_holder);
    $("#show_in_invoice").prop('checked', show_in_invoice === 1);

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
    var branch_name = $(this).data("branch-name");
    var routing_number = $(this).data("routing-number");
    var upi_id = $(this).data("upi-id");
    var account_holder = $(this).data("account-holder");
    var show_in_invoice = $(this).data("show-in-invoice");

    $("#view_account_name").text(name);
    $("#view_balance").text(balance);
    $("#view_opening_date").text(opening_date);
    $("#view_account_number").text(account_number);
    $("#view_bank_name").text(bank_name);
    $("#view_branch_name").text(branch_name);
    $("#view_routing_number").text(routing_number);
    $("#view_upi_id").text(upi_id);
    $("#view_account_holder").text(account_holder);
    $("#view_show_in_invoice").text(show_in_invoice == 1 ? 'Yes' : 'No');
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
                            }
                            else if (response.redirect) {
                                window.sessionStorage.hasPreviousMessage = true;
                                window.sessionStorage.previousMessage =
                                    response.message ?? null;
                                location.href = response.redirect;
                            }
                            else {
                                Notify("success", response.message || response);
                            }
                        },
                        error: function (xhr) {
                            Notify(
                                "error",
                                xhr.responseJSON?.message || "Something went wrong!"
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

// Transaction view start
$(document).on("click", ".transaction-view-btn", function () {
    var img = $(this).data("img");
    var user = $(this).data("user");
    var transaction_type = $(this).data("transaction-type");
    var amount = $(this).data("amount");
    var date = $(this).data("date");
    var note = $(this).data("note");

    $("#view_img").attr("src", img);
    $("#view_user").text(user);
    $("#view_transaction_type").text(transaction_type);
    $("#view_amount").text(amount);
    $("#view_date").text(date);
    $("#view_note").text(note);
});
// Transaction view end

// Cash Transaction view start
$(document).on("click", ".cash-view-btn", function () {
    var img = $(this).data("img");
    var user = $(this).data("user");
    var transaction_type = $(this).data("transaction-type");
    var platform = $(this).data("platform");
    var amount = $(this).data("amount");
    var note = $(this).data("note");
    var date = $(this).data("date");

    $("#view_img").attr("src", img);
    $("#view_user").text(user);
    $("#view_transaction_type").text(transaction_type);
    $("#view_platform").text(platform);
    $("#view_amount").text(amount);
    $("#view_note").text(note);
    $("#view_date").text(date);
});
// Cash Transaction view end

