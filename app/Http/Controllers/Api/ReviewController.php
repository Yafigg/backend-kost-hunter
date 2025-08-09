<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Kos;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store new review for kos
     */
    public function store(Request $request, Kos $kos)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        // Check if user has booked this kos (optional validation)
        $hasBooking = Booking::where('user_id', $request->user()->id)
            ->where('kos_id', $kos->id)
            ->where('status', 'accept')
            ->exists();

        if (!$hasBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review kos that you have booked'
            ], 400);
        }

        // Check if user already reviewed this kos
        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('kos_id', $kos->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this kos'
            ], 400);
        }

        $review = Review::create([
            'kos_id' => $kos->id,
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
            'rating' => $request->rating
        ]);

        $review->load(['user:id,name,avatar', 'reply']);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Update user's review
     */
    public function update(Request $request, Review $review)
    {
        // Check ownership
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this review'
            ], 403);
        }

        $request->validate([
            'comment' => 'sometimes|string|max:1000',
            'rating' => 'sometimes|integer|min:1|max:5'
        ]);

        $review->update($request->only(['comment', 'rating']));
        $review->load(['user:id,name,avatar', 'reply']);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Delete user's review
     */
    public function destroy(Request $request, Review $review)
    {
        // Check ownership
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this review'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}