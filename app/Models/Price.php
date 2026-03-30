<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'date',
        'price',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'float',
    ];
    
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}