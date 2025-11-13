<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Kos;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings for owner's kos.
     */
    public function index(Request $request)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            \Log::info('OwnerBookingController@index: Owner ID: ' . $owner->id);
            
            // Get all kos owned by this owner
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');
            
            \Log::info('OwnerBookingController@index: Found ' . $kosIds->count() . ' kos for owner');
            \Log::info('OwnerBookingController@index: Kos IDs: ' . $kosIds->implode(', '));
            
            // If owner has no kos, return empty array with debug info
            if ($kosIds->isEmpty()) {
                \Log::info('OwnerBookingController@index: Owner has no kos, returning empty array');
                $response = response()->json([
                    'success' => true,
                    'data' => [],
                    'debug' => [
                        'owner_id' => $owner->id,
                        'owner_name' => $owner->name,
                        'kos_count' => 0,
                        'message' => 'Owner belum memiliki kos. Silakan buat kos terlebih dahulu.'
                    ]
                ]);
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
            
            // Get all bookings for these kos
            $bookings = Booking::whereIn('kos_id', $kosIds)
                ->with([
                    'kos:id,name,address,user_id',
                    'room:id,room_number,room_type,kos_id',
                    'user:id,name,email,phone'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('OwnerBookingController@index: Found ' . $bookings->count() . ' bookings');
            
            // Log first booking details if exists
            if ($bookings->isNotEmpty()) {
                $firstBooking = $bookings->first();
                \Log::info('OwnerBookingController@index: First booking - ID: ' . $firstBooking->id . ', Kos ID: ' . $firstBooking->kos_id);
            } else {
                // Log all bookings in database to see if there are any bookings at all
                $allBookings = Booking::count();
                \Log::info('OwnerBookingController@index: Total bookings in database: ' . $allBookings);
                if ($allBookings > 0) {
                    $sampleBooking = Booking::with('kos')->first();
                    \Log::info('OwnerBookingController@index: Sample booking - ID: ' . $sampleBooking->id . ', Kos ID: ' . $sampleBooking->kos_id . ', Kos Owner ID: ' . ($sampleBooking->kos ? $sampleBooking->kos->user_id : 'N/A'));
                }
            }

            // Ensure bookings is converted to array format
            $bookingsArray = $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'kos_id' => $booking->kos_id,
                    'room_id' => $booking->room_id,
                    'user_id' => $booking->user_id,
                    'booking_code' => $booking->booking_code,
                    'start_date' => $booking->start_date?->toIso8601String(),
                    'end_date' => $booking->end_date?->toIso8601String(),
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'rejected_reason' => $booking->rejected_reason,
                    'created_at' => $booking->created_at?->toIso8601String(),
                    'updated_at' => $booking->updated_at?->toIso8601String(),
                    'kos' => $booking->kos ? [
                        'id' => $booking->kos->id,
                        'name' => $booking->kos->name,
                        'address' => $booking->kos->address,
                        'user_id' => $booking->kos->user_id,
                    ] : null,
                    'room' => $booking->room ? [
                        'id' => $booking->room->id,
                        'room_number' => $booking->room->room_number,
                        'room_type' => $booking->room->room_type,
                        'kos_id' => $booking->room->kos_id,
                    ] : null,
                    'user' => $booking->user ? [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                        'phone' => $booking->user->phone,
                    ] : null,
                ];
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => $bookingsArray,
                'debug' => [
                    'owner_id' => $owner->id,
                    'owner_name' => $owner->name,
                    'kos_count' => $kosIds->count(),
                    'kos_ids' => $kosIds->toArray(),
                    'bookings_count' => $bookings->count()
                ]
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerBookingController@index error: ' . $e->getMessage());
            \Log::error('OwnerBookingController@index stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching bookings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, Booking $booking)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Check if booking belongs to owner's kos
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');
            
            if (!$kosIds->contains($booking->kos_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this booking'
                ], 403);
            }
            
            $booking->load([
                'kos:id,name,address,user_id',
                'room:id,room_number,room_type,kos_id',
                'user:id,name,email,phone'
            ]);
            
            $bookingData = [
                'id' => $booking->id,
                'kos_id' => $booking->kos_id,
                'room_id' => $booking->room_id,
                'user_id' => $booking->user_id,
                'booking_code' => $booking->booking_code,
                'start_date' => $booking->start_date?->toIso8601String(),
                'end_date' => $booking->end_date?->toIso8601String(),
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'rejected_reason' => $booking->rejected_reason,
                'created_at' => $booking->created_at?->toIso8601String(),
                'updated_at' => $booking->updated_at?->toIso8601String(),
                'kos' => $booking->kos ? [
                    'id' => $booking->kos->id,
                    'name' => $booking->kos->name,
                    'address' => $booking->kos->address,
                    'user_id' => $booking->kos->user_id,
                ] : null,
                'room' => $booking->room ? [
                    'id' => $booking->room->id,
                    'room_number' => $booking->room->room_number,
                    'room_type' => $booking->room->room_type,
                    'kos_id' => $booking->room->kos_id,
                ] : null,
                'user' => $booking->user ? [
                    'id' => $booking->user->id,
                    'name' => $booking->user->name,
                    'email' => $booking->user->email,
                    'phone' => $booking->user->phone,
                ] : null,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $bookingData
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerBookingController@show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update booking status (approve/reject).
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Check if booking belongs to owner's kos
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');
            
            if (!$kosIds->contains($booking->kos_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this booking'
                ], 403);
            }
            
            $request->validate([
                'status' => 'required|in:approved,rejected,accept,reject',
                'rejected_reason' => 'nullable|string|max:500',
            ]);
            
            $statusInput = $request->input('status');
            // Convert 'approved' to 'accept' and 'rejected' to 'reject' for database compatibility
            $status = $statusInput === 'approved' ? 'accept' : ($statusInput === 'rejected' ? 'reject' : $statusInput);
            $rejectedReason = $request->input('rejected_reason');
            $isApproved = ($status === 'accept' || $statusInput === 'approved');
            
            // Only allow status change if current status is pending
            if ($booking->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking status cannot be changed. Current status: ' . $booking->status
                ], 400);
            }
            
            $booking->status = $status;
            if ($status === 'reject' && $rejectedReason) {
                $booking->rejected_reason = $rejectedReason;
            }
            $booking->save();
            
            $booking->load([
                'kos:id,name,address,user_id',
                'room:id,room_number,room_type,kos_id',
                'user:id,name,email,phone'
            ]);
            
            $bookingData = [
                'id' => $booking->id,
                'kos_id' => $booking->kos_id,
                'room_id' => $booking->room_id,
                'user_id' => $booking->user_id,
                'booking_code' => $booking->booking_code,
                'start_date' => $booking->start_date?->toIso8601String(),
                'end_date' => $booking->end_date?->toIso8601String(),
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'rejected_reason' => $booking->rejected_reason,
                'created_at' => $booking->created_at?->toIso8601String(),
                'updated_at' => $booking->updated_at?->toIso8601String(),
                'kos' => $booking->kos ? [
                    'id' => $booking->kos->id,
                    'name' => $booking->kos->name,
                    'address' => $booking->kos->address,
                    'user_id' => $booking->kos->user_id,
                ] : null,
                'room' => $booking->room ? [
                    'id' => $booking->room->id,
                    'room_number' => $booking->room->room_number,
                    'room_type' => $booking->room->room_type,
                    'kos_id' => $booking->room->kos_id,
                ] : null,
                'user' => $booking->user ? [
                    'id' => $booking->user->id,
                    'name' => $booking->user->name,
                    'email' => $booking->user->email,
                    'phone' => $booking->user->phone,
                ] : null,
            ];
            
            return response()->json([
                'success' => true,
                'message' => $isApproved ? 'Booking berhasil disetujui' : 'Booking berhasil ditolak',
                'data' => $bookingData
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('OwnerBookingController@updateStatus error: ' . $e->getMessage());
            \Log::error('OwnerBookingController@updateStatus stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Get transaction history/reports with filters.
     */
    public function report(Request $request)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Get all kos owned by this owner
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');
            
            if ($kosIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'summary' => [
                        'total_bookings' => 0,
                        'total_revenue' => 0,
                        'pending_count' => 0,
                        'accepted_count' => 0,
                        'rejected_count' => 0,
                    ]
                ], 200, [
                    'Content-Type' => 'application/json',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }
            
            // Build query
            $query = Booking::whereIn('kos_id', $kosIds)
                ->with([
                    'kos:id,name,address,user_id',
                    'room:id,room_number,room_type,kos_id',
                    'user:id,name,email,phone'
                ]);
            
            // Apply filters
            $month = $request->input('month'); // Format: YYYY-MM (e.g., 2025-08)
            $year = $request->input('year'); // Format: YYYY (e.g., 2025)
            $startDate = $request->input('start_date'); // Format: YYYY-MM-DD
            $endDate = $request->input('end_date'); // Format: YYYY-MM-DD
            $status = $request->input('status'); // pending, accept, reject
            
            // Filter by month and year
            if ($month) {
                $query->whereYear('created_at', substr($month, 0, 4))
                      ->whereMonth('created_at', substr($month, 5, 2));
            } elseif ($year) {
                $query->whereYear('created_at', $year);
            }
            
            // Filter by date range
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            
            // Filter by status
            if ($status) {
                // Convert 'approved' to 'accept' and 'rejected' to 'reject'
                $dbStatus = $status === 'approved' ? 'accept' : ($status === 'rejected' ? 'reject' : $status);
                $query->where('status', $dbStatus);
            }
            
            // Get bookings
            $bookings = $query->orderBy('created_at', 'desc')->get();
            
            // Calculate summary
            $totalRevenue = $bookings->where('status', 'accept')->sum('total_price');
            $pendingCount = $bookings->where('status', 'pending')->count();
            $acceptedCount = $bookings->where('status', 'accept')->count();
            $rejectedCount = $bookings->where('status', 'reject')->count();
            
            // Format bookings data
            $bookingsArray = $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'kos_id' => $booking->kos_id,
                    'room_id' => $booking->room_id,
                    'user_id' => $booking->user_id,
                    'booking_code' => $booking->booking_code,
                    'start_date' => $booking->start_date?->toIso8601String(),
                    'end_date' => $booking->end_date?->toIso8601String(),
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'rejected_reason' => $booking->rejected_reason,
                    'created_at' => $booking->created_at?->toIso8601String(),
                    'updated_at' => $booking->updated_at?->toIso8601String(),
                    'kos' => $booking->kos ? [
                        'id' => $booking->kos->id,
                        'name' => $booking->kos->name,
                        'address' => $booking->kos->address,
                        'user_id' => $booking->kos->user_id,
                    ] : null,
                    'room' => $booking->room ? [
                        'id' => $booking->room->id,
                        'room_number' => $booking->room->room_number,
                        'room_type' => $booking->room->room_type,
                        'kos_id' => $booking->room->kos_id,
                    ] : null,
                    'user' => $booking->user ? [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                        'phone' => $booking->user->phone,
                    ] : null,
                ];
            })->values()->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $bookingsArray,
                'summary' => [
                    'total_bookings' => $bookings->count(),
                    'total_revenue' => $totalRevenue,
                    'pending_count' => $pendingCount,
                    'accepted_count' => $acceptedCount,
                    'rejected_count' => $rejectedCount,
                ],
                'filters' => [
                    'month' => $month,
                    'year' => $year,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                ]
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerBookingController@report error: ' . $e->getMessage());
            \Log::error('OwnerBookingController@report stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics/statistics for owner dashboard.
     */
    public function analytics(Request $request)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Get all kos owned by this owner
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');
            
            // Total kos
            $totalKos = $kosIds->count();
            
            // Get all bookings for owner's kos
            $allBookings = Booking::whereIn('kos_id', $kosIds)
                ->with(['kos:id,name', 'room:id,room_number'])
                ->get();
            
            // Overall statistics
            $totalBookings = $allBookings->count();
            $totalRevenue = $allBookings->where('status', 'accept')->sum('total_price');
            $pendingCount = $allBookings->where('status', 'pending')->count();
            $acceptedCount = $allBookings->where('status', 'accept')->count();
            $rejectedCount = $allBookings->where('status', 'reject')->count();
            
            // Monthly statistics (last 12 months)
            $monthlyStats = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();
                
                $monthBookings = $allBookings->filter(function ($booking) use ($monthStart, $monthEnd) {
                    $bookingDate = \Carbon\Carbon::parse($booking->created_at);
                    return $bookingDate->between($monthStart, $monthEnd);
                });
                
                $monthRevenue = $monthBookings->where('status', 'accept')->sum('total_price');
                
                $monthlyStats[] = [
                    'month' => $date->format('Y-m'),
                    'month_label' => $date->format('M Y'),
                    'bookings' => $monthBookings->count(),
                    'revenue' => $monthRevenue,
                    'accepted' => $monthBookings->where('status', 'accept')->count(),
                    'pending' => $monthBookings->where('status', 'pending')->count(),
                    'rejected' => $monthBookings->where('status', 'reject')->count(),
                ];
            }
            
            // Statistics per kos
            $kosStats = [];
            foreach ($kosIds as $kosId) {
                $kos = Kos::find($kosId);
                if (!$kos) continue;
                
                $kosBookings = $allBookings->where('kos_id', $kosId);
                $kosRevenue = $kosBookings->where('status', 'accept')->sum('total_price');
                
                $kosStats[] = [
                    'kos_id' => $kosId,
                    'kos_name' => $kos->name,
                    'total_bookings' => $kosBookings->count(),
                    'total_revenue' => $kosRevenue,
                    'accepted' => $kosBookings->where('status', 'accept')->count(),
                    'pending' => $kosBookings->where('status', 'pending')->count(),
                    'rejected' => $kosBookings->where('status', 'reject')->count(),
                ];
            }
            
            // Average rating (from reviews)
            $reviews = \DB::table('reviews')
                ->whereIn('kos_id', $kosIds)
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
                ->first();
            
            $avgRating = $reviews ? round($reviews->avg_rating, 1) : 0;
            $totalReviews = $reviews ? $reviews->total_reviews : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_kos' => $totalKos,
                        'total_bookings' => $totalBookings,
                        'total_revenue' => $totalRevenue,
                        'pending_count' => $pendingCount,
                        'accepted_count' => $acceptedCount,
                        'rejected_count' => $rejectedCount,
                        'avg_rating' => $avgRating,
                        'total_reviews' => $totalReviews,
                    ],
                    'monthly_stats' => $monthlyStats,
                    'kos_stats' => $kosStats,
                ]
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerBookingController@analytics error: ' . $e->getMessage());
            \Log::error('OwnerBookingController@analytics stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
