<?php

namespace Modules\HotelBooking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomInvetoryUpdateRequest extends FormRequest
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
            "name" => "required",
            "id" => "required",
            "selected_data" => "required|array",
        ];
    }
}
