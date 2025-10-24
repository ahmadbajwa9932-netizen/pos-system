<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale_item extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'sale_id',
        'purchase_id',
        'quantity',
        'price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total',
        'total_after_discount'
    ];

    /**
     * A sale item belongs to a sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class,'sale_id')->withTrashed();
    }

    /**
     * A sale item belongs to a purchase (product batch).
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id')->withTrashed();
    }

    public function returnItems()
{
    return $this->hasMany(SaleReturnItem::class, 'sale_item_id');
}
}
