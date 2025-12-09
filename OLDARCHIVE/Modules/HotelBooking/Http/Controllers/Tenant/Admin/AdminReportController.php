<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelBooking\Entities\BookingInformation;

class AdminReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hotel-manage-hotel-booking-report',['only' => 'index','search','']);
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('hotelbooking::admin.report.index');
    }

    public function search(Request $request){
        // make validation here first
        $request->validate([
            "start_date" => "required",
            "end_date" => "required"
        ]);
        $bookingInformation = BookingInformation::with(["user","hotel","room_type"])->
        whereBetween("created_at",[$request->start_date,$request->end_date])->latest()->get()->groupBy(function ($query){
            return $query->created_at->format("d F Y");
        });

       if($bookingInformation->isEmpty()){
           return response()->json([
               'type' => 'worning',
               'warning_msg' => __('No Booking on this date range ')
           ]);
       }

        $filename = uniqid();

        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'. $filename .'.csv"',
        );

        return view("hotelbooking::admin.report.report-table",compact("bookingInformation"))->render();
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('hotelbooking::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
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
        return view('hotelbooking::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
