<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'seats' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}