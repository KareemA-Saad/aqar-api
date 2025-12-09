<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeBulkPriceUpdateRequest extends FormRequest
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
            "date" => 'required',
            "date.*" => "required",
            "extra_base_charge" => "nullable",
            "room_type_id" => "required"
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            "extra_base_charge" => $this->base_charge,
        ]);
    }

    public function messages()
    {
        return [
            "date.required" => "Please select day for update inventory",
            "date.*.required" => "Please select day to update inventory",
            "room_type_id.required" => "Wrong data given please reload the page.",
        ];
    }
}
