<?php

namespace Modules\HotelBooking\Http\Services;



use App\Helpers\SanitizeInput;
use Modules\HotelBooking\Entities\BedType;

class BedTypeService
{
    public static function createOrUpdate($request,$bedType = null){
        if(is_null($bedType)){
            $bedType = new BedType();
        }
        $bedType->setTranslation('name',$request['lang'], SanitizeInput::esc_html($request['name']));
        $bedType->status = $request['status'];
        $bedType->save();
        return $bedType;
    }

}
