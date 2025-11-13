<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Kos;
use App\Models\KosRoom;
use App\Models\KosFacility;
use App\Models\KosImage;
use App\Models\PaymentMethod;
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
            ->with(['rooms', 'facilities', 'images', 'reviews.user'])
            ->withCount(['rooms', 'bookings', 'reviews'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kos
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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kos created successfully',
            'data' => $kos
        ], 201);
    }

    /**
     * Display specific kos
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
        ]);

        $kos->update($request->only([
            'name', 'address', 'description', 'price_per_month',
            'gender', 'whatsapp_number', 'latitude', 'longitude', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Kos updated successfully',
            'data' => $kos
        ]);
    }

    /**
     * Delete kos
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
            'rooms' => 'required|array',
            'rooms.*.room_number' => 'required|string|max:50',
            'rooms.*.room_type' => 'required|in:single,double',
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
     * Get facilities for a kos
     */
    public function getFacilities(Request $request, Kos $kos)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view facilities for this kos'
            ], 403);
        }

        $facilities = $kos->facilities()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $facilities
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
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
            'facilities' => 'required|array',
            'facilities.*.facility' => 'required|string|max:255',
            'facilities.*.icon' => 'nullable|string|max:50',
        ]);

        $facilities = [];
        foreach ($request->facilities as $facilityData) {
            $facilities[] = $kos->facilities()->create($facilityData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Facilities added successfully',
            'data' => $facilities
        ], 201, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Update a facility
     */
    public function updateFacility(Request $request, Kos $kos, $facilityId)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update facilities for this kos'
            ], 403);
        }

        $facility = KosFacility::where('id', $facilityId)
            ->where('kos_id', $kos->id)
            ->first();

        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => 'Facility not found'
            ], 404);
        }

        $request->validate([
            'facility' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
        ]);

        $facility->update([
            'facility' => $request->facility,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Facility updated successfully',
            'data' => $facility
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Delete a facility
     */
    public function deleteFacility(Request $request, Kos $kos, $facilityId)
    {
        // Check ownership
        if ($kos->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete facilities for this kos'
            ], 403);
        }

        $facility = KosFacility::where('id', $facilityId)
            ->where('kos_id', $kos->id)
            ->first();

        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => 'Facility not found'
            ], 404);
        }

        $facility->delete();

        return response()->json([
            'success' => true,
            'message' => 'Facility deleted successfully'
        ], 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ]);
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
                'message' => 'You do not have permission to upload images to this kos'
            ], 403);
        }

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:2048',
            'is_primary' => 'sometimes|integer|min:0'
        ]);

        $uploadedImages = [];
        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('kos-images', 'public');
            $isPrimary = $request->is_primary == $index;

            // If setting as primary, unset other primary images
            if ($isPrimary) {
                $kos->images()->update(['is_primary' => false]);
            }

            $uploadedImages[] = $kos->images()->create([
                'file' => $path,
                'is_primary' => $isPrimary
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
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
            'payment_methods' => 'required|array',
            'payment_methods.*.bank_name' => 'required|string|max:100',
            'payment_methods.*.account_number' => 'required|string|max:50',
            'payment_methods.*.account_name' => 'required|string|max:255',
            'payment_methods.*.type' => 'required|in:Transfer,Cash,QRIS',
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

            // Get all kos owned by this owner
            $kosIds = Kos::where('user_id', $owner->id)->pluck('id');

            if ($kosIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Get all reviews for owner's kos
            $reviews = DB::table('reviews')
                ->join('kos', 'reviews.kos_id', '=', 'kos.id')
                ->join('users', 'reviews.user_id', '=', 'users.id')
                ->leftJoin('review_replies', 'reviews.id', '=', 'review_replies.review_id')
                ->whereIn('reviews.kos_id', $kosIds)
                ->select(
                    'reviews.id',
                    'reviews.kos_id',
                    'reviews.user_id',
                    'reviews.comment',
                    'reviews.rating',
                    'reviews.created_at',
                    'reviews.updated_at',
                    'kos.name as kos_name',
                    'kos.address as kos_address',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.avatar as user_avatar',
                    'review_replies.owner_reply',
                    'review_replies.created_at as reply_created_at',
                    'review_replies.updated_at as reply_updated_at'
                )
                ->orderBy('reviews.created_at', 'desc')
                ->get();

            // Transform to array format
            $reviewsArray = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'kos_id' => $review->kos_id,
                    'user_id' => $review->user_id,
                    'comment' => $review->comment,
                    'rating' => $review->rating,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                    'kos' => [
                        'id' => $review->kos_id,
                        'name' => $review->kos_name,
                        'address' => $review->kos_address,
                    ],
                    'user' => [
                        'id' => $review->user_id,
                        'name' => $review->user_name,
                        'email' => $review->user_email,
                        'avatar' => $review->user_avatar,
                    ],
                    'reply' => $review->owner_reply ? [
                        'owner_reply' => $review->owner_reply,
                        'created_at' => $review->reply_created_at,
                        'updated_at' => $review->reply_updated_at,
                    ] : null,
                ];
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => $reviewsArray
            ], 200, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            \Log::error('OwnerKosController@getReviews error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to review
     */
    public function replyToReview(Request $request, $reviewId)
    {
        $request->validate([
            'owner_reply' => 'required|string'
        ]);

        // Check if review exists and belongs to owner's kos
        $review = DB::table('reviews')
            ->join('kos', 'reviews.kos_id', '=', 'kos.id')
            ->where('reviews.id', $reviewId)
            ->where('kos.user_id', $request->user()->id)
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you do not have permission to reply'
            ], 404);
        }

        // Check if reply already exists
        $existingReply = ReviewReply::where('review_id', $reviewId)->first();
        
        if ($existingReply) {
            $existingReply->update(['owner_reply' => $request->owner_reply]);
            $reply = $existingReply;
        } else {
            $reply = ReviewReply::create([
                'review_id' => $reviewId,
                'owner_reply' => $request->owner_reply
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'data' => $reply
        ]);
    }
}