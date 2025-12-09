<?php

namespace Modules\HotelBooking\Http\Services;



use App\Helpers\SanitizeInput;
use Illuminate\Support\Str;
use Modules\HotelBooking\Entities\Room;
use Modules\HotelBooking\Entities\RoomImage;

class RoomService
{
    public static function createOrUpdate($request,$room = null){
        if(is_null($room)){
            $room = new Room();
        }
        $room->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $room->slug = Str::slug($request['name']);
        $room->setTranslation('description',$request['lang'], $request['description']);
        $room->room_type_id = $request['room_type_id'];
        $room->base_cost = $request['base_cost'];
        $room->share_value = $request['share_value'];
        $room->duration = $request['duration'];
        $room->status = $request['status'];
        $room->country_id = $request['country_id'];
        $room->state_id = $request['state_id'];
        $room->location = $request['location'];
        $room->location = $request['location'];
        $room->location = $request['location'];
        $room->is_featured = ($request['is_featured']=='on')?true:false;
        $room->type = $request['type'];
        $room->save();
        return $room;
    }

    public static function prepare_for_insert_pivot_data($data,$id,$local,$parent){
        $dataVariable = [];
        foreach($data as $key => $value){
            $dataVariable[] = [
                $local => $id,
                $parent => $value,
            ];
        }
        return $dataVariable;
    }

    public static function store_rooms($data){
        return Room::create($data);
    }

    public static function store_room_images($data,$id){
        // prepare room image for store
        $images = self::prepare_for_insert_pivot_data($data,$id,"room_id","image_id");
        return RoomImage::insert($images);
    }

    public static function delete_room_images($id)
    {
        return RoomImage::where("room_id",$id)->delete();
    }

}
