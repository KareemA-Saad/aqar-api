<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeBulkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "date" => "required",
            "date.*" => "required",
            "refundable" => "nullable",
            "refundable_select" => "nullable",
            "bookable" => "nullable",
            "bookable_select" => "nullable",
            "room_type_id" => "required",
        ];
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
