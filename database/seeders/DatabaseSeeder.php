<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Discount;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // ✅ Room Types
        $standard = RoomType::updateOrCreate(
            ['name' => 'Standard'],
            [
                'total_rooms' => 5,
                'max_adults' => 3
            ]
        );

        $deluxe = RoomType::updateOrCreate(
            ['name' => 'Deluxe'],
            [
                'total_rooms' => 5,
                'max_adults' => 3
            ]
        );

        $roomTypes = [$standard, $deluxe];

        // ✅ Next 30 Days Data
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::today()->addDays($i);

            foreach ($roomTypes as $room) {
                $availableRooms = ($i % 7 === 0) ? 0 : 5;
                $basePrice = $room->name === 'Standard' ? 3000 + ($i * 50) : 5000 + ($i * 80);

                Inventory::updateOrCreate(
                    [
                        'room_type_id' => $room->id,
                        'date' => $date,
                    ],
                    [
                        'available_rooms' => $availableRooms,
                    ]
                );

                Price::updateOrCreate(
                    [
                        'room_type_id' => $room->id,
                        'date' => $date,
                    ],
                    [
                        'price' => $basePrice,
                    ]
                );
            }
        }

        // ✅ Discounts

        Discount::updateOrCreate(
            ['type' => 'long_stay'],
            [
                'value' => 10,
                'min_days' => 3
            ]
        );

        Discount::updateOrCreate(
            ['type' => 'last_minute'],
            [
                'value' => 15,
                'days_before' => 2
            ]
        );
    }
}
