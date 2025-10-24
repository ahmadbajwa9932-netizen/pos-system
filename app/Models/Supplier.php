<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'company',
        'address',
        'contact_info',
    ];
    public function purchases()
{
    return $this->hasMany(Purchase::class);
}
}
