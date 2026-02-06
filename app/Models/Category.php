<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
