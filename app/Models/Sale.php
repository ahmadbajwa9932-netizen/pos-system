<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'voucher_no',
        'customer_id',
        'payment_type',
        'subtotal',
        'discount_type',
        'discount',
        'discount_amount',
        'tax',
        'grand_total',
        'received_amount',
        'change_amount',
        'remaining_balance',
        'due_date',
        'status'
    ];

    /**
     * A sale belongs to a customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id')->withTrashed();
    }

    /**
     * A sale has many sale items.
     */
    public function saleItems()
    {
        return $this->hasMany(Sale_item::class);
    }

    public function payments()
{
    return $this->hasMany(Payment::class);
}

public function returns()
{
    return $this->hasMany(SaleReturn::class);
}
}
