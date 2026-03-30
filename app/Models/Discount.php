<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'min_days',
        'days_before',
    ];

    protected $casts = [
        'value' => 'integer',
        'min_days' => 'integer',
        'days_before' => 'integer',
    ];
    
    public function isLongStay()
    {
        return $this->type === 'long_stay';
    }

    public function isLastMinute()
    {
        return $this->type === 'last_minute';
    }
}