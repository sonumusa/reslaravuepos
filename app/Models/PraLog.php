<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PraLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
