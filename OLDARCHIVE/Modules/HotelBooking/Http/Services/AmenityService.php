<?php

namespace Modules\HotelBooking\Http\Services;



use Modules\HotelBooking\Entities\Amenity;
use App\Helpers\SanitizeInput;

class AmenityService
{
    public static function createOrUpdate($request,$amenity = null){
        if(is_null($amenity)){
            $amenity = new Amenity();
        }
        $amenity->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $amenity->icon = $request['icon'];
        $amenity->save();
        return $amenity;
    }

    public static function get_all_amenities($id = null){
        $amenities = Amenity::select("id","icon","name");

        if(empty($id)){
            return $amenities->paginate(20);
        }else{
            return $amenities->where("id",$id)->firstOrFail();
        }
    }

}



