// Get numeric value from string
function getNumericValue(value) {
    return parseFloat(value.replace(/[^0-9.-]+/g, "")) || 0;
}

updateTotalAmount();

// Update quantity on manual change
$(document).on('change', '.return-qty', function() {
    var row = $(this).closest('tr');
    var currentQtyInput = $(this);
    var currentQty = getNumericValue(currentQtyInput.val());
    var maxQty = parseFloat(row.data('max_qty'));

    // Validate the entered quantity
    if (currentQty > maxQty) {
        toastr.error('You cannot return more than the ordered quantity.');
        currentQtyInput.val(0);
    } else if (currentQty < 0) {
        toastr.error('Return quantity cannot be less than 0.');
        currentQtyInput.val(0);
    }

    updateSubTotal(row);
});


// Increase quantity
$(document).on('click', '.add-btn', function(e) {
    e.preventDefault();

    // Find the closest row and get current quantity and max quantity
    var row = $(this).closest('tr');
    var currentQtyInput = row.find('.return-qty');
    var currentQty = getNumericValue(currentQtyInput.val());
    var maxQty = parseFloat(row.data('max_qty'));

    // Check if increment will exceed maxQty
    if ((currentQty + 1) <= maxQty) {
        currentQtyInput.val(currentQty + 1);
        updateSubTotal(row);
    } else {
        toastr.error('You cannot return more than the ordered quantity.');
    }
});

// Decrease quantity
$(document).on('click', '.sub-btn', function(e) {
    e.preventDefault();

    // Find the closest row and get current quantity
    var row = $(this).closest('tr');
    var currentQtyInput = row.find('.return-qty');
    var currentQty = getNumericValue(currentQtyInput.val());

    // return quantity cannot negative
    if ((currentQty - 1) >= 0) {
        currentQtyInput.val(currentQty - 1);
        updateSubTotal(row);
    } else {
        toastr.error('Return quantity cannot be negative.');
    }
});

// Update the subtotal for each row based on the return quantity
function updateSubTotal(row) {
    let priceText = row.find(".price").text();
    let price = getNumericValue(String(priceText || ""))
        || getNumericValue(String(row.data("discounted_price_per_unit") || "0"));

    let quantity = getNumericValue(String(row.find(".return-qty").val() || "0"));
    let subTotal = price * quantity;

    row.find(".subtotal").text(currencyFormat(subTotal));
    updateTotalAmount();
}


// Update total return amount
function updateTotalAmount() {
    var totalReturn = 0;
    $('.subtotal').each(function() {
        totalReturn += getNumericValue($(this).text());
    });

    var returnText = $('.return_amount').data('return-text');

    $('.return_amount').text(returnText + ' ' + currencyFormat(totalReturn));
}
