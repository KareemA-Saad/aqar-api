<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelBooking\Entities\Room;
use Modules\HotelBooking\Entities\RoomImage;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Http\Services\RoomService;
use Modules\HotelBooking\Http\Services\ServicesHelpers;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-rooms-all|hotel-manage-rooms-create|hotel-manage-rooms-edit|hotel-manage-rooms-delete',['only' => 'index']);
        $this->middleware('permission:hotel-manage-rooms-create',['only' => 'create','store']);
        $this->middleware('permission:hotel-manage-rooms-edit',['only' => 'edit','update']);
        $this->middleware('permission:hotel-manage-rooms-delete',['only' => 'destroy']);;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $all_rooms = Room::select("id","name","base_cost","share_value","description","status")->orderBy('id','desc')->get();
        return view('hotelbooking::admin.room.index',compact('all_rooms'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $all_room_type = RoomType::select("id","name")->get();
        $all_countries =Country::get();
        $all_states = State::get();
        return view("hotelbooking::admin.room.create",compact("all_room_type","all_countries","all_states"));
    }


    public function store(Request $request)
    {
        $request->validate([
            "name" => "required",
            "room_type_id" => "required",
            "base_cost" => "required",
            "share_value" => "nullable",
            "description" => "nullable",
            "country_id" => "required",
            "state_id" => "required",
            "location" => "required",
            "image.*" => "nullable",
            "status" => "nullable",
            "type" => "required",
            "duration" => "nullable"
        ]);
        $image_array = explode("|", $request->image);
        $data = $request;
        unset($data["image"]);
        $id = optional(RoomService::createOrUpdate($data))->id;

        if($image_array[0] != ""){
            $bool = RoomService::store_room_images($image_array,$id);
        }

        return redirect(route('tenant.admin.rooms.index'))
            ->with(ServicesHelpers::send_response(true,"create"));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
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
        $room = Room::findOrFail($id);
        $all_room_type = RoomType::select("id","name")->get();
        $all_countries =Country::get();
        $all_states = State::where('country_id',$room->country_id)->get();
        $images = optional(optional(optional($room)->room_image)->pluck("image_id"))->toArray();
        return view('hotelbooking::admin.room.edit',compact('room','all_room_type','images','all_countries','all_states'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            "name" => "required",
            "room_type_id" => "required",
            "base_cost" => "required",
            "share_value" => "nullable",
            "description" => "nullable",
            "country_id" => "required",
            "state_id" => "required",
            "location" => "required",
            "image.*" => "nullable",
            "status" => "nullable",
            "type" => "required",
            "duration" => "nullable"
        ]);
        $image_array = explode("|", $request->image);

        $data = $request;
        unset($data["image"]);
        $room = Room::findOrFail($id);
        $id = optional(RoomService::createOrUpdate($data,$room))->id;
        if($image_array[0] != ""){
            RoomService::delete_room_images($id);
            $bool = RoomService::store_room_images($image_array,$id);
        }
        return redirect(route('tenant.admin.rooms.index'))
            ->with(ServicesHelpers::send_response(true,"update"));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        RoomImage::where('room_id',$id)->delete();
        $bool = $room->delete();
        return response()->json(["success" => $bool]);

    }
}
