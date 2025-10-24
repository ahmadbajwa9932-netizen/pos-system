<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'product_name',
        'purchased_price',    //purchasd price
        'sold_price',
        'previous_sold_price',
        'quantity',
        'unit',
        'sold_quantity',
        'purchase_date',
        'supplier_id',
        'category_id',
    ];
    public function supplier()
{
    return $this->belongsTo(Supplier::class,'supplier_id')->withTrashed();
}

public function category()
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }
}
