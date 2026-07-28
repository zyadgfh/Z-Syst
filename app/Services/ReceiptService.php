<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\App;

/**
 * ReceiptService
 *
 * خدمة الإيصالات والطباعة
 * تدعم: HTML (للطباعة في المتصفح)، بيانات JSON (للتطبيقات)
 */
class ReceiptService
{
    /**
     * Generate receipt data array for a sale.
     */
    public function generateReceiptData(string $saleId): array
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
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? $item->product->product_name ?? 'منتج',
                'generic_name' => $item->product->generic_name ?? null,
                'barcode' => $item->product->barcode ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) ($item->discount ?? 0),
                'line_total' => (float) $item->line_total,
            ];
        });

        $payments = $sale->payments->map(function ($payment) {
            return [
                'payment_method' => $payment->payment_method,
                'amount' => (float) $payment->amount,
                'reference_number' => $payment->reference_number,
            ];
        });

        return [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at->format('Y-m-d H:i:s'),
            'cashier' => $sale->createdBy?->name ?? '—',
            'customer_name' => $sale->customer_name ?? 'نقدي',
            'customer_phone' => $sale->customer_phone,
            'items' => $items,
            'payments' => $payments,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) ($sale->discount_amount ?? 0),
            'tax_amount' => (float) ($sale->tax_amount ?? 0),
            'total_amount' => (float) $sale->total_amount,
            'amount_paid' => (float) ($sale->amount_paid ?? $sale->total_amount),
            'change_amount' => (float) ($sale->change_amount ?? 0),
            'payment_method' => $sale->payment_method ?? 'cash',
            'status' => $sale->status,
            'items_count' => $items->count(),
            'total_quantity' => (float) $items->sum('quantity'),

            // Wallet info for receipt display
            'wallet' => $walletEnabled ? [
                'phone' => $walletPhone,
                'provider' => $walletProvider,
                'name' => $walletName,
                'instructions' => $walletInstructions,
            ] : null,
        ];
    }

    /**
     * Generate HTML receipt for printing.
     */
    public function generateReceiptHtml(string $saleId): string
    {
        $data = $this->generateReceiptData($saleId);

        $itemsHtml = '';
        foreach ($data['items'] as $item) {
            $itemsHtml .= <<<HTML
            <tr>
                <td>{$item['product_name']}</td>
                <td class="center">{$item['quantity']}</td>
                <td class="right">{$this->formatCurrency($item['unit_price'])}</td>
                <td class="right">{$this->formatCurrency($item['line_total'])}</td>
            </tr>
HTML;
        }

        $walletHtml = '';
        if ($data['wallet'] && !empty($data['wallet']['phone'])) {
            $walletHtml = <<<HTML
            <div class="wallet-info">
                <p><strong>💳 الدفع بالمحفظة:</strong></p>
                <p>{$data['wallet']['name']} - {$data['wallet']['phone']}</p>
                <p class="instructions">{$data['wallet']['instructions']}</p>
            </div>
HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>إيصال #{$data['invoice_number']}</title>
            <style>
                @page { margin: 0; }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    padding: 16px;
                    color: #222;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px dashed #888;
                    padding-bottom: 12px;
                    margin-bottom: 12px;
                }
                .header h2 { font-size: 16px; margin-bottom: 4px; }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 12px 0;
                }
                th, td {
                    padding: 4px;
                    border-bottom: 1px dashed #ccc;
                    font-size: 11px;
                }
                th { color: #666; font-size: 10px; }
                .center { text-align: center; }
                .right { text-align: left; }
                .total-row td {
                    font-weight: bold;
                    font-size: 14px;
                    padding-top: 8px;
                    border-top: 2px solid #333;
                }
                .footer {
                    margin-top: 16px;
                    padding-top: 12px;
                    border-top: 2px dashed #888;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                }
                .wallet-info {
                    margin-top: 12px;
                    padding: 8px;
                    border: 1px dashed #888;
                    border-radius: 4px;
                    font-size: 11px;
                    text-align: center;
                }
                .wallet-info .instructions {
                    margin-top: 4px;
                    font-size: 10px;
                    color: #555;
                }
                .status-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 10px;
                    font-weight: bold;
                }
                .status-completed { background: #d4edda; color: #155724; }
                .status-partial { background: #fff3cd; color: #856404; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>🧾 فاتورة</h2>
                <p style="font-size:14px;font-weight:bold">#{$data['invoice_number']}</p>
                <p style="font-size:10px;color:#666">{$data['date']}</p>
                <p style="font-size:10px;color:#666">الكاشير: {$data['cashier']}</p>
            </div>

            <p><strong>العميل:</strong> {$data['customer_name']}</p>
            <p><strong>طريقة الدفع:</strong> {$this->translatePaymentMethod($data['payment_method'])}</p>
            <p><strong>الحالة:</strong> <span class="status-badge status-{$data['status']}">{$this->translateStatus($data['status'])}</span></p>

            <table>
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th class="center">الكمية</th>
                        <th class="right">السعر</th>
                        <th class="right">المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>

            <div style="margin-top:8px">
                <p class="right"><strong>المجموع الفرعي:</strong> {$this->formatCurrency($data['subtotal'])}</p>
                <p class="right"><strong>الخصم:</strong> {$this->formatCurrency($data['discount_amount'])}</p>
                <p class="right"><strong>الضريبة:</strong> {$this->formatCurrency($data['tax_amount'])}</p>
                <p class="right" style="font-size:16px;font-weight:bold;margin-top:8px">
                    <strong>الإجمالي:</strong> {$this->formatCurrency($data['total_amount'])}
                </p>
                <p class="right"><strong>المدفوع:</strong> {$this->formatCurrency($data['amount_paid'])}</p>
                <p class="right"><strong>الباقي:</strong> {$this->formatCurrency($data['change_amount'])}</p>
            </div>

            {$walletHtml}

            <div class="footer">
                <p>شكراً لزيارتكم! 💊</p>
                <p style="margin-top:4px">تمت بواسطة: {$data['cashier']}</p>
            </div>

            <script>window.print();<\/script>
        </body>
        </html>
HTML;
    }

    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 2) . ' ج.م';
    }

    private function translatePaymentMethod(string $method): string
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

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'مكتمل',
            'partial' => 'مدفوع جزئياً',
            'voided' => 'ملغي',
            'pending' => 'معلق',
            default => $status,
        };
    }
}

