<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComboProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock_id',
        'branch_id',
        'purchase_price',
        'quantity',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    protected $casts = [
        'product_id' => 'integer',
        'branch_id' => 'integer',
        'stock_id' => 'integer',
        'quantity' => 'double',
        'purchase_price' => 'double',
    ];
}
