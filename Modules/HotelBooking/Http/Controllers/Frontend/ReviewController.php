<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreReviewRequest;
use Modules\HotelBooking\Http\Resources\ReviewResource;
use Modules\HotelBooking\Entities\BookingInformation;
use Modules\HotelBooking\Entities\Review;
use Modules\HotelBooking\Entities\Hotel;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reviews', description: 'Hotel review endpoints')]
class ReviewController extends Controller
{
    #[OA\Get(
        path: '/api/v1/hotels/{hotelId}/reviews',
        summary: 'Get hotel reviews',
        description: 'Get paginated reviews for a hotel',
        tags: ['Reviews'],
        parameters: [
            new OA\Parameter(name: 'hotelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['newest', 'oldest', 'rating_high', 'rating_low'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reviews retrieved'),
            new OA\Response(response: 404, description: 'Hotel not found'),
        ]
    )]
    public function index(Request $request, int $hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => __('Hotel not found.'),
            ], 404);
        }

        $query = Review::with('user')
            ->where('hotel_id', $hotelId)
            ->where('status', 'approved');

        // Sort
        $sortBy = $request->sort_by ?? 'newest';
        $query = match ($sortBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating_high' => $query->orderByDesc('rating'),
            'rating_low' => $query->orderBy('rating', 'asc'),
            default => $query->orderByDesc('created_at'),
        };

        $reviews = $query->paginate($request->per_page ?? 10);

        // Calculate stats
        $stats = Review::where('hotel_id', $hotelId)
            ->where('status', 'approved')
            ->selectRaw('
                AVG(rating) as average_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews),
                'stats' => [
                    'average_rating' => round($stats->average_rating ?? 0, 1),
                    'total_reviews' => $stats->total_reviews ?? 0,
                    'distribution' => [
                        5 => $stats->five_star ?? 0,
                        4 => $stats->four_star ?? 0,
                        3 => $stats->three_star ?? 0,
                        2 => $stats->two_star ?? 0,
                        1 => $stats->one_star ?? 0,
                    ],
                ],
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/hotels/{hotelId}/reviews',
        summary: 'Submit a review',
        description: 'Submit a review for a hotel (requires completed booking)',
        tags: ['Reviews'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreReviewRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Review submitted'),
            new OA\Response(response: 400, description: 'Cannot submit review'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreReviewRequest $request, int $hotelId): JsonResponse
    {
        $hotel = Hotel::find($hotelId);

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => __('Hotel not found.'),
            ], 404);
        }

        $userId = auth()->id();

        // Check if user has a completed booking at this hotel
        $hasCompletedBooking = BookingInformation::where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->where('status', BookingInformation::STATUS_CHECKED_OUT)
            ->exists();

        if (!$hasCompletedBooking) {
            return response()->json([
                'success' => false,
                'message' => __('You can only review hotels where you have completed a stay.'),
            ], 400);
        }

        // Check if user already reviewed this hotel
        $existingReview = Review::where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->exists();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => __('You have already reviewed this hotel.'),
            ], 400);
        }

        $review = Review::create([
            'hotel_id' => $hotelId,
            'user_id' => $userId,
            'booking_id' => $request->booking_id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'pros' => $request->pros,
            'cons' => $request->cons,
            'cleanliness_rating' => $request->cleanliness_rating,
            'service_rating' => $request->service_rating,
            'location_rating' => $request->location_rating,
            'value_rating' => $request->value_rating,
            'status' => 'pending', // Requires approval
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Review submitted successfully. It will be visible after approval.'),
            'data' => new ReviewResource($review),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/reviews/my-reviews',
        summary: 'Get user reviews',
        description: 'Get reviews submitted by authenticated user',
        tags: ['Reviews'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User reviews'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function myReviews(): JsonResponse
    {
        $reviews = Review::with(['hotel'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/reviews/{id}',
        summary: 'Update review',
        description: 'Update a review (own review only)',
        tags: ['Reviews'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreReviewRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Review updated'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Review not found'),
        ]
    )]
    public function update(StoreReviewRequest $request, int $id): JsonResponse
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => __('Review not found.'),
            ], 404);
        }

        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized.'),
            ], 403);
        }

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'pros' => $request->pros,
            'cons' => $request->cons,
            'cleanliness_rating' => $request->cleanliness_rating,
            'service_rating' => $request->service_rating,
            'location_rating' => $request->location_rating,
            'value_rating' => $request->value_rating,
            'status' => 'pending', // Re-submit for approval
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Review updated successfully. It will be visible after approval.'),
            'data' => new ReviewResource($review),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/reviews/{id}',
        summary: 'Delete review',
        description: 'Delete a review (own review only)',
        tags: ['Reviews'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review deleted'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Review not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => __('Review not found.'),
            ], 404);
        }

        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized.'),
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => __('Review deleted successfully.'),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/hotels/{hotelId}/can-review',
        summary: 'Check if user can review',
        description: 'Check if authenticated user can submit a review for a hotel',
        tags: ['Reviews'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review eligibility'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function canReview(int $hotelId): JsonResponse
    {
        $userId = auth()->id();

        $hasCompletedBooking = BookingInformation::where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->where('status', BookingInformation::STATUS_CHECKED_OUT)
            ->exists();

        $hasExistingReview = Review::where('hotel_id', $hotelId)
            ->where('user_id', $userId)
            ->exists();

        $canReview = $hasCompletedBooking && !$hasExistingReview;

        return response()->json([
            'success' => true,
            'data' => [
                'can_review' => $canReview,
                'has_completed_booking' => $hasCompletedBooking,
                'has_existing_review' => $hasExistingReview,
            ],
        ]);
    }
}
