<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturn extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'sale_id',
        'total_return_amount',
        'status',
        'notes',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class,'sale_id')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
