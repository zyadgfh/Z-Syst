<?php

return [

    // =========================
    // Sales Report
    // =========================
    'sales' => [
        'invoiceNumber'    => ['label' => 'Invoice Number', 'type' => 'string'],
        'saleDate'         => ['label' => 'Sale Date', 'type' => 'date'],
        'totalAmount'      => ['label' => 'Total Amount', 'type' => 'amount'],
        'paidAmount'       => ['label' => 'Paid Amount', 'type' => 'amount'],
        'dueAmount'        => ['label' => 'Due Amount', 'type' => 'amount'],
        'discountAmount'   => ['label' => 'Discount Amount', 'type' => 'amount'],
        'discount_percent' => ['label' => 'Discount Percent', 'type' => 'percentage'],
        'discount_type'    => ['label' => 'Discount Type', 'type' => 'string'],
        'shipping_charge'  => ['label' => 'Shipping Charge', 'type' => 'amount'],
        'isPaid'           => ['label' => 'Payment Status', 'type' => 'boolean'],
        'vat_amount'       => ['label' => 'VAT Amount', 'type' => 'amount'],
        'vat_percent'      => ['label' => 'VAT Percent', 'type' => 'percentage'],
        'lossProfit'       => ['label' => 'Loss/Profit', 'type' => 'amount'],
        'change_amount'    => ['label' => 'Change Amount', 'type' => 'amount'],
        'created_at'       => ['label' => 'Created At', 'type' => 'date'],
        'updated_at'       => ['label' => 'Updated At', 'type' => 'date'],

        'details' => [
            'price'                => ['label' => 'Product Price', 'type' => 'amount'],
            'lossProfit'           => ['label' => 'Loss Profit', 'type' => 'amount'],
            'quantities'           => ['label' => 'Quantity', 'type' => 'number'],
            'expire_date'          => ['label' => 'Expire Date', 'type' => 'date'],
            'mfg_date'             => ['label' => 'Manufacture Date', 'type' => 'date'],
            'productPurchasePrice' => ['label' => 'Product Purchase Price', 'type' => 'amount'],
        ],

        'party' => [
            'name'                 => ['label' => 'Customer Name', 'type' => 'string'],
            'email'                => ['label' => 'Customer Email', 'type' => 'string'],
            'phone'                => ['label' => 'Customer Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Customer Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Customer Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Customer Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Customer Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Customer Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Customer Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Customer Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Customer Shipping Address', 'type' => 'string'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'vat' => [
            'name' => ['label' => 'Vat Name', 'type' => 'string'],
            'rate' => ['label' => 'Vat Rate', 'type' => 'percentage'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],

    // =========================
    // Sales Return
    // =========================
    'sale_returns' => [
        'invoice_no'  => ['label' => 'Invoice Number', 'type' => 'string'],
        'return_date' => ['label' => 'Return Date', 'type' => 'date'],

        'sale' => [
            'totalAmount'    => ['label' => 'Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Discount Amount', 'type' => 'amount'],
            'saleDate'       => ['label' => 'Sale Date', 'type' => 'date'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'details' => [
            'return_amount' => ['label' => 'Return Amount', 'type' => 'amount'],
            'return_qty'    => ['label' => 'Return Qty', 'type' => 'number'],
        ],
    ],

    // =========================
    // Purchases Report
    // =========================
    'purchases' => [
        'invoiceNumber'    => ['label' => 'Invoice Number', 'type' => 'string'],
        'totalAmount'      => ['label' => 'Total Amount', 'type' => 'amount'],
        'paidAmount'       => ['label' => 'Paid Amount', 'type' => 'amount'],
        'dueAmount'        => ['label' => 'Due Amount', 'type' => 'amount'],
        'discountAmount'   => ['label' => 'Discount Amount', 'type' => 'amount'],
        'discount_percent' => ['label' => 'Discount', 'type' => 'percentage'],
        'discount_type'    => ['label' => 'Discount Type', 'type' => 'string'],
        'shipping_charge'  => ['label' => 'Shiping Charge', 'type' => 'amount'],
        'vat_amount'       => ['label' => 'VAT Amount', 'type' => 'amount'],
        'vat_percent'      => ['label' => 'VAT Percent', 'type' => 'percentage'],
        'isPaid'           => ['label' => 'Payment Status', 'type' => 'boolean'],
        'change_amount'    => ['label' => 'Change Amount', 'type' => 'amount'],
        'purchaseDate'     => ['label' => 'Purchase Date', 'type' => 'date'],
        'created_at'       => ['label' => 'Created At', 'type' => 'date'],
        'updated_at'       => ['label' => 'Updated At', 'type' => 'date'],

        'details' => [
            'productDealerPrice'    => ['label' => 'Dealer Price', 'type' => 'amount'],
            'productPurchasePrice'  => ['label' => 'Purchase Price', 'type' => 'amount'],
            'profit_percent'        => ['label' => 'Profit', 'type' => 'percentage'],
            'productSalePrice'      => ['label' => 'Sale Price', 'type' => 'amount'],
            'productWholeSalePrice' => ['label' => 'Wholesale Price', 'type' => 'amount'],
            'quantities'            => ['label' => 'Quantity', 'type' => 'number'],
            'mfg_date'              => ['label' => 'Manufacture Date', 'type' => 'date'],
            'expire_date'           => ['label' => 'Expire Date', 'type' => 'date'],
        ],

        'party' => [
            'name'                 => ['label' => 'Supplier Name', 'type' => 'string'],
            'email'                => ['label' => 'Supplier Email', 'type' => 'string'],
            'phone'                => ['label' => 'Supplier Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Supplier Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Supplier Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Supplier Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Supplier Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Supplier Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Supplier Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Supplier Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Supplier Shipping Address', 'type' => 'string'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'vat' => [
            'name' => ['label' => 'Vat Name', 'type' => 'string'],
            'rate' => ['label' => 'Vat Rate', 'type' => 'percentage'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],

    // =========================
    // Purchase Return
    // =========================
    'purchase_returns' => [
        'invoice_no'  => ['label' => 'Invoice Number', 'type' => 'string'],
        'return_date' => ['label' => 'Return Date', 'type' => 'date'],

        'purchase' => [
            'totalAmount'    => ['label' => 'Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Discount Amount', 'type' => 'amount'],
            'purchaseDate'   => ['label' => 'Purchase Date', 'type' => 'date'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'details' => [
            'return_amount' => ['label' => 'Return Amount', 'type' => 'amount'],
            'return_qty'    => ['label' => 'Return Qty', 'type' => 'number'],
        ],
    ],

    // =========================
    // Products Report
    // =========================
    'products' => [
        'productName'         => ['label' => 'Product Name', 'type' => 'string'],
        'productPicture'      => ['label' => 'Product Image', 'type' => 'string'],
        'productCode'         => ['label' => 'Product Code', 'type' => 'string'],
        'product_type'        => ['label' => 'Product Type', 'type' => 'string'],
        'alert_qty'           => ['label' => 'Alert Qty', 'type' => 'number'],
        'productManufacturer' => ['label' => 'Manufacturer', 'type' => 'string'],

        'stocks' => [
            'productPurchasePrice'  => ['label' => 'Purchase Price', 'type' => 'amount'],
            'productSalePrice'      => ['label' => 'Sale Price', 'type' => 'amount'],
            'productWholeSalePrice' => ['label' => 'Wholesale Price', 'type' => 'amount'],
            'productDealerPrice'    => ['label' => 'Dealer Price', 'type' => 'amount'],
            'batch_no'              => ['label' => 'Batch No', 'type' => 'string'],
            'mfg_date'              => ['label' => 'Manufacture Date', 'type' => 'date'],
            'expire_date'           => ['label' => 'Stock Expire Date', 'type' => 'date'],
        ],

        'unit' => [
            'unitName' => ['label' => 'Unit Name', 'type' => 'string'],
        ],

        'brand' => [
            'brandName' => ['label' => 'Brand Name', 'type' => 'string'],
        ],

        'vat' => [
            'name' => ['label' => 'Vat Name', 'type' => 'string'],
            'rate' => ['label' => 'Vat Rate', 'type' => 'percentage'],
        ],

        'category' => [
            'categoryName' => ['label' => 'Category Name', 'type' => 'string'],
        ],

        'warehouse' => [
            'name'    => ['label' => 'Warehouse Name', 'type' => 'string'],
            'phone'   => ['label' => 'Warehouse Phone', 'type' => 'string'],
            'email'   => ['label' => 'Warehouse Email', 'type' => 'string'],
            'address' => ['label' => 'Warehouse Address', 'type' => 'string'],
        ],

        'rack' => [
            'name' => ['label' => 'Rack Name', 'type' => 'string'],
        ],

        'shelf' => [
            'name' => ['label' => 'Shelf Name', 'type' => 'string'],
        ],

        'product_model' => [
            'name' => ['label' => 'Model Name', 'type' => 'string'],
        ],

        // Virtual field
        'total_stock' => ['label' => 'Total Stock (All Batches)', 'type' => 'number'],
    ],

    // =========================
    // Customers
    // =========================
    'customers' => [
        'name'                 => ['label' => 'Name', 'type' => 'string'],
        'email'                => ['label' => 'Email', 'type' => 'string'],
        'phone'                => ['label' => 'Phone', 'type' => 'string'],
        'due'                  => ['label' => 'Due', 'type' => 'amount'],
        'image'                => ['label' => 'Image', 'type' => 'string'],
        'status'               => ['label' => 'Status', 'type' => 'string'],
        'address'              => ['label' => 'Address', 'type' => 'string'],
        'credit_limit'         => ['label' => 'Credit Limit', 'type' => 'amount'],
        'wallet'               => ['label' => 'Wallet', 'type' => 'amount'],
        'opening_balance'      => ['label' => 'Opening Balance', 'type' => 'amount'],
        'opening_balance_type' => ['label' => 'Opening Balance Type', 'type' => 'string'],
        'billing_address'      => ['label' => 'Billing Address', 'type' => 'string'],
        'shipping_address'     => ['label' => 'Shipping Address', 'type' => 'string'],
    ],

    // =========================
    // Suppliers
    // =========================
    'suppliers' => [
        'name'                 => ['label' => 'Name', 'type' => 'string'],
        'email'                => ['label' => 'Email', 'type' => 'string'],
        'phone'                => ['label' => 'Phone', 'type' => 'string'],
        'due'                  => ['label' => 'Due', 'type' => 'amount'],
        'image'                => ['label' => 'Image', 'type' => 'string'],
        'status'               => ['label' => 'Status', 'type' => 'string'],
        'address'              => ['label' => 'Address', 'type' => 'string'],
        'credit_limit'         => ['label' => 'Credit Limit', 'type' => 'amount'],
        'wallet'               => ['label' => 'Wallet', 'type' => 'amount'],
        'opening_balance'      => ['label' => 'Opening Balance', 'type' => 'amount'],
        'opening_balance_type' => ['label' => 'Opening Balance Type', 'type' => 'string'],
        'billing_address'      => ['label' => 'Billing Address', 'type' => 'string'],
        'shipping_address'     => ['label' => 'Shipping Address', 'type' => 'string'],
    ],

    // =========================
    // Incomes
    // =========================
    'incomes' => [
        'amount'      => ['label' => 'Amount', 'type' => 'amount'],
        'incomeFor'  => ['label' => 'Income For', 'type' => 'string'],
        'referenceNo' => ['label' => 'Reference No', 'type' => 'string'],
        'note'        => ['label' => 'Note', 'type' => 'string'],
        'incomeDate' => ['label' => 'Income Date', 'type' => 'date'],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
        ],

        'category' => [
            'categoryName' => ['label' => 'Income Category', 'type' => 'string'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type Name', 'type' => 'string'],
        ],
    ],

    // =========================
    // Expenses
    // =========================
    'expenses' => [
        'amount'      => ['label' => 'Amount', 'type' => 'amount'],
        'expanseFor'  => ['label' => 'Expense For', 'type' => 'string'],
        'referenceNo' => ['label' => 'Reference No', 'type' => 'string'],
        'note'        => ['label' => 'Note', 'type' => 'string'],
        'expenseDate' => ['label' => 'Expense Date', 'type' => 'date'],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
        ],

        'category' => [
            'categoryName' => ['label' => 'Expense Category', 'type' => 'string'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type Name', 'type' => 'string'],
        ],
    ],

    // =========================
    // Taxes
    // =========================
    'taxes' => [
        'name' => ['label' => 'Name', 'type' => 'string'],
        'rate' => ['label' => 'Rate', 'type' => 'percentage'],
    ],

    // =========================
    // Warehouses
    // =========================
    'warehouses' => [
        'name'    => ['label' => 'Name', 'type' => 'string'],
        'phone'   => ['label' => 'Phone', 'type' => 'string'],
        'email'   => ['label' => 'Email', 'type' => 'string'],
        'address' => ['label' => 'Address', 'type' => 'string'],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],
    ],

    // =========================
    // Stocks
    // =========================
    'stocks' => [
        'batch_no'              => ['label' => 'Batch No', 'type' => 'string'],
        'productStock'          => ['label' => 'Stock', 'type' => 'number'],
        'productPurchasePrice'  => ['label' => 'Purchase Price', 'type' => 'amount'],
        'profit_percent'        => ['label' => 'Profit', 'type' => 'percentage'],
        'productSalePrice'      => ['label' => 'Sale Pice', 'type' => 'amount'],
        'productWholeSalePrice' => ['label' => 'Wholesale Price', 'type' => 'amount'],
        'productDealerPrice'    => ['label' => 'Dealer Price', 'type' => 'amount'],
        'mfg_date'              => ['label' => 'Manufacture Date', 'type' => 'date'],
        'expire_date'           => ['label' => 'Expire Date', 'type' => 'date'],

        'product' => [
            'productName'         => ['label' => 'Product Name', 'type' => 'string'],
            'productPicture'      => ['label' => 'Product Image', 'type' => 'string'],
            'productCode'         => ['label' => 'Product Code', 'type' => 'string'],
            'product_type'        => ['label' => 'Product Type', 'type' => 'string'],
            'alert_qty'           => ['label' => 'Alert Qty', 'type' => 'number'],
            'productManufacturer' => ['label' => 'Manufacturer', 'type' => 'string'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'warehouse' => [
            'name'    => ['label' => 'Warehouse Name', 'type' => 'string'],
            'phone'   => ['label' => 'Warehouse Phone', 'type' => 'string'],
            'email'   => ['label' => 'Warehouse Email', 'type' => 'string'],
            'address' => ['label' => 'Warehouse Address', 'type' => 'string'],
        ],
    ],

    // =========================
    // Transactions
    // =========================
    'transactions' => [
        'invoiceNumber'     => ['label' => 'Invoice No', 'type' => 'string'],
        'totalDue'          => ['label' => 'Total Due', 'type' => 'amount'],
        'dueAmountAfterPay' => ['label' => 'Due After Pay', 'type' => 'amount'],
        'payDueAmount'      => ['label' => 'Pay Due', 'type' => 'amount'],
        'paymentDate'       => ['label' => 'Payment Date', 'type' => 'date'],

        'party' => [
            'name'                 => ['label' => 'Party Name', 'type' => 'string'],
            'email'                => ['label' => 'Party Email', 'type' => 'string'],
            'phone'                => ['label' => 'Party Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Party Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Party Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Party Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Party Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Party Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Party Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Party Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Party Shipping Address', 'type' => 'string'],
        ],

        'sale' => [
            'totalAmount'    => ['label' => 'Sale Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Sale Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Sale Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Sale Discount Amount', 'type' => 'amount'],
            'saleDate'       => ['label' => 'Sale Date', 'type' => 'date'],
        ],

        'purchase' => [
            'totalAmount'    => ['label' => 'Purchase Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Purchase Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Purchase Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Purchase Discount Amount', 'type' => 'amount'],
            'purchaseDate'   => ['label' => 'Purchase Date', 'type' => 'date'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],

    // =========================
    // Sale Payments
    // =========================
    'sale_payments' => [
        'invoiceNumber'     => ['label' => 'Invoice No', 'type' => 'string'],
        'totalDue'          => ['label' => 'Total Due', 'type' => 'amount'],
        'dueAmountAfterPay' => ['label' => 'Due After Pay', 'type' => 'amount'],
        'payDueAmount'      => ['label' => 'Pay Due', 'type' => 'amount'],
        'paymentDate'       => ['label' => 'Payment Date', 'type' => 'date'],

        'party' => [
            'name'                 => ['label' => 'Customer Name', 'type' => 'string'],
            'email'                => ['label' => 'Customer Email', 'type' => 'string'],
            'phone'                => ['label' => 'Customer Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Customer Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Customer Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Customer Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Customer Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Customer Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Customer Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Customer Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Customer Shipping Address', 'type' => 'string'],
        ],

        'sale' => [
            'totalAmount'    => ['label' => 'Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Discount Amount', 'type' => 'amount'],
            'saleDate'       => ['label' => 'Date', 'type' => 'date'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],

    // =========================
    // Purchase Payments
    // =========================
    'purchase_payments' => [
        'invoiceNumber'     => ['label' => 'Invoice No', 'type' => 'string'],
        'totalDue'          => ['label' => 'Total Due', 'type' => 'amount'],
        'dueAmountAfterPay' => ['label' => 'Due After Pay', 'type' => 'amount'],
        'payDueAmount'      => ['label' => 'Pay Due', 'type' => 'amount'],
        'paymentDate'       => ['label' => 'Payment Date', 'type' => 'date'],

        'party' => [
            'name'                 => ['label' => 'Supplier Name', 'type' => 'string'],
            'email'                => ['label' => 'Supplier Email', 'type' => 'string'],
            'phone'                => ['label' => 'Supplier Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Supplier Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Supplier Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Supplier Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Supplier Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Supplier Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Supplier Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Supplier Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Supplier Shipping Address', 'type' => 'string'],
        ],

        'purchase' => [
            'totalAmount'    => ['label' => 'Purchase Total Amount', 'type' => 'amount'],
            'paidAmount'     => ['label' => 'Purchase Paid Amount', 'type' => 'amount'],
            'dueAmount'      => ['label' => 'Purchase Due Amount', 'type' => 'amount'],
            'discountAmount' => ['label' => 'Purchase Discount Amount', 'type' => 'amount'],
            'purchaseDate'   => ['label' => 'Purchase Date', 'type' => 'date'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],

    // =========================
    // Low Stocks Report
    // =========================
    'low_stocks' => [
        'productName'         => ['label' => 'Product Name', 'type' => 'string'],
        'productPicture'      => ['label' => 'Product Image', 'type' => 'string'],
        'productCode'         => ['label' => 'Product Code', 'type' => 'string'],
        'product_type'        => ['label' => 'Product Type', 'type' => 'string'],
        'alert_qty'           => ['label' => 'Alert Qty', 'type' => 'number'],
        'productManufacturer' => ['label' => 'Manufacturer', 'type' => 'string'],

        'stocks' => [
            'productPurchasePrice'  => ['label' => 'Purchase Price', 'type' => 'amount'],
            'productSalePrice'      => ['label' => 'Sale Price', 'type' => 'amount'],
            'productWholeSalePrice' => ['label' => 'Wholesale Price', 'type' => 'amount'],
            'productDealerPrice'    => ['label' => 'Dealer Price', 'type' => 'amount'],
            'batch_no'              => ['label' => 'Batch No', 'type' => 'string'],
            'mfg_date'              => ['label' => 'Manufacture Date', 'type' => 'date'],
            'expire_date'           => ['label' => 'Stock Expire Date', 'type' => 'date'],
        ],

        'unit' => [
            'unitName' => ['label' => 'Unit Name', 'type' => 'string'],
        ],

        'brand' => [
            'brandName' => ['label' => 'Brand Name', 'type' => 'string'],
        ],

        'vat' => [
            'name' => ['label' => 'Vat Name', 'type' => 'string'],
            'rate' => ['label' => 'Vat Rate', 'type' => 'percentage'],
        ],

        'category' => [
            'categoryName' => ['label' => 'Category Name', 'type' => 'string'],
        ],

        'warehouse' => [
            'name'    => ['label' => 'Warehouse Name', 'type' => 'string'],
            'phone'   => ['label' => 'Warehouse Phone', 'type' => 'string'],
            'email'   => ['label' => 'Warehouse Email', 'type' => 'string'],
            'address' => ['label' => 'Warehouse Address', 'type' => 'string'],
        ],

        'rack' => [
            'name' => ['label' => 'Rack Name', 'type' => 'string'],
        ],

        'shelf' => [
            'name' => ['label' => 'Shelf Name', 'type' => 'string'],
        ],

        'product_model' => [
            'name' => ['label' => 'Model Name', 'type' => 'string'],
        ],

        // Virtual field
        'total_stock' => ['label' => 'Total Stock (All Batches)', 'type' => 'number'],
    ],

    // =========================
    // Loss Profit Report
    // =========================
    'loss_profit' => [
        'invoiceNumber'    => ['label' => 'Invoice Number', 'type' => 'string'],
        'saleDate'         => ['label' => 'Sale Date', 'type' => 'date'],
        'totalAmount'      => ['label' => 'Total Amount', 'type' => 'amount'],
        'paidAmount'       => ['label' => 'Paid Amount', 'type' => 'amount'],
        'dueAmount'        => ['label' => 'Due Amount', 'type' => 'amount'],
        'discountAmount'   => ['label' => 'Discount Amount', 'type' => 'amount'],
        'discount_percent' => ['label' => 'Discount Percent', 'type' => 'percentage'],
        'discount_type'    => ['label' => 'Discount Type', 'type' => 'string'],
        'shipping_charge'  => ['label' => 'Shipping Charge', 'type' => 'amount'],
        'isPaid'           => ['label' => 'Payment Status', 'type' => 'boolean'],
        'vat_amount'       => ['label' => 'VAT Amount', 'type' => 'amount'],
        'vat_percent'      => ['label' => 'VAT Percent', 'type' => 'percentage'],
        'lossProfit'       => ['label' => 'Loss/Profit', 'type' => 'amount'],
        'change_amount'    => ['label' => 'Change Amount', 'type' => 'amount'],
        'created_at'       => ['label' => 'Created At', 'type' => 'date'],
        'updated_at'       => ['label' => 'Updated At', 'type' => 'date'],

        'details' => [
            'price'                => ['label' => 'Product Price', 'type' => 'amount'],
            'lossProfit'           => ['label' => 'Loss Profit', 'type' => 'amount'],
            'quantities'           => ['label' => 'Quantity', 'type' => 'number'],
            'expire_date'          => ['label' => 'Expire Date', 'type' => 'date'],
            'mfg_date'             => ['label' => 'Manufacture Date', 'type' => 'date'],
            'productPurchasePrice' => ['label' => 'Product Purchase Price', 'type' => 'amount'],
        ],

        'party' => [
            'name'                 => ['label' => 'Customer Name', 'type' => 'string'],
            'email'                => ['label' => 'Customer Email', 'type' => 'string'],
            'phone'                => ['label' => 'Customer Phone', 'type' => 'string'],
            'due'                  => ['label' => 'Customer Due Amount', 'type' => 'amount'],
            'address'              => ['label' => 'Customer Address', 'type' => 'string'],
            'credit_limit'         => ['label' => 'Customer Credit Limit', 'type' => 'amount'],
            'wallet'               => ['label' => 'Customer Wallet Balance', 'type' => 'amount'],
            'opening_balance'      => ['label' => 'Customer Opening Balance', 'type' => 'amount'],
            'opening_balance_type' => ['label' => 'Customer Opening Balance Type', 'type' => 'string'],
            'billing_address'      => ['label' => 'Customer Billing Address', 'type' => 'string'],
            'shipping_address'     => ['label' => 'Customer Shipping Address', 'type' => 'string'],
        ],

        'branch' => [
            'name'    => ['label' => 'Branch Name', 'type' => 'string'],
            'phone'   => ['label' => 'Branch Phone', 'type' => 'string'],
            'address' => ['label' => 'Branch Address', 'type' => 'string'],
        ],

        'user' => [
            'name'  => ['label' => 'User Name', 'type' => 'string'],
            'phone' => ['label' => 'User Phone', 'type' => 'string'],
            'email' => ['label' => 'User Email', 'type' => 'string'],
        ],

        'vat' => [
            'name' => ['label' => 'Vat Name', 'type' => 'string'],
            'rate' => ['label' => 'Vat Rate', 'type' => 'percentage'],
        ],

        'payment_type' => [
            'name' => ['label' => 'Payment Type', 'type' => 'string'],
        ],
    ],
];
