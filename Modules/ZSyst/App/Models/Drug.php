<?php

namespace Modules\ZSyst\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Drug extends Model
{
    protected $fillable = [
        'company_id',
        'business_id',
        'name',
        'scientific_name',
        'barcode',
        'generic_name',
        'strength',
        'form',
        'manufacturer',
        'purchase_price',
        'sale_price',
        'wholesale_price',
        'stock_alert',
        'is_active',
    ];

    public function alternatives(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'drug_alternatives', 'drug_id', 'alternative_drug_id');
    }
}
