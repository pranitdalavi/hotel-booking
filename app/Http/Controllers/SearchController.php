<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomType;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Discount;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function index()
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:3',
            'meal_plan' => 'nullable|in:room_only,breakfast',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $stayDays = $checkIn->diffInDays($checkOut);
        $guests = (int) $request->guests;
        $mealPlan = $request->meal_plan ?? 'room_only';

        $roomTypes = RoomType::all();
        $longStay = Discount::where('type', 'long_stay')->first();
        $lastMinute = Discount::where('type', 'last_minute')->first();
        $daysBeforeCheckIn = Carbon::today()->diffInDays($checkIn, false);

        $results = [];

        foreach ($roomTypes as $room) {
            if ($guests > $room->max_adults) {
                continue;
            }

            $dates = [];
            for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
                $dates[] = $date->toDateString();
            }

            $inventoryRecords = Inventory::where('room_type_id', $room->id)
                ->whereIn('date', $dates)
                ->get();

            $priceRecords = Price::where('room_type_id', $room->id)
                ->whereIn('date', $dates)
                ->get();

            $inventoryComplete = $inventoryRecords->count() === count($dates);
            $pricingComplete = $priceRecords->count() === count($dates);

            $availableRooms = 0;
            $isAvailable = false;

            if ($inventoryComplete) {
                $availableRooms = $inventoryRecords->min('available_rooms');
                $isAvailable = $availableRooms > 0;
            }

            $totalPrice = $pricingComplete ? $priceRecords->sum('price') : 0;
            $discountAmount = 0;

            if ($totalPrice > 0 && $longStay && $stayDays >= $longStay->min_days) {
                $discountAmount += ($totalPrice * $longStay->value) / 100;
            }

            if ($totalPrice > 0 && $lastMinute && $daysBeforeCheckIn >= 0 && $daysBeforeCheckIn <= $lastMinute->days_before) {
                $discountAmount += ($totalPrice * $lastMinute->value) / 100;
            }

            $finalPrice = max(0, $totalPrice - $discountAmount);

            if ($mealPlan === 'breakfast') {
                $finalPrice += $stayDays * 500;
            }

            $results[] = [
                'room_type_id' => $room->id,
                'room_type' => $room->name,
                'available' => $isAvailable && $inventoryComplete && $pricingComplete,
                'total_rooms' => $room->total_rooms,
                'available_rooms' => $availableRooms,
                'stay_nights' => $stayDays,
                'meal_plan' => $mealPlan,
                'original_price' => $pricingComplete ? round($totalPrice, 2) : null,
                'discount_applied' => round($discountAmount, 2),
                'final_price' => $pricingComplete ? round($finalPrice, 2) : null,
                'note' => !$inventoryComplete || !$pricingComplete ? 'Data unavailable for full date range' : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
