<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'track_inventory' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'prep_time_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
