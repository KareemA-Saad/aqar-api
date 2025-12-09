<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use App\Facades\GlobalLanguage;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;
use Modules\HotelBooking\Entities\Amenity;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\HotelReview;
use Modules\HotelBooking\Http\Requests\HotelRequest;
use Modules\HotelBooking\Http\Services\AmenityService;
use Modules\HotelBooking\Http\Services\HotelServices;
use Modules\HotelBooking\Http\Services\ServicesHelpers;


class HotelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-hotels-all|hotel-manage-hotels-create|hotel-manage-hotels-edit|hotel-manage-hotels-delete',['only' => 'index','country_state','all_hotel_review']);
        $this->middleware('permission:hotel-manage-hotels-create',['only' => 'create','store']);
        $this->middleware('permission:hotel-manage-hotels-edit',['only' => 'edit','update']);
        $this->middleware('permission:hotel-manage-hotels-delete',['only' => 'destroy']);
        $this->middleware('permission:hotel-manage-hotel-reviews-all',['only' => 'all_hotel_review']);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $all_hotels = Hotel::select("id","name","distance","restaurant_inside","location","about","status")->orderBy('id','desc')->get();
        return view('hotelbooking::admin.hotel.index')->with([
            'default_lang' => $request->lang ?? GlobalLanguage::default_slug(),
            'all_hotels'=>$all_hotels
        ]);
    }

    public function all_hotel_review(Request $request)
    {
        $all_hotel_reviews = HotelReview::orderBy('id','desc')->get();
        return view('hotelbooking::admin.HotelReview.index',compact('all_hotel_reviews'));
    }

    public function create()
    {
        return view('hotelbooking::admin.hotel.create')->with([
            'all_amenities'=>Amenity::get(),
            'all_countries'=>Country::get(),
            'all_states'=>State::get(),
            'default_lang' => $request->lang ?? GlobalLanguage::default_slug(),
        ]);
    }

    public function store(HotelRequest $request)
    {
        $data = $request->validated();
        $data['lang'] = $request->lang;
        $amenities = $data["hotel_amenities"];
        unset($data["hotel_managers"]);
        unset($data["hotel_amenities"]);
        $id = HotelServices::CreateOrUpdate($data)->id;
        $bool =  HotelServices::create_hotel_amenities($amenities,$id);
        return view('hotelbooking::admin.hotel.index')->with([
            'default_lang' => $request->lang ?? GlobalLanguage::default_slug(),
            'all_hotels' => Hotel::select("id","name","distance","restaurant_inside","location","about","status")->orderBy('id','desc')->get()
        ]);
    }


    public function show($id)
    {
        return view('hotelbooking::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $hotel = Hotel::findOrFail($id);
        $all_amenities = AmenityService::get_all_amenities();
        $all_countries = Country::get();
        $all_states = State::get();
        $hotel["hotel_amenities"] = optional(optional(optional($hotel)->hotel_amenities)->pluck("id"))->toArray();
        return view('hotelbooking::admin.hotel.edit', compact('hotel','all_countries','all_states','all_amenities'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(HotelRequest $request, $id)
    {
        $data = $request->validated();
        $data['lang'] = $request->lang;
        $amenities = $data["hotel_amenities"];
        unset($data["hotel_amenities"]);
        $bool = HotelServices::prepare_for_hotels_update($data,$amenities,$id);

        return redirect(route('tenant.admin.hotels.index'))->with(ServicesHelpers::send_response($bool,"update"));
    }

    public function country_state($country_id)
    {
        $states = State::where('country_id',$country_id)->get();
        return  response()->json($states);
        return redirect(route('tenant.admin.hotels.index'))->with(ServicesHelpers::send_response($bool,"delete"));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        $bool = $hotel->delete();
        return redirect(route('tenant.admin.hotels.index'))->with(ServicesHelpers::send_response($bool,"delete"));
    }

}
