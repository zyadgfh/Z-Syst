<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return SaleFactory::new();
    }

    protected $fillable = [
        'invoice_number', 'customer_id', 'branch_id', 'user_id', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'amount_paid', 'change_amount', 'payment_method', 'payment_status', 'sale_type', 'prescription_id', 'notes', 'company_id',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
