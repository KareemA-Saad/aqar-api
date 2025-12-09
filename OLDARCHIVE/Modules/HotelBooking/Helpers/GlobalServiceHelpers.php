<?php

namespace Modules\HotelBooking\Helpers;

use DateTime;

class GlobalServiceHelpers
{
    public static function pagination_limit(): int
    {
        return 20;
    }

    public static function get_parameter_as_string($secure = true)
    {
        // first check request type is get or not if secure is true
        if($secure){
            if(!request()->isMethod('GET')){
                return false;
            }
        }

        $all_parameter = request()->all();
        $count_parameter = count($all_parameter);
        $returned_string = "";

        // check parameter is bigger than 1
        if($count_parameter > 0){
            foreach($all_parameter as $key => $value){
                if(!is_array($value)){
                    $returned_string .= $key .'=' . $value . "&";
                }else{
                    foreach($value as $i_key => $item){
                        $returned_string .= $key . '%5B%5D= ' . $item . "&";
                    }
                }
            }

            // remove last & symbol from this string
            $returned_string = substr($returned_string,0,-1);
        }else{
            return "";
        }

        // return all parameter as string
        return '?' . $returned_string;
    }

    /**
     * @throws \Exception
     */
    public static function differance_between_dates($string, $separator = ' - '): \DateInterval
    {
        if(empty($string)){
            return false;
        }

        // make get string to array
        $sap_date = explode($separator,$string);
        $from_date = new DateTime($sap_date[0]);
        $to_date = new DateTime($sap_date[1]);

        return $from_date->diff($to_date);
    }

    /**
     * @throws \Exception
     */
    public static function differance_between_dates_single($from_date,$to_date): \DateInterval
    {
        // make get string to array
        $from_date = new DateTime($from_date);
        $to_date = new DateTime($to_date);

        return $from_date->diff($to_date);
    }

    public static function status_name_as_string($status): string
    {
        return $status ? "Active" : "in-Active";
    }

    public static function status_with_badge($status): string
    {
        return $status ? "<span class='badge badge-success py-2 px-4'>Active</span>" : "<span class='badge badge-danger py-2 px-4'>in-Active</span>";
    }
}
