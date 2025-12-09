<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use App\Facades\GlobalLanguage;
use Illuminate\Routing\Controller;
use Modules\HotelBooking\Entities\BedType;
use Modules\HotelBooking\Http\Requests\BedTypeRequest;
use Modules\HotelBooking\Http\Services\BedTypeService;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class BedTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-bed-type-all|hotel-manage-bed-type-create|hotel-manage-bed-type-edit|hotel-manage-bed-type-delete',['only' => 'index']);
        $this->middleware('permission:hotel-manage-bed-type-create',['only' => 'create','store']);
        $this->middleware('permission:hotel-manage-bed-type-edit',['only' => 'edit','update']);
        $this->middleware('permission:hotel-manage-bed-type-delete',['only' => 'destroy']);;
    }

    public function index()
    {
        return view('hotelbooking::admin.bedType.index')->with([
            'all_bed_types' => BedType::select("id","name","status")->orderBy('id','desc')->get(),
            'default_lang' => $request->lang ?? GlobalLanguage::default_slug(),
        ]);
    }
    public function create()
    {
        return view('hotelbooking::admin.bedType.create');
    }

    public function store(BedTypeRequest $request)
    {
        $bool = BedTypeService::createOrUpdate($request->all());
        return redirect(route('tenant.admin.bed-type.index'))->with(ServicesHelpers::send_response($bool,"create"));
    }

    public function edit($id)
    {
        return view('hotelbooking::admin.bedType.edit')->with([
            'bed_type' => BedType::where("id",$id)->firstOrFail(),
            'default_lang' => request()->lang ?? GlobalLanguage::default_slug(),
        ]);
    }

    public function update(BedTypeRequest $request, $id)
    {
        $bedType = BedType::findOrFail($id);
        $bool = BedTypeService::createOrUpdate($request->all(),$bedType);

        return redirect(route('tenant.admin.bed-type.index'))->with(ServicesHelpers::send_response($bool,"update"));
    }

    public function destroy($id)
    {
        $bed_type = BedType::findOrFail($id);
        $bool = $bed_type->delete();

        return response()->json(["success" => $bool]);
    }

}
