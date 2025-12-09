<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Helpers\GlobalServiceHelpers;
use Modules\HotelBooking\Http\Requests\RoomInvetoryUpdateRequest;
use Modules\HotelBooking\Http\Requests\RoomTypeBulkPriceUpdateRequest;
use Modules\HotelBooking\Http\Requests\RoomTypeBulkUpdateRequest;
use Modules\HotelBooking\Http\Services\BookingServices;
use Modules\HotelBooking\Http\Services\RoomBookInventoryService;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class RoomBookInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-room-book-inventories-all',['only' => 'index','search_inventories','search']);
        $this->middleware('permission:hotel-manage-room-book-inventories-update',['only' => 'inventory_update']);
        $this->middleware('permission:hotel-manage-room-book-inventories-bulk-update',['only' => 'inventory_bulk_update']);
        $this->middleware('permission:hotel-manage-room-book-inventories-bulk-rate-update',['only' => 'inventory_price_update']);
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $all_room_types = RoomType::get();
        $all_days = RoomBookInventoryService::get_next_days($this->inventory_room_count_days());
        $all_inventories = RoomBookInventoryService::get_room_inventories(null,current($all_days)["date"],end($all_days)["date"]);
        return view("hotelbooking::admin.room_book_inventory.index",compact("all_room_types","all_days","all_inventories"));
    }

    public function search_inventories(Request $request){

        $all_room_types = RoomType::get();


         $lang_slug = request()->lang ?? \App\Facades\GlobalLanguage::default_slug();

        $diff = GlobalServiceHelpers::differance_between_dates_single($request->from_date,$request->to_date);

        $all_days = "";
        if(!$diff->invert){
            $all_days = RoomBookInventoryService::get_next_days($diff->d,null,$request->from_date);
        }
        $all_inventories = RoomBookInventoryService::get_room_inventories(null,current($all_days)["date"],end($all_days)["date"]);


        return view("hotelbooking::admin.room_book_inventory.search-inventory",compact("all_room_types","all_days","all_inventories","lang_slug"))->render();
    }

    public function inventory_update(RoomInvetoryUpdateRequest $request){
        // get room type id by name
        // filter all date only from request
        // prepare for createOrUpdate
        // send response for user

        $data = $request->validated();
        $iteration = 0;
        $final_data = RoomBookInventoryService::prepare_array_for_database($data["selected_data"],$data["id"]);

        $filtered_array = array_map(function ($d) {
            return $d["date"];
        }, $data["selected_data"]);

        foreach($filtered_array as $item){
            Inventory::where("room_type_id", $data["id"])->where("date",$item . " 00:00:00")
                ->updateOrCreate(["date" => $item . " 00:00:00"],$final_data[$iteration]);
            $iteration++;
        }

        return  response()->json(ServicesHelpers::send_response(true,"update"));
    }

    private function inventory_room_count_days(){
        return 14;
    }

    public function search(Request $request){
        $now = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::now()->format("Y-m-d h:i:s"));
        $requested_date = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::parse($request->from_date . ' ' . $now->format("h:i:s"))->format("Y-m-d h:i:s"));

        if(!$now->lte($requested_date)){
            return response()->json([
                "status" => "error",
                "error" => "Please select current date for future date"
            ],422);
        }

        $diff = GlobalServiceHelpers::differance_between_dates_single($request->from_date,$request->to_date);
        $all_days = [];
        if(!$diff->invert){
            $all_days = RoomBookInventoryService::get_next_days($diff->d,null,$request->from_date);
        }
        $room_type = BookingServices::get_room_type($request->room_type_id,true);
        $count_available_room = BookingServices::count_available_room($request);

        return view("hotelbooking::admin.room_book_inventory.days-search",compact("all_days","count_available_room","room_type"))->render();
    }

    public function price_search(Request $request){
        $now = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::now()->format("Y-m-d h:i:s"));
        $requested_date = Carbon::createFromFormat('Y-m-d h:i:s',Carbon::parse($request->from_date . ' ' . $now->format("h:i:s"))->format("Y-m-d h:i:s"));
        if(!$now->lte($requested_date)){
            return response()->json([
                "status" => "error",
                "error" => "Please select current date for future date"
            ],422);
        }

        $diff = GlobalServiceHelpers::differance_between_dates_single($request->from_date,$request->to_date);
        $all_days = "";
        if(!$diff->invert){
            $all_days = RoomBookInventoryService::get_next_days($diff->d,null,$request->from_date);
        }
        return view("hotelbooking::admin.room_book_inventory.price-days-search",compact("all_days"))->render();
    }

    public function inventory_bulk_update(RoomTypeBulkUpdateRequest $request){
        $data = $request->validated();

        $main_array = [];
        if(array_key_exists("refundable",$data)){
            $main_array["refundable"] = $data["refundable_select"];
        }else{
            $main_array["refundable"] = "";
        }

        if(array_key_exists("bookable",$data)){
            $main_array["bookable"] = $data["bookable_select"];
        }else{
            $main_array["bookable"] = "";
        }

        $update = Inventory::where("room_type_id",$data["room_type_id"])->whereIn("date",RoomBookInventoryService::prepare_day_date_for_update($data["date"]))
            ->update($main_array);

        return response()->json(ServicesHelpers::send_response($update,"update"));
    }

    public function inventory_price_update(RoomTypeBulkPriceUpdateRequest $request){
        $data = $request->validated();
        $main_array = ["extra_base_charge" => $data["extra_base_charge"]];
        $update = Inventory::where("room_type_id",$data["room_type_id"])->whereIn("date",RoomBookInventoryService::prepare_day_date_for_update($data["date"]))
            ->update($main_array);

        return response()->json(ServicesHelpers::send_response($update,"update"));
    }
}
