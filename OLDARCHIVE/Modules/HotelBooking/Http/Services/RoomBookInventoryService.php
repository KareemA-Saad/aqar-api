<?php

namespace Modules\HotelBooking\Http\Services;


use Illuminate\Support\Carbon;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomTypeInventory;
use Modules\HotelBooking\Helpers\GlobalServiceHelpers;

class RoomBookInventoryService
{
    public static function get_next_days($days = 15,$format = null,$from_date = null): array
    {
        $arr = [];
        for($i = 0; $i < $days; $i++){
            $date = empty($from_date) ? Carbon::now()->addDay($i) : Carbon::parse($from_date)->addDay($i);
            $arr[] = [
                "day" => $date->format("d"),
                "month" => $date->format("F"),
                "year" => $date->format("Y"),
                "day_name" => $date->format("l"),
                "date" => $date->format("Y-m-d"),
                "full_date" => $format ? $date->format($format) : $date->toDateTimeString()
            ];
        }
        return $arr;
    }

    public static function get_from_to_next_days($from_date,$to_date,$format = null): array
    {
        $now = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::now()->format("Y-m-d h:i:s"));
        $requested_date = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::parse($from_date . ' ' . $now->format("h:i:s"))->format("Y-m-d h:i:s"));

        if(!$now->lte($requested_date)){
            return response()->json([
                "status" => "error",
                "error" => "Please select current date for future date"
            ],422);
        }

        $diff = GlobalServiceHelpers::differance_between_dates_single($from_date,$to_date);
        if(!$diff->invert){
            $all_days = RoomBookInventoryService::get_next_days($diff->d);
        }

        $arr = [];
        for($i = 0; $i < $diff->d; $i++){
            $date = Carbon::now()->addDay($i);
            $arr[] = [
                "day" => $date->format("d"),
                "month" => $date->format("F"),
                "year" => $date->format("Y"),
                "day_name" => $date->format("l"),
                "date" => $date->format("Y-m-d"),
                "full_date" => $format ? $date->format($format) : $date->toDateTimeString()
            ];
        }

        return $arr;
    }

    public static function prepare_array_for_database($data,$id){
        $arr = [];

        foreach($data as $item){
            $arr[] = [
                "room_type_id" => $id,
                "date" => $item["date"],
                "available_room" => $item["value"],
                "total_room" => $item["value"],
                "booked_room" => 0,
                "status" => 1
            ];
        }

        return $arr;
    }

    public static function get_room_inventories($id = null,$from = null,$to = null){
        if(!empty($id)){
            if(!empty($from) && !empty($to)){
                if(is_array($id)){
                    return RoomTypeInventory::with("rooms")->whereIn("id",$id)->whereBetween("date",[$from,$to])->get();
                }else{
                    return RoomTypeInventory::with("rooms")->where("id",$id)->whereBetween("date",[$from,$to])->get();
                }
            }else{
                if(is_array($id)) {
                    return RoomTypeInventory::with("rooms")->whereIn("id", $id)->get();
                }else{
                    return RoomTypeInventory::with("rooms")->where("id", $id)->get();
                }
            }
        }else{
            if(!empty($from) && !empty($to)){
                return Inventory::whereBetween("date",[$from,$to])->get();
            }else{
                return RoomTypeInventory::all();
            }
        }
    }

    public static function prepare_day_date_for_update($dates): ?array
    {
        if(is_array($dates) && !empty($dates)){
            $new_dates = [];
            foreach($dates as $date){
                $new_dates[] = $date;
            }
            return $new_dates;

        }
        return null;
    }
}
