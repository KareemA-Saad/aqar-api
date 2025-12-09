<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "name" => "required|string",
            "location" => "required|string",
            "about" => "required|string",
            "hotel_amenities" => "required",
            "hotel_amenities.*" => "numeric",
            "distance" => "required",
            "restaurant_inside" => "required",
            'status' => "required",
            'state_id' => "required",
            'country_id' => "required"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
