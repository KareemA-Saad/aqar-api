<?php

namespace Modules\HotelBooking\Http\Services;



use App\Helpers\SanitizeInput;
use Modules\HotelBooking\Entities\Room_type;
use Modules\HotelBooking\Entities\Room_typeAmenity;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Entities\RoomTypeAmenity;

class RoomTypeService
{
    public static function createOrUpdate($request,$roomType = null){
        if(is_null($roomType)){
            $roomType = new RoomType();
        }
        $roomType->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $roomType->save();

        return $roomType;
    }


    public static function CreateOrUpdateRoomTypeAmenities($data,$room_type, $type= null){

        if(is_null($type)){
            // insert room type amenities
            $amenities = self::store_room_type_amenities($data,$room_type->id);
        }else{
            // first delete previous amenities
            self::delete_room_type_amenities($room_type->id);
            //prepare amenities for store
            $amenities = self::prepare_room_type_amenities($data,$room_type->id);
            // insert amenities
            $amenities = self::store_room_type_amenities($data,$room_type->id);
        }
    }

    public static function store_room_type_amenities($data,$id){
        $data = self::prepare_room_type_amenities($data,$id);
        return RoomTypeAmenity::insert($data);
    }

    private static function prepare_room_type_amenities($data,$id){
        $dataVariable = [];
        foreach($data as $key => $value){
            $dataVariable[] = [
                "room_type_id" => $id,
                "amenity_id" => $value,
            ];
        }
        return $dataVariable;
    }

    public static function delete_room_type_amenities($id){
        return RoomTypeAmenity::where("room_type_id",$id)->delete();
    }

}
