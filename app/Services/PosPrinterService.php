<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;

/**
 * PosPrinterService
 *
 * خدمة الطباعة الحرارية ESC/POS
 * تدعم طباعة الإيصالات على طابعات الحرارية
 * 
 * الأوامر المدعومة:
 * - تنسيق النص (تكبير، موزع، مائل)
 * - قطع الورق
 * - طباعة الباركود
 * - حجز الطابعة لتجنب التعارض
 */
class PosPrinterService
{
    /**
     * Generate ESC/POS commands for receipt printing.
     * Returns binary commands that can be sent directly to thermal printer.
     */
    public function generateEscPosCommands(string $saleId): string
    {
        $data = $this->getReceiptData($saleId);
        
        $commands = '';
        
        // Initialize printer
        $commands .= $this->initialize();
        
        // Header
        $commands .= $this->setCenter();
        $commands .= $this->setBold(true);
        $commands .= "🏥 صيدلية Z-Syst\n";
        $commands .= $this->cutPaper(0);
        
        $commands .= $this->setCenter();
        $commands .= $this->setBold(true);
        $commands .= "🧾 إيصال\n";
        $commands .= $this->setBold(false);
        $commands .= str_repeat('-', 32) . "\n";
        
        // Invoice details
        $commands .= $this->setLeft();
        $commands .= $this->formatLine('رقم الفاتورة', $data['invoice_number']);
        $commands .= $this->formatLine('التاريخ', $data['date']);
        $commands .= $this->formatLine('الكاشير', $data['cashier']);
        $commands .= $this->formatLine('العميل', $data['customer_name']);
        $commands .= str_repeat('-', 32) . "\n";
        
        // Items header
        $commands .= $this->setBold(true);
        $commands .= $this->formatTableRow('المنتج', 'الكمية', 'السعر', 'المجموع');
        $commands .= $this->setBold(false);
        
        // Items
        foreach ($data['items'] as $item) {
            $commands .= $this->formatItem($item);
        }
        
        $commands .= str_repeat('-', 32) . "\n";
        
        // Totals
        $commands .= $this->setBold(true);
        $commands .= $this->formatLine('الإجمالي', $this->formatCurrency($data['total_amount']));
        $commands .= $this->formatLine('المدفوع', $this->formatCurrency($data['amount_paid']));
        $commands .= $this->formatLine('الباقي', $this->formatCurrency($data['change_amount']));
        $commands .= $this->setBold(false);
        
        // Payment method
        $commands .= str_repeat('-', 32) . "\n";
        $commands .= "طريقة الدفع: " . $this->translatePaymentMethod($data['payment_method']) . "\n";
        
        // Wallet info
        if ($data['wallet'] && !empty($data['wallet']['phone'])) {
            $commands .= str_repeat('-', 32) . "\n";
            $commands .= $this->setBold(true);
            $commands .= "💳 الدفع بالمحفظة:\n";
            $commands .= $this->setBold(false);
            $commands .= $data['wallet']['name'] . " - " . $data['wallet']['phone'] . "\n";
            $commands .= $data['wallet']['instructions'] . "\n";
        }
        
        // Footer
        $commands .= str_repeat('-', 32) . "\n";
        $commands .= $this->setCenter();
        $commands .= "شكراً لزيارتكم! 💊\n";
        $commands .= "تمت بواسطة: " . $data['cashier'] . "\n";
        $commands .= str_repeat('-', 32) . "\n\n\n";
        
        // Cut paper
        $commands .= $this->cutPaper(2);
        
        return $commands;
    }
    
    /**
     * Initialize printer (ESC @)
     */
    protected function initialize(): string
    {
        return "\x1B\x40";
    }
    
    /**
     * Set text alignment (ESC a)
     */
    protected function setCenter(): string
    {
        return "\x1B\x61\x01"; // Center alignment
    }
    
    protected function setLeft(): string
    {
        return "\x1B\x61\x00"; // Left alignment
    }
    
    /**
     * Set bold (ESC E)
     */
    protected function setBold(bool $enabled = true): string
    {
        return $enabled ? "\x1B\x45\x01" : "\x1B\x45\x00";
    }
    
    /**
     * Set font size (GS !)
     */
    protected function setFontSize(int $size = 0): string
    {
        // Size: 0=normal, 1=width 2x, 2=height 2x, 3=width&height 2x
        return "\x1D\x21" . chr($size);
    }
    
    /**
     * Feed lines and cut paper (GS V)
     * mode: 0=partial cut, 1=full cut, 2=partial cut + feed
     */
    protected function cutPaper(int $mode = 0): string
    {
        return "\x1D\x56" . chr($mode);
    }
    
