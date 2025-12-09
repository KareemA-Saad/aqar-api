<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "name" => "required|unique:room_types,name,". @$this->room_type->id,
            "extra_adult" => "nullable",
            "extra_child" => "nullable",
            "bed_type_id" => "nullable",
            "extra_bed_type_id" => "nullable",
            "max_adult" => "nullable|numeric",
            "max_child" => "nullable|numeric",
            "max_guest" => "nullable|numeric",
            "no_bedroom" => "nullable|numeric",
            "no_living_room" => "nullable|numeric",
            "no_bathrooms" => "nullable|numeric",
            "base_charge" => "nullable|numeric",
            "breakfast_price" => "nullable|numeric",
            "lunch_price" => "nullable|numeric",
            "dinner_price" => "nullable|numeric",
            "description" => "nullable",
            "amenities" => "nullable",
            "hotel_id" => "nullable",
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
