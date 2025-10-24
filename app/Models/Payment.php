<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'sale_id',
        'amount',
        'method',
        'payment_date'
    ];

    /**
     * A payment belongs to a sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class,'sale_id')->withTrashed();
    }
}
