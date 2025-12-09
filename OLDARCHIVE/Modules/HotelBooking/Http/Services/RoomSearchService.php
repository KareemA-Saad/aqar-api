<?php

namespace Modules\HotelBooking\Http\Services;



use Illuminate\Support\Carbon;
use Modules\HotelBooking\Entities\Room;
use Modules\HotelBooking\Entities\RoomType;

class RoomSearchService
{
    public static function search($search_data){

       $rooms = Room::query()->with("room_types","room_types.country",'room_types.state')
           ->withCount("reviews")
           ->when(request()->person && !empty(request()->person),function($q) {
               $q->whereHas("room_types", function ($q) {
                   $q->where("max_adult", '>=', request()->person);
               });
           })
           ->when(request()->state && !empty(request()->state),function($q) {
            $q->where("state_id",'=',request()->state );
          })
          ->when(request()->property_type && !empty(request()->property_type),function($q) {
            $q->where("type",request()->property_type );
          })
          ->when(request()->keyword && !empty(request()->keyword),function($q) {
            $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
            $q->where("name",'like','%'.request()->keyword.'%');
          })
           ->when(request()->children && !empty(request()->children),function($q) {
               $q->whereHas("room_types",function ($query){
                   $query->where("max_child",'>=',request()->children );
               });
           })
           ->when(request()->check_in && !empty(request()->check_out) ,function($q) use($search_data) {
               $q->doesntHave("room_inventory","and",function ($query) use ($search_data){
                   $query->whereBetween("booked_date",[self::clean_date($search_data["check_in"]),self::clean_date($search_data["check_out"])]);
               });
           })
           ->when(request()->min_price && request()->max_price, function($q) use ($search_data) {
               $q->whereHas('room_types', function ($query) use ($search_data) {
                   $query->whereBetween('base_charge', [$search_data['min_price'], $search_data['max_price']]);
               });
           })
           ->when(request()->amenity_id && !empty(request()->amenity_id),function($q){
               $amenity_id = request()->amenity_id;
               $q->whereHas("room_types",function ($q) use ($amenity_id){
                   $q->whereHas("room_type_amenities",function ($query) use ($amenity_id) {
                       $query->where("amenity_id",$amenity_id);
                   });
               });
           })
            ->when(request()->ratting && !empty(request()->ratting),function($q){
                $ratting = request()->ratting;
                $q->whereHas("reviews",function ($query) use ($ratting){
                    $query->where("ratting","like",$ratting. '%');
                });
            });
        return $rooms->paginate(4);
    }

    private static function clean_date($date)
    {
        return Carbon::parse(str_replace(" 00:00:00","",$date))->format("Y-m-d");
    }

    public static function get_max_price_hotel_type($add_extra_price = null)
    {
        return RoomType::max("base_charge") + ($add_extra_price ?? 0);
    }
}
