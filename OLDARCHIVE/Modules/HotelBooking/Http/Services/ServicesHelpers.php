<?php

namespace Modules\HotelBooking\Http\Services;

class ServicesHelpers
{
    public static function send_response($bool,$type,$info = false): array
    {
        if(!$info){
            if($bool){
                return ["success" => "true","msg" => self::message($type,$bool)];
            }else{
                return ["success" => "false","msg" => self::message($type,$bool)];
            }
        }else{
            return ["success" => $bool,"msg" => $info];
        }
    }

    private static function message($type,$bool): string
    {
        $msg = "";
        switch ($type){
            case $type == "create" and $bool:
                $msg =  "Item created successfully";
                break;
            case $type == "delete" and $bool:
                $msg =  "Item Deleted successfully";
                break;
                case $type == "cancel" and $bool:
                $msg =  "Item Canceled successfully";
                break;
            case $type == "update" and $bool:
                $msg =  "Item updated successfully";
                break;
            case $type == "delete" and !$bool:
                $msg =  "Item failed to delete";
                break;
            case $type == "update" and !$bool:
                $msg =  "Item failed to update";
                break;
            case $type == "create" and !$bool:
                $msg =  "Item failed to create";
                break;
            default:
                $msg = " ";
        }

        return $msg;
    }

    public static function prepare_for_insert_pivot_data($data,$id,$local,$parent){
        $dataVariable = [];
        foreach($data as $key => $value){
            $dataVariable[] = [
                $local => $id,
                $parent => $value,
            ];
        }

        return $dataVariable;
    }

    public static function validate_string($data){
        $data = htmlentities(htmlspecialchars($data));
        return filter_var($data,FILTER_SANITIZE_STRING);
    }
}
