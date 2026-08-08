<?php

namespace App\Services;

use App\Models\SupplierCredit;
use App\Models\SupplierDebit;
use App\Models\CreditDebitItem;
use Illuminate\Support\Facades\DB;

class CreditDebitService
{
    public function createCredit(array $data): SupplierCredit
    {
        return DB::transaction(function () use ($data) {
            $data['credit_number'] = $this->generateCreditNumber();
            $credit = SupplierCredit::create($data);

            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    CreditDebitItem::create([
                        'parent_id' => $credit->id,
                        'parent_type' => SupplierCredit::class,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['quantity'] * $item['unit_price'],
                    ]);
                }
            }

            return $credit;
        });
    }

    public function createDebit(array $data): SupplierDebit
    {
        return DB::transaction(function () use ($data) {
            $data['debit_number'] = $this->generateDebitNumber();
            $debit = SupplierDebit::create($data);

            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    CreditDebitItem::create([
                        'parent_id' => $debit->id,
                        'parent_type' => SupplierDebit::class,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['quantity'] * $item['unit_price'],
                    ]);
                }
            }

            return $debit;
        });
    }

    protected function generateCreditNumber(): string
    {
        $date = now()->format('Ymd');
        $lastCredit = SupplierCredit::where('credit_number', 'like', "CR-{$date}%")
            ->orderBy('id', 'desc')->first();

        if ($lastCredit) {
            $lastNumber = (int) substr($lastCredit->credit_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "CR-{$date}-{$newNumber}";
    }

    protected function generateDebitNumber(): string
    {
        $date = now()->format('Ymd');
        $lastDebit = SupplierDebit::where('debit_number', 'like', "DR-{$date}%")
            ->orderBy('id', 'desc')->first();

        if ($lastDebit) {
            $lastNumber = (int) substr($lastDebit->debit_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "DR-{$date}-{$newNumber}";
    }

    public function approveCredit(SupplierCredit $credit, int $approvedBy): SupplierCredit
    {
        $credit->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $credit;
    }

    public function approveDebit(SupplierDebit $debit, int $approvedBy): SupplierDebit
    {
        $debit->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $debit;
    }
}
