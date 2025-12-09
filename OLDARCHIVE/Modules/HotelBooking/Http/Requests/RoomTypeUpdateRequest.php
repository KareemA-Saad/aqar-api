<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeUpdateRequest extends FormRequest
{
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
        return [
            "name" => "required|unique:room_types,id," . $this->id,
            "max_adult" => "required",
            "max_child" => "required",
            "max_guest" => "required",
            "no_bedroom" => "required",
            "no_living_room" => "required",
            "no_bathrooms" => "required",
            "base_charge" => "required",
            "breakfast_price" => "required",
            "lunch_price" => "required",
            "dinner_price" => "required",
            "description" => "required",
            "amenities" => "required",
            "amenities.*" => "required",
            "extra_adult" => "nullable",
            "extra_child" => "nullable",
            "bed_type_id" => "nullable",
            "extra_bed_type_id" => "nullable",
            "hotel_id" => "required",
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            "extra_adult" => null,
            "extra_child" => null,
            "breakfast_price" => $this->breakfast_charge,
            "bed_type_id" => $this->bed_type,
            "extra_bed_type_id" => null,
            "no_bedroom" => $this->no_of_bedroom,
            "no_living_room" => $this->no_of_living_room,
            "no_bathrooms" => $this->no_of_bathroom,
            "hotel_id" => $this->hotel_id
        ]);
    }
}
