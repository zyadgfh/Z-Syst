<?php

namespace App\Services;

use App\Models\SupplierPayment;
use App\Models\PaymentSchedule;
use App\Models\AgingReport;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    public function create(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            $data['payment_number'] = $this->generatePaymentNumber();
            return SupplierPayment::create($data);
        });
    }

    protected function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $lastPayment = SupplierPayment::where('payment_number', 'like', "PAY-{$date}%")
            ->orderBy('id', 'desc')->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "PAY-{$date}-{$newNumber}";
    }

    public function approve(SupplierPayment $payment, int $approvedBy): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $approvedBy) {
            $payment->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
            return $payment;
        });
    }

    public function generateAgingReport(int $businessId): void
    {
        $suppliers = \App\Models\Supplier::forBusiness($businessId)->get();

        foreach ($suppliers as $supplier) {
            AgingReport::create([
                'supplier_id' => $supplier->id,
                'business_id' => $businessId,
                'report_date' => now(),
                'period_30' => $this->calculatePeriod($supplier, 0, 30),
                'period_60' => $this->calculatePeriod($supplier, 31, 60),
                'period_90' => $this->calculatePeriod($supplier, 61, 90),
                'period_90_plus' => $this->calculatePeriod($supplier, 91, 9999),
                'total' => $this->calculateTotalBalance($supplier),
                'generated_at' => now(),
            ]);
        }
    }

    protected function calculatePeriod($supplier, $minDays, $maxDays): float
    {
        $invoices = \App\Models\SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'paid')
            ->get();

        return $invoices->sum(function ($invoice) use ($minDays, $maxDays) {
            $days = now()->diffInDays($invoice->invoice_date);
            return ($days >= $minDays && $days <= $maxDays) ? $invoice->balance : 0;
        });
    }

    protected function calculateTotalBalance($supplier): float
    {
        return \App\Models\SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'paid')
            ->sum('balance');
    }

    public function createSchedule(array $data): PaymentSchedule
    {
        return PaymentSchedule::create($data);
    }

    public function getPendingPayments(int $businessId)
    {
        return SupplierPayment::forBusiness($businessId)
            ->pending()
            ->with(['supplier', 'createdBy'])
            ->latest()
            ->get();
    }
}
