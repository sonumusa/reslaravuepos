<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_walkin' => 'boolean',
        'loyalty_points' => 'integer',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'birthday' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
