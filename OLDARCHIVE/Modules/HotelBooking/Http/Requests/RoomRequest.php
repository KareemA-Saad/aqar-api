<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() : array
    {
        return [
            "name" => "required",
            "room_type_id" => "required",
            "base_cost" => "required",
            "share_value" => "required",
            "description" => "nullable",
            "image.*" => "nullable",
            "status" => "nullable"
        ];
    }


    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            "image" => explode("|",$this->image_gallery),
        ]);
    }
}
