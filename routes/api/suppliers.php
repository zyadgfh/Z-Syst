<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
    // Barcodes - specific routes before wildcard
    Route::get('barcodes/search', [Api\BarcodeController::class, 'search'])->name('barcodes.search');
    Route::get('barcodes/settings', [Api\BarcodeController::class, 'settings'])->name('barcodes.settings');
    Route::get('barcodes/not-printed', [Api\BarcodeController::class, 'notPrinted'])->name('barcodes.not-printed');
    Route::get('barcodes/download/{filename}', [Api\BarcodeController::class, 'download'])->name('barcodes.download');
    Route::get('barcodes/by-product/{productId}', [Api\BarcodeController::class, 'byProduct'])->name('barcodes.by-product');
    Route::get('barcodes/by-batch/{batchId}', [Api\BarcodeController::class, 'byBatch'])->name('barcodes.by-batch');
    Route::post('barcodes/generate-multiple', [Api\BarcodeController::class, 'generateMultiple'])->name('barcodes.generate-multiple');
    Route::post('barcodes/generate-for-batch', [Api\BarcodeController::class, 'generateForBatch'])->name('barcodes.generate-for-batch');
    Route::post('barcodes/print-multiple', [Api\BarcodeController::class, 'printMultiple'])->name('barcodes.print-multiple');
    Route::post('barcodes/print-for-product', [Api\BarcodeController::class, 'printForProduct'])->name('barcodes.print-for-product');
    Route::post('barcodes/print-for-batch', [Api\BarcodeController::class, 'printForBatch'])->name('barcodes.print-for-batch');
    Route::apiResource('barcodes', Api\BarcodeController::class)->except('show');
    Route::get('barcodes/{barcode}', [Api\BarcodeController::class, 'show'])->name('barcodes.show');
    Route::post('barcodes/{barcode}/print', [Api\BarcodeController::class, 'print'])->name('barcodes.print');
    Route::post('barcodes/{barcode}/reprint', [Api\BarcodeController::class, 'reprint'])->name('barcodes.reprint');

    // Supplier Invoices
    Route::apiResource('supplier-invoices', Api\SupplierInvoiceController::class)->except('show');
    // Specific routes BEFORE wildcard to avoid {supplierInvoice} capturing them
    Route::get('supplier-invoices/statistics', [Api\SupplierInvoiceController::class, 'statistics'])->name('supplier-invoices.statistics');
    Route::get('supplier-invoices/aging-report', [Api\SupplierInvoiceController::class, 'agingReport'])->name('supplier-invoices.aging-report');
    Route::get('supplier-invoices/pending', [Api\SupplierInvoiceController::class, 'pending'])->name('supplier-invoices.pending');
    Route::get('supplier-invoices/overdue', [Api\SupplierInvoiceController::class, 'overdue'])->name('supplier-invoices.overdue');
    Route::get('supplier-invoices/unpaid', [Api\SupplierInvoiceController::class, 'unpaid'])->name('supplier-invoices.unpaid');
    Route::post('supplier-invoices/create-from-purchase', [Api\SupplierInvoiceController::class, 'createFromPurchase'])->name('supplier-invoices.create-from-purchase');
    Route::post('supplier-invoices/{supplierInvoice}/approve', [Api\SupplierInvoiceController::class, 'approve'])->name('supplier-invoices.approve');
    Route::post('supplier-invoices/{supplierInvoice}/reject', [Api\SupplierInvoiceController::class, 'reject'])->name('supplier-invoices.reject');
    Route::post('supplier-invoices/{supplierInvoice}/cancel', [Api\SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');
    Route::post('supplier-invoices/{supplierInvoice}/add-payment', [Api\SupplierInvoiceController::class, 'addPayment'])->name('supplier-invoices.add-payment');
    Route::post('supplier-invoices/payments/{paymentId}/approve', [Api\SupplierInvoiceController::class, 'approvePayment'])->name('supplier-invoices.approve-payment');
    Route::get('supplier-invoices/{supplierInvoice}', [Api\SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');

    // Suppliers
    Route::apiResource('suppliers', Api\SupplierController::class)->except('show');
    // Specific routes BEFORE wildcard to avoid {supplier} capturing them
    Route::get('suppliers/top-performers', [Api\SupplierController::class, 'topPerformers'])->name('suppliers.top-performers');
    Route::post('suppliers/{supplier}/calculate-performance', [Api\SupplierController::class, 'calculatePerformance'])->name('suppliers.calculate-performance');
    Route::get('suppliers/{supplier}', [Api\SupplierController::class, 'show'])->name('suppliers.show');

    // Supplier Payments
    Route::apiResource('supplier-payments', Api\SupplierPaymentController::class)->except('show');
    Route::get('supplier-payments/generate-aging-report', [Api\SupplierPaymentController::class, 'generateAgingReport'])->name('supplier-payments.generate-aging-report');
    Route::post('supplier-payments/{supplierPayment}/approve', [Api\SupplierPaymentController::class, 'approve'])->name('supplier-payments.approve');
    Route::get('supplier-payments/{supplierPayment}', [Api\SupplierPaymentController::class, 'show'])->name('supplier-payments.show');

    // Purchase Orders
    Route::apiResource('purchase-orders', Api\PurchaseOrderController::class)->except('show');
    // Specific routes BEFORE wildcard to avoid {purchaseOrder} capturing them
    Route::get('purchase-orders/pending', [Api\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::get('purchase-orders/overdue', [Api\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
    Route::get('purchase-orders/statistics', [Api\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');
    Route::post('purchase-orders/{purchaseOrder}/send', [Api\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/approve', [Api\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [Api\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [Api\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/convert', [Api\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
    Route::get('purchase-orders/{purchaseOrder}', [Api\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    // GRN (Goods Received Notes)
    Route::get('grn/pending', [Api\GRNController::class, 'pending'])->name('grn.pending');
    Route::get('grn/statistics', [Api\GRNController::class, 'statistics'])->name('grn.statistics');
    Route::apiResource('grn', Api\GRNController::class)->except('show');
    Route::get('grn/{grn}', [Api\GRNController::class, 'show'])->name('grn.show');
    Route::post('grn/{grn}/verify', [Api\GRNController::class, 'verify'])->name('grn.verify');
    Route::post('grn/{grn}/accept', [Api\GRNController::class, 'accept'])->name('grn.accept');
    Route::post('grn/{grn}/reject', [Api\GRNController::class, 'reject'])->name('grn.reject');
});
