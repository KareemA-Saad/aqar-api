<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Frontend;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelBooking\Entities\HotelReview;
use Modules\HotelBooking\Http\Services\HotelReviewService;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class HotelReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function all_hotel_review()
    {
        $all_hotel_reviews = HotelReview::orderBy('id','desc')->get();
        return view('hotelbooking::admin.HotelReview.index',compact('all_hotel_reviews'));
    }

    public function hotel_review(Request $request)
    {
        $request->validate([
            'comfort'=> "required|numeric|between:1,5",
            'cleanliness'=> "required|numeric|between:1,5",
            'staff'=> "required|numeric|between:1,5",
            'facilities'=> "required|numeric|between:1,5",
            'description'=>"required"
        ]);

      // getting all rattings
        $ratings = [];
        $ratings[]=$request->comfort;
        $ratings[]=$request->cleanliness;
        $ratings[]=$request->staff;
        $ratings[]=$request->facilities;

        $ratings_array = $ratings;

       $average = HotelReviewService::calculateRatting($ratings_array);
       $request['ratting'] = $average;
       $bool = HotelReview::create($request->all());
       return back()->with(ServicesHelpers::send_response($bool,"create"));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */

}
