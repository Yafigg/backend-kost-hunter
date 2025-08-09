<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Kos;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Display user's favorites
     */
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['kos:id,name,address,price_per_month,images'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Add kos to favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'kos_id' => 'required|exists:kos,id'
        ]);

        // Check if already favorited
        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('kos_id', $request->kos_id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Kos already in favorites'
            ], 400);
        }

        $favorite = Favorite::create([
            'user_id' => $request->user()->id,
            'kos_id' => $request->kos_id
        ]);

        $favorite->load(['kos:id,name,address,price_per_month']);

        return response()->json([
            'success' => true,
            'message' => 'Kos added to favorites',
            'data' => $favorite
        ], 201);
    }

    /**
     * Remove kos from favorites
     */
    public function destroy(Request $request, Favorite $favorite)
    {
        // Check ownership
        if ($favorite->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to remove this favorite'
            ], 403);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kos removed from favorites'
        ]);
    }
}
