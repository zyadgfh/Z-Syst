<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return SaleItemFactory::new();
    }

    protected $fillable = ['sale_id', 'product_id', 'batch_number', 'quantity', 'unit_price', 'discount', 'tax', 'total'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
