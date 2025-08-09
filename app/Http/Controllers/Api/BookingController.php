<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Kos;
use App\Models\KosRoom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display user's bookings
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with(['kos:id,name,address', 'room:id,room_number,room_type'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Store new booking
     */
    public function store(Request $request)
    {
        $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'room_id' => 'required|exists:kos_rooms,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Check if room belongs to kos
        $room = KosRoom::where('id', $request->room_id)
            ->where('kos_id', $request->kos_id)
            ->where('is_available', true)
            ->first();

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not available or not found'
            ], 400);
        }

        // Check for overlapping bookings
        $overlapping = Booking::where('room_id', $request->room_id)
            ->where('status', '!=', 'reject')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Room is already booked for selected dates'
            ], 400);
        }

        // Calculate total price
        $kos = Kos::find($request->kos_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $months = $startDate->diffInMonths($endDate) ?: 1;
        $totalPrice = $kos->price_per_month * $months;

        // Create booking
        $booking = Booking::create([
            'kos_id' => $request->kos_id,
            'room_id' => $request->room_id,
            'user_id' => $request->user()->id,
            'booking_code' => Booking::generateBookingCode(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_price' => $totalPrice,
            'status' => 'pending'
        ]);

        $booking->load(['kos:id,name,address', 'room:id,room_number,room_type']);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 201);
    }

    /**
     * Display specific booking
     */
    public function show(Request $request, Booking $booking)
    {
        // Check ownership - Society checks if booking belongs to them
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this booking'
            ], 403);
        }

        $booking->load([
            'kos.owner:id,name,phone',
            'kos:id,name,address,whatsapp_number',
            'room:id,room_number,room_type'
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Generate booking receipt/nota
     */
    public function receipt(Request $request, Booking $booking)
    {
        // Check ownership
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this receipt'
            ], 403);
        }

        $booking->load([
            'kos.owner:id,name,phone',
            'kos:id,name,address,whatsapp_number',
            'room:id,room_number,room_type',
            'user:id,name,email,phone'
        ]);

        $receipt = [
            'booking_code' => $booking->booking_code,
            'booking_date' => $booking->created_at->format('d/m/Y H:i'),
            'status' => $booking->status,
            'kos' => [
                'name' => $booking->kos->name,
                'address' => $booking->kos->address,
                'owner' => $booking->kos->owner->name,
                'phone' => $booking->kos->owner->phone,
            ],
            'room' => [
                'number' => $booking->room->room_number,
                'type' => $booking->room->room_type,
            ],
            'customer' => [
                'name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone' => $booking->user->phone,
            ],
            'period' => [
                'start_date' => $booking->start_date->format('d/m/Y'),
                'end_date' => $booking->end_date->format('d/m/Y'),
                'duration' => $booking->start_date->diffInMonths($booking->end_date) . ' month(s)',
            ],
            'payment' => [
                'total_price' => $booking->total_price,
                'formatted_price' => 'Rp ' . number_format($booking->total_price, 0, ',', '.'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $receipt
        ]);
    }
}