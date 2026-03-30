<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'date',
        'available_rooms',
    ];

    protected $casts = [
        'date' => 'date',
    ];
    
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}