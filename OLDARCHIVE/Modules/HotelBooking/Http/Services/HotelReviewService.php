<?php

namespace Modules\HotelBooking\Http\Services;
class HotelReviewService
{
    public static function calculateRatting($request) {
        $average = array_sum($request) / count($request);
        round($average, 1);
        return $average;
    }


}