    /**
     * Feed lines (ESC d)
     */
    protected function feedLines(int $lines = 1): string
    {
        return "\x1B\x64" . chr($lines);
    }
    
    /**
     * Print barcode (GS k)
     */
    public function generateBarcode(string $barcode, string $saleId): string
    {
        $commands = '';
        
        // Select barcode system (Code128)
        $commands .= "\x1D\x6B\x49";
        $commands .= chr(strlen($barcode));
        $commands .= $barcode;
        
        return $commands;
    }
    
    /**
     * Format a single item for receipt
     */
    protected function formatItem(array $item): string
    {
        $name = mb_substr($item['product_name'], 0, 20);
        $qty = $item['quantity'];
        $price = $this->formatCurrency($item['unit_price']);
        $total = $this->formatCurrency($item['line_total']);
        
        return sprintf(
            "%-20s %5s %6s %7s\n",
            $name,
            $qty,
            $price,
            $total
        );
    }
    
    /**
     * Format a two-column line (label: value)
     */
    protected function formatLine(string $label, string $value): string
    {
        $labelLen = min(strlen($label), 16);
        $formattedLabel = str_pad($label . ':', 16);
        $formattedValue = str_pad($value, 16);
        
        return $formattedLabel . $formattedValue . "\n";
    }
    
    /**
     * Format table row
     */
    protected function formatTableRow(string $col1, string $col2, string $col3, string $col4): string
    {
        return sprintf(
            "%-14s %5s %6s %7s\n",
            $col1,
            $col2,
            $col3,
            $col4
        );
    }
    
    /**
     * Format currency in Arabic/Egyptian format
     */
    protected function formatCurrency(float $amount): string
    {
        return number_format($amount, 2) . ' ج.م';
    }
    
    /**
     * Translate payment method to Arabic
     */
    protected function translatePaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'نقداً',
            'card' => 'بطاقة',
            'wallet' => 'محفظة إلكترونية',
            'insurance' => 'تأمين',
            'credit' => 'آجل',
            'vodafone_cash' => 'فودافون كاش',
            'instapay' => 'إنستاباي',
            'mixed' => 'مدفوعات متعددة',
            default => $method,
        };
    }
    
    /**
     * Get receipt data for a sale (reuses ReceiptService logic)
     */
    protected function getReceiptData(string $saleId): array
    {
        $sale = Sale::with([
            'items.product:id,name,product_name,generic_name,barcode,sales_price',
            'createdBy:id,name',
            'customer',
            'payments',
        ])->findOrFail($saleId);

        $companyId = $sale->company_id;

        // Get wallet info from settings
        $walletPhone = Setting::getValue('wallet_phone', '', $companyId);
        $walletProvider = Setting::getValue('wallet_provider', 'vodafone_cash', $companyId);
        $walletName = Setting::getValue('wallet_name', '', $companyId);
        $walletEnabled = Setting::getValue('wallet_enabled', true, $companyId);
        $walletInstructions = Setting::getValue('wallet_instructions', 'يرجى تحويل المبلغ إلى رقم المحفظة الظاهر في الإيصال', $companyId);

        $items = $sale->items->map(function ($item) {
            return [
                'product_name' => $item->product->name ?? $item->product->product_name ?? 'منتج',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        })->toArray();

        return [
            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at->format('Y-m-d H:i:s'),
            'cashier' => $sale->createdBy?->name ?? '—',
            'customer_name' => $sale->customer_name ?? 'نقدي',
            'items' => $items,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) ($sale->discount_amount ?? 0),
            'tax_amount' => (float) ($sale->tax_amount ?? 0),
            'total_amount' => (float) $sale->total_amount,
            'amount_paid' => (float) ($sale->amount_paid ?? $sale->total_amount),
            'change_amount' => (float) ($sale->change_amount ?? 0),
            'payment_method' => $sale->payment_method ?? 'cash',
            'status' => $sale->status,
            'wallet' => $walletEnabled ? [
                'phone' => $walletPhone,
                'provider' => $walletProvider,
                'name' => $walletName,
                'instructions' => $walletInstructions,
            ] : null,
        ];
    }
    
    /**
     * Generate receipt for printing to ESC/POS compatible printer.
     * Returns base64 encoded commands for network/printer API.
     */
    public function getReceiptForPrinter(string $saleId): string
    {
        $commands = $this->generateEscPosCommands($saleId);
        return base64_encode($commands);
    }
}