<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuModifier extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}