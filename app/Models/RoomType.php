<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_rooms',
        'max_adults',
    ];

    // 🔹 Relationships

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
}