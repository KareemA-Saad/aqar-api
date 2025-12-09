<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Frontend;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AttractionController extends Controller
{
    private const BASE_PATH = 'hotel-booking.';
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function single_attraction(request $request)
    {
        $data = [
            'title' =>  $request->title,
            'image_id' =>  $request->image_id,
            'description' =>  $request->description,
        ];

        return themeView(self::BASE_PATH.'single-attraction',compact('data'));
    }

}
