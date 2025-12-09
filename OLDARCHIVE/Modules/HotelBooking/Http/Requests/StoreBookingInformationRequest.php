<?php

namespace Modules\HotelBooking\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Modules\HotelBooking\Http\Services\BookingServices;

/**
 * @property mixed $user
 */
class StoreBookingInformationRequest extends FormRequest
{
    private array $inventory_not_founded = [];
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
//        'sss'
        return [
            "country_id" => "required",
            "state_id" => "required",
            "city" => "nullable",
            "room_id" => "required",
            "street" => "nullable",
            "email" => "required|email",
            "mobile" => "required",
            "state" => "nullable",
            "post_code" => "required",
            "notes" => "nullable",
            "booking_date" => "required",
            "booking_expiry_date" => "required",
            "payment_gateway" => "required",
            "room_type_id" => "required",
            "hotel_id" => "required",
            "booking_status" => "required",
            "payment_status" => "required",
            "payment_track" => "required",
            "transaction_id" => "required",
            "amount" => "required",
            "payment_amount" => "nullable",
            "request_from" => "nullable"
        ];
    }

    protected function prepareForValidation()
    {
        $from_date = $this->carbon_date($this->booking_date);
        $to_date = $this->carbon_date($this->booking_expiry_date);
        $replace_time = " 00:00:00";

        $base_charge = BookingServices::get_day_wise_room_sum_amount(str_replace($replace_time,"",$from_date),str_replace($replace_time,"",$to_date),$this->room_type_id);

        // store all not founded inventory date
        $this->inventory_not_founded = $base_charge[1];

        $this->merge([
            "payment_gateway" => "cash_on_delivery",
            "booking_status" => 0,
            "payment_status" => 0,
            "payment_track" => Str::random(10) . Str::random(10),
            "transaction_id" => Str::random(10) . Str::random(10),
            "amount" => $base_charge[0],
            "requested_date" => $this->inventory_not_founded,
            "payment_type" => 0,
            "created_by" => '',
        ]);
    }

    private function carbon_date($date){
        return Carbon::parse($date)->format("Y-m-d") . ' 00:00:00' ?? null;
    }

    public function payment_gateway($gateway): int
    {
        if($gateway == "partial_payment_gateway"){
            return 2;
        }else if($gateway == "razorpay"){
            return 1;
        }else{
            return 0;
        }
    }
}
