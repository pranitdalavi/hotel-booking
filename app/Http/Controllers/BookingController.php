<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Price;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function book(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:3',
            'meal_plan' => 'nullable|in:room_only,breakfast',
        ]);

        $roomType = RoomType::find($request->room_type_id);
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $mealPlan = $request->meal_plan ?? 'room_only';
        $stayDays = $checkIn->diffInDays($checkOut);
        $guests = $request->guests;

        if ($guests > $roomType->max_adults) {
            return response()->json([
                'success' => false,
                'message' => 'Guest count exceeds room capacity.',
            ], 422);
        }

        $dates = $this->generateDateRange($checkIn, $checkOut);

        $inventoryRecords = Inventory::where('room_type_id', $roomType->id)
            ->whereIn('date', $dates)
            ->lockForUpdate()
            ->get();

        $priceRecords = Price::where('room_type_id', $roomType->id)
            ->whereIn('date', $dates)
            ->get();

        if ($inventoryRecords->count() !== count($dates) || $priceRecords->count() !== count($dates)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected room is not available for the full date range.',
            ], 422);
        }

        $availableRooms = $inventoryRecords->min('available_rooms');
        if ($availableRooms < 1) {
            return response()->json([
                'success' => false,
                'message' => 'No rooms available for the selected dates.',
            ], 422);
        }

        $totalPrice = $priceRecords->sum('price');
        $discountAmount = $this->calculateDiscount($totalPrice, $stayDays, $checkIn);
        $finalPrice = max(0, $totalPrice - $discountAmount);

        if ($mealPlan === 'breakfast') {
            $finalPrice += $stayDays * 500;
        }

        $booking = DB::transaction(function () use ($inventoryRecords, $roomType, $checkIn, $checkOut, $guests, $mealPlan, $totalPrice, $discountAmount, $finalPrice) {
            foreach ($inventoryRecords as $inventory) {
                $inventory->decrement('available_rooms', 1);
            }

            return Booking::create([
                'room_type_id' => $roomType->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $guests,
                'meal_plan' => $mealPlan,
                'status' => 'pending',
                'original_price' => round($totalPrice, 2),
                'discount_amount' => round($discountAmount, 2),
                'final_price' => round($finalPrice, 2),
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->id,
                'room_type' => $roomType->name,
                'amount_due' => round($booking->final_price, 2),
                'status' => $booking->status,
            ],
        ]);
    }

    public function pay(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'payment_method' => 'required|in:card,upi',
        ]);

        try {
            $booking = Booking::findOrFail($request->booking_id);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking can only be paid when pending.',
            ], 422);
        }

        $payment = DB::transaction(function () use ($booking, $request) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $request->payment_method,
                'status' => 'paid',
                'amount' => round($booking->final_price, 2),
                'transaction_id' => 'TRX-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'meta' => json_encode(['paid_at' => Carbon::now()->toDateTimeString()]),
            ]);

            $booking->status = 'confirmed';
            $booking->save();

            return $payment;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ],
        ]);
    }

    private function calculateDiscount(float $totalPrice, int $stayDays, Carbon $checkIn): float
    {
        $discountAmount = 0;

        $longStay = Discount::where('type', 'long_stay')->first();
        if ($longStay && $stayDays >= $longStay->min_days) {
            $discountAmount += ($totalPrice * $longStay->value) / 100;
        }

        $lastMinute = Discount::where('type', 'last_minute')->first();
        $daysBeforeCheckIn = Carbon::today()->diffInDays($checkIn, false);

        if ($lastMinute && $daysBeforeCheckIn >= 0 && $daysBeforeCheckIn <= $lastMinute->days_before) {
            $discountAmount += ($totalPrice * $lastMinute->value) / 100;
        }

        return round($discountAmount, 2);
    }

    private function generateDateRange(Carbon $checkIn, Carbon $checkOut): array
    {
        $dates = [];

        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }
}
