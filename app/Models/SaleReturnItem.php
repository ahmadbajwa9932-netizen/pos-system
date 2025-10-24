<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturnItem extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'purchase_id',
        'quantity_returned',
        'unit_net_after_discount',
        'amount_refunded',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class,'sale_return_id')->withTrashed();
    }

    public function saleItem()
    {
        // Your model is named Sale_item (with underscore)
        return $this->belongsTo(Sale_item::class, 'sale_item_id')->withTrashed();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class,'purchase_id')->withTrashed();
    }
}
