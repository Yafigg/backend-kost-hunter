<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Kos;
use App\Models\KosRoom;
use App\Models\KosFacility;
use App\Models\KosImage;
use App\Models\PaymentMethod;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KosController extends Controller
{
    /**
     * Display listing of owner's kos
     */
    public function index(Request $request)
    {
        $kos = Kos::where('user_id', $request->user()->id)
            ->with(['rooms', 'facilities', 'images', 'reviews.user', 'paymentMethods'])
            ->withCount(['rooms', 'bookings', 'reviews'])
            ->get();

        // Ensure paymentMethods are included in JSON response
        // Laravel automatically converts camelCase relationships to snake_case in JSON
        // So paymentMethods becomes payment_methods in JSON response
        $kosArray = $kos->map(function ($k) {
            // Force load paymentMethods if not loaded
            if (!$k->relationLoaded('paymentMethods')) {
                $k->load('paymentMethods');
            }
            
            // Get base array - toArray() should include loaded relationships
            $array = $k->toArray();
            
            // Explicitly ensure payment_methods is in the array
            // (Laravel's toArray() should include it, but we make sure)
            if (!isset($array['payment_methods'])) {
                if ($k->paymentMethods && $k->paymentMethods->count() > 0) {
                    $array['payment_methods'] = $k->paymentMethods->map(function ($pm) {
                        return [
                            'id' => $pm->id,
                            'kos_id' => $pm->kos_id,
                            'bank_name' => $pm->bank_name,
                            'account_number' => $pm->account_number,
                            'account_name' => $pm->account_name,
                            'type' => $pm->type,
                            'is_active' => $pm->is_active,
                            'created_at' => $pm->created_at,
                            'updated_at' => $pm->updated_at,
                        ];
                    })->toArray();
                } else {
                    $array['payment_methods'] = [];
                }
            }
            
            return $array;
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $kosArray
        ]);
    }

    /**
     * Store a new kos
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'required|string',
            'price_per_month' => 'required|numeric|min:0',
            'gender' => 'required|in:male,female,all',
            'whatsapp_number' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $kos = Kos::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'address' => $request->address,
            'description' => $request->description,
            'price_per_month' => $request->price_per_month,
            'gender' => $request->gender,
            'whatsapp_number' => $request->whatsapp_number,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_active' => true,
        ]);

        $kos->load(['rooms', 'facilities', 'images', 'paymentMethods']);

        return response()->json([
            'success' => true,
            'message' => 'Kos created successfully',
            'data' => $kos
        ], 201);
    }

    /**
     * Display the specified kos
     */
    public function show(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this kos'
            ], 403);
        }

        $kos->load(['rooms', 'facilities', 'images', 'reviews.user', 'paymentMethods', 'bookings.user']);

        return response()->json([
            'success' => true,
            'data' => $kos
        ]);
    }

    /**
     * Update kos
     */
    public function update(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this kos'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price_per_month' => 'sometimes|numeric|min:0',
            'gender' => 'sometimes|in:male,female,all',
            'whatsapp_number' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
            'facilities' => 'sometimes|array',
            'facilities.*' => 'string|max:255',
            'payment_methods' => 'sometimes|array',
            'payment_methods.*' => 'string|max:255',
        ]);

        $kos->update($request->only([
            'name', 'address', 'description', 'price_per_month',
            'gender', 'whatsapp_number', 'latitude', 'longitude', 'is_active'
        ]));

        // Sync facilities if provided
        if ($request->has('facilities')) {
            // Delete all existing facilities
            $kos->facilities()->delete();
            
            // Create new facilities
            foreach ($request->facilities as $facilityName) {
                $kos->facilities()->create([
                    'facility' => $facilityName,
                    'icon' => null, // Default icon, can be customized later
                ]);
            }
        }

        // Sync payment methods if provided
        if ($request->has('payment_methods')) {
            \Log::info('DEBUG KosController.update: Received payment_methods', [
                'payment_methods' => $request->payment_methods,
                'kos_id' => $kos->id,
            ]);
            
            // Delete all existing payment methods
            $kos->paymentMethods()->delete();
            \Log::info('DEBUG KosController.update: Deleted existing payment methods');
            
            // Create new payment methods
            foreach ($request->payment_methods as $paymentMethodName) {
                // Map frontend payment method names to backend types
                $type = 'Transfer'; // Default
                if (strtolower($paymentMethodName) === 'cash') {
                    $type = 'Cash';
                } elseif (strtolower($paymentMethodName) === 'bulanan' || strtolower($paymentMethodName) === 'monthly') {
                    $type = 'Transfer'; // Monthly payment is still a transfer
                } elseif (strtolower($paymentMethodName) === 'tahunan' || strtolower($paymentMethodName) === 'yearly') {
                    $type = 'Transfer'; // Yearly payment is still a transfer
                } elseif (strtolower($paymentMethodName) === 'transfer' || strtolower($paymentMethodName) === 'transfer bank') {
                    $type = 'Transfer';
                } elseif (strtolower($paymentMethodName) === 'qris' || strtolower($paymentMethodName) === 'ovo' || strtolower($paymentMethodName) === 'gopay' || strtolower($paymentMethodName) === 'e-wallet') {
                    $type = 'QRIS';
                }
                
                $paymentMethod = $kos->paymentMethods()->create([
                    'bank_name' => $paymentMethodName,
                    'account_number' => '',
                    'account_name' => '',
                    'type' => $type,
                    'is_active' => true,
                ]);
                
                \Log::info('DEBUG KosController.update: Created payment method', [
                    'id' => $paymentMethod->id,
                    'bank_name' => $paymentMethod->bank_name,
                    'type' => $paymentMethod->type,
                ]);
            }
            
            \Log::info('DEBUG KosController.update: Created ' . count($request->payment_methods) . ' payment methods');
        }

        // Reload relationships
        $kos->load(['facilities', 'paymentMethods']);

        return response()->json([
            'success' => true,
            'message' => 'Kos updated successfully',
            'data' => $kos
        ]);
    }

    /**
     * Remove the specified kos
     */
    public function destroy(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this kos'
            ], 403);
        }

        $kos->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kos deleted successfully'
        ]);
    }

    /**
     * Add rooms to kos
     */
    public function addRooms(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add rooms to this kos'
            ], 403);
        }

        $request->validate([
            'rooms' => 'required|array|min:1',
            'rooms.*.room_number' => 'required|string|max:50',
            'rooms.*.room_type' => 'required|in:single,double,triple,quad',
            'rooms.*.price' => 'required|numeric|min:0',
            'rooms.*.is_available' => 'sometimes|boolean',
        ]);

        $rooms = [];
        foreach ($request->rooms as $roomData) {
            $rooms[] = $kos->rooms()->create($roomData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rooms added successfully',
            'data' => $rooms
        ], 201);
    }

    /**
     * Update room
     */
    public function updateRoom(Request $request, Kos $kos, KosRoom $room)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id || $room->kos_id !== $kos->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this room'
            ], 403);
        }

        $request->validate([
            'room_number' => 'sometimes|string|max:50',
            'room_type' => 'sometimes|in:single,double,triple,quad',
            'price' => 'sometimes|numeric|min:0',
            'is_available' => 'sometimes|boolean',
        ]);

        $room->update($request->only(['room_number', 'room_type', 'price', 'is_available']));

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    /**
     * Delete room
     */
    public function deleteRoom(Request $request, Kos $kos, KosRoom $room)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id || $room->kos_id !== $kos->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this room'
            ], 403);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    /**
     * Add facilities to kos
     */
    public function addFacilities(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add facilities to this kos'
            ], 403);
        }

        $request->validate([
            'facilities' => 'required|array|min:1',
            'facilities.*.facility' => 'required|string|max:255',
            'facilities.*.icon' => 'nullable|string|max:255',
        ]);

        $facilities = [];
        foreach ($request->facilities as $facilityData) {
            $facilities[] = $kos->facilities()->create($facilityData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Facilities added successfully',
            'data' => $facilities
        ], 201);
    }

    /**
     * Add payment methods to kos
     */
    public function addPaymentMethods(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add payment methods to this kos'
            ], 403);
        }

        $request->validate([
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*.bank_name' => 'required|string|max:255',
            'payment_methods.*.account_number' => 'nullable|string|max:50',
            'payment_methods.*.account_name' => 'nullable|string|max:255',
            'payment_methods.*.type' => 'required|in:Cash,Transfer,QRIS',
            'payment_methods.*.is_active' => 'sometimes|boolean',
        ]);

        $paymentMethods = [];
        foreach ($request->payment_methods as $paymentData) {
            $paymentMethods[] = $kos->paymentMethods()->create($paymentData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment methods added successfully',
            'data' => $paymentMethods
        ], 201);
    }

    /**
     * Upload images for kos
     */
    public function uploadImages(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to upload images for this kos'
            ], 403);
        }

        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('kos_images', 'public');
            $uploadedImages[] = $kos->images()->create([
                'file' => $path,
                'is_primary' => $kos->images()->count() === 0, // First image is primary
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
        ], 201);
    }

    /**
     * Delete image
     */
    public function deleteImage(Request $request, Kos $kos, KosImage $image)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id || $image->kos_id !== $kos->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this image'
            ], 403);
        }

        // Delete file from storage
        if ($image->file && \Storage::disk('public')->exists($image->file)) {
            \Storage::disk('public')->delete($image->file);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage(Request $request, Kos $kos, KosImage $image)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id || $image->kos_id !== $kos->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to set primary image for this kos'
            ], 403);
        }

        // Unset all primary images
        $kos->images()->update(['is_primary' => false]);

        // Set this image as primary
        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary image set successfully',
            'data' => $image
        ]);
    }

    /**
     * Get kos statistics
     */
    public function statistics(Request $request)
    {
        $userId = $request->user()->id;

        $totalKos = Kos::where('user_id', $userId)->count();
        $activeKos = Kos::where('user_id', $userId)->where('is_active', true)->count();
        $totalRooms = KosRoom::whereHas('kos', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();
        $availableRooms = KosRoom::whereHas('kos', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('is_available', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_kos' => $totalKos,
                'active_kos' => $activeKos,
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
            ]
        ]);
    }

    /**
     * Get all reviews for owner's kos
     */
    public function getReviews(Request $request)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get all kos IDs owned by this owner
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');

            if ($kosIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Get all reviews for owner's kos with user and reply information
            $reviews = Review::whereIn('kos_id', $kosIds)
                ->with(['user:id,name,email,avatar', 'kos:id,name,address', 'reply'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Format reviews data
            $reviewsData = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'kos_id' => $review->kos_id,
                    'user_id' => $review->user_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'email' => $review->user->email,
                        'avatar' => $review->user->avatar,
                    ] : null,
                    'kos' => $review->kos ? [
                        'id' => $review->kos->id,
                        'name' => $review->kos->name,
                        'address' => $review->kos->address,
                    ] : null,
                    'reply' => $review->reply ? [
                        'id' => $review->reply->id,
                        'owner_reply' => $review->reply->owner_reply,
                        'created_at' => $review->reply->created_at,
                        'updated_at' => $review->reply->updated_at,
                    ] : null,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $reviewsData
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerKosController@getReviews error: ' . $e->getMessage());
            \Log::error('OwnerKosController@getReviews stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to a review
     */
    public function replyToReview(Request $request, Review $review)
    {
        try {
            $owner = $request->user();
            
            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Check if the review belongs to owner's kos
            $kos = $review->kos;
            if (!$kos || $kos->user_id !== $owner->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to reply to this review'
                ], 403);
            }

            $request->validate([
                'reply' => 'required|string|max:1000',
            ]);

            // Check if reply already exists
            $existingReply = ReviewReply::where('review_id', $review->id)->first();

            if ($existingReply) {
                // Update existing reply
                $existingReply->update([
                    'owner_reply' => $request->reply,
                ]);
                
                $reply = $existingReply;
            } else {
                // Create new reply
                $reply = ReviewReply::create([
                    'review_id' => $review->id,
                    'owner_reply' => $request->reply,
                ]);
            }

            // Reload review with reply
            $review->load(['user:id,name,email,avatar', 'kos:id,name,address', 'reply']);

            return response()->json([
                'success' => true,
                'message' => $existingReply ? 'Reply updated successfully' : 'Reply sent successfully',
                'data' => [
                    'id' => $review->id,
                    'kos_id' => $review->kos_id,
                    'user_id' => $review->user_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'email' => $review->user->email,
                        'avatar' => $review->user->avatar,
                    ] : null,
                    'kos' => $review->kos ? [
                        'id' => $review->kos->id,
                        'name' => $review->kos->name,
                        'address' => $review->kos->address,
                    ] : null,
                    'reply' => [
                        'id' => $reply->id,
                        'owner_reply' => $reply->owner_reply,
                        'created_at' => $reply->created_at,
                        'updated_at' => $reply->updated_at,
                    ],
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('OwnerKosController@replyToReview error: ' . $e->getMessage());
            \Log::error('OwnerKosController@replyToReview stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error replying to review: ' . $e->getMessage()
            ], 500);
        }
    }
}
