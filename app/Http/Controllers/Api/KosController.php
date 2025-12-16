<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kos;
use Illuminate\Http\Request;

class KosController extends Controller
{
    /**
     * Display listing of kos for society
     */
    public function index(Request $request)
    {
        $query = Kos::query()
            ->with(['images', 'facilities', 'owner:id,name,phone'])
            ->withCount(['reviews', 'bookings'])
            ->where('is_active', true);

        // Filter by gender
        if ($request->has('gender')) {
            if ($request->gender === 'mixed') {
                // 'mixed' means only show kos with gender = 'all' (campur)
                $query->where('gender', 'all');
            } elseif ($request->gender !== 'all') {
                // 'male' or 'female' - show kos with that gender or 'all'
                $query->byGender($request->gender);
            }
            // If gender is 'all', don't filter (show all kos)
        }

        // Search by name or address
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price_per_month', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_month', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price_per_month', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price_per_month', 'desc');
                break;
            case 'popular':
                $query->orderBy('view_count', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $kos = $query->paginate($request->get('per_page', 10));

        // Add average rating to each kos
        $kos->getCollection()->transform(function ($item) {
            $avgRating = $item->reviews()->avg('rating');
            $item->average_rating = $avgRating ? round($avgRating, 1) : 0;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $kos
        ]);
    }

    /**
     * Display specific kos detail
     */
    public function show(Kos $kos)
    {
        // Increment view count
        $kos->incrementViewCount();

        // Load relationships
        $kos->load([
            'owner:id,name,phone',
            'rooms' => function($query) {
                $query->where('is_available', true);
            },
            'facilities',
            'images',
            'reviews.user:id,name,avatar',
            'reviews.reply',
            'paymentMethods' => function($query) {
                $query->where('is_active', true);
            }
        ]);

        // Add average rating
        $avgRating = $kos->reviews()->avg('rating');
        $kos->average_rating = round($avgRating, 1);

        return response()->json([
            'success' => true,
            'data' => $kos
        ]);
    }
}