<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:3',
        'alert_quantity' => 'decimal:3',
        'cost_price' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
