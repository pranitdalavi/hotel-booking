<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'check_in',
        'check_out',
        'guests',
        'meal_plan',
        'status',
        'original_price',
        'discount_amount',
        'final_price',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'original_price' => 'float',
        'discount_amount' => 'float',
        'final_price' => 'float',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
