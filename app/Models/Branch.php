<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'gst_rate' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function terminals()
    {
        return $this->hasMany(PosTerminal::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
