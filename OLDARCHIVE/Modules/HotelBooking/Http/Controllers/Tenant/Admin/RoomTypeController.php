<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HotelBooking\Entities\Amenity;
use Modules\HotelBooking\Entities\BedType;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Http\Requests\RoomTypeRequest;
use Modules\HotelBooking\Http\Services\RoomTypeService;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class RoomTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-room_type-all|hotel-manage-room_type-create|hotel-manage-room_type-edit|hotel-manage-room_type-delete',['only' => 'index']);
        $this->middleware('permission:hotel-manage-room_type-create',['only' => 'create','store']);
        $this->middleware('permission:hotel-manage-room_type-edit',['only' => 'edit','update']);
        $this->middleware('permission:hotel-manage-room_type-delete',['only' => 'destroy']);;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('hotelbooking::admin.roomType.index')->with([
            'all_room_types' =>RoomType::get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('hotelbooking::admin.roomType.create')->with([
            'all_amenities'=>Amenity::select('id','name','icon')->get(),
            'all_bed_type'=>BedType::select('id','name')->get(),
        ]);
    }

    public function store(RoomTypeRequest $request)
    {
        $data = $request->validated();
        $data['lang'] = $request->lang;
        $amenities = isset($data['amenities'])?$data['amenities']:array();
        unset($data['amenities']);
        try {
            DB::beginTransaction();

            $room_type = RoomTypeService::createOrUpdate($data);
            RoomTypeService::CreateOrUpdateRoomTypeAmenities($amenities,$room_type);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error: ' . $e->getMessage());
            return redirect(route('tenant.admin.room-types.create'))->with(ServicesHelpers::send_response(false,"create"));
        }
        return redirect(route('tenant.admin.room-types.index'))->with(ServicesHelpers::send_response(true,"create"));
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
        $room_type = RoomType::findOrFail($id);
        $all_amenities = Amenity::select('id','name','icon')->get();
        $all_bed_type = BedType::select('id','name')->get();
        $all_hotels = Hotel::get();

        return view("hotelbooking::admin.roomType.edit", compact(["all_amenities","all_bed_type","room_type","all_hotels"]));
    }
    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(RoomTypeRequest $request, RoomType $room_type)
    {
        $data = $request->validated();
        $data['lang'] = $request->lang;
        $amenities = isset($data['amenities'])?$data['amenities']:array();
        unset($data['amenities']);

        try {
            DB::beginTransaction();

            RoomTypeService::createOrUpdate($data,$room_type);
            RoomTypeService::CreateOrUpdateRoomTypeAmenities($amenities,$room_type,$type="update");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error: ' . $e->getMessage());
            return redirect(route('tenant.admin.room-types.index'))->with(ServicesHelpers::send_response(false,"update"));
        }
        return redirect(route('tenant.admin.room-types.index'))->with(ServicesHelpers::send_response(true,"update"));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(RoomType $room_type)
    {
        $bool = $room_type->delete();
        return redirect(route('tenant.admin.room-types.index'))
            ->with(ServicesHelpers::send_response($bool,"delete"));
    }
}
