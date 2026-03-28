<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RoomType::create(['name' => 'Standard', 'total_rooms' => 5]);
        RoomType::create(['name' => 'Deluxe', 'total_rooms' => 5]);
    }
}
