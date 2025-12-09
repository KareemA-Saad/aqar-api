<?php

namespace Modules\HotelBooking\Http\Services;



use App\Helpers\SanitizeInput;
use Modules\HotelBooking\Entities\Amenity;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\HotelAmenity;
use Modules\HotelBooking\Entities\HotelImage;
use Illuminate\Support\Str;

class HotelServices
{
    public static function createOrUpdate($request,$hotel = null){
        if(is_null($hotel)){
            $hotel = new Hotel();
        }
        $hotel->country_id = $request['country_id'];
        $hotel->state_id = $request['state_id'];
        $hotel->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $hotel->slug = Str::slug($request['name']);
        $hotel->setTranslation('location',$request['lang'], SanitizeInput::esc_html($request['location']));
        $hotel->setTranslation('about',$request['lang'], SanitizeInput::esc_html($request['about']));
        $hotel->setTranslation('distance',$request['lang'], SanitizeInput::esc_html($request['distance']));
        $hotel->restaurant_inside = $request['restaurant_inside'];
        $hotel->status = $request['status'];
        $hotel->save();
        return $hotel;
    }

    public static function createOrUpdateHotelAmenity($request,$hotel_amenity = null){
        if(is_null($hotel_amenity)){
            $hotel = new Amenity();
        }

        $hotel->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $hotel->setTranslation('location',$request['lang'], SanitizeInput::esc_html($request['location']));
        $hotel->setTranslation('about',$request['lang'], SanitizeInput::esc_html($request['about']));
        $hotel->distance = $request['distance'];
        $hotel->restaurant_inside = $request['restaurant_inside'];
        $hotel->status = $request['status'];
        $hotel->save();
        return $hotel;
    }

    public static function create_hotel_amenities($data,$id): bool
    {
        $data = self::prepare_hotel_amenities_data($data,$id);
        return HotelAmenity::insert($data);
    }
    public static function prepare_hotel_amenities_data($data,$id): array
    {
        $dataVariable = [];
        foreach($data as $key => $value){
            $dataVariable[] = [
                "hotel_id" => $id,
                "amenities_id" => $value,
            ];
        }

        return $dataVariable;
    }

    public static function create_hotel_image($data,$id){
        $data = ServicesHelpers::prepare_for_insert_pivot_data($data,$id,"hotel_id","image_id");

        return HotelImage::insert($data);
    }

    public static function prepare_for_hotels_update($data,$amenities,$id): bool
    {
        // update hotel
        $hotel = Hotel::find($id);
        $hotel = self::createOrUpdate($data,$hotel);

        // delete hotel manager and hotel amenities and hotel images
        $delete_hotel =  self::delete_hotel_amenities($id) && self::delete_hotel_images($id);
        // insert hotel manager hotel amenities and hotel images
        $insert_hotel = self::create_hotel_amenities($amenities,$id);

        if($hotel && $delete_hotel && $insert_hotel){
            return true;
        }else{
            return false;
        }
    }


    public static function delete_hotel_amenities($id){
        return HotelAmenity::where("hotel_id",$id)->delete();
    }

    public static function delete_hotel_images($id){
        return HotelImage::where("hotel_id",$id)->delete();
    }


}
