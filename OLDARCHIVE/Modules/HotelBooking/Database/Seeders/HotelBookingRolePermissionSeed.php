<?php

namespace Modules\HotelBooking\Database\Seeders;


use App\Models\Widgets;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class HotelBookingRolePermissionSeed extends Seeder
{
    public static function run()
    {
        $package = tenant()->payment_log()->first()?->package()->first() ?? [];

        $all_features = $package->plan_features ?? [];

        $payment_log = tenant()->payment_log()?->first() ?? [];


        if(empty($all_features) && @$payment_log->status != 'trial'){
            return;
        }
        $check_feature_name = $all_features->pluck('feature_name')->toArray();


        $permissions = [
            "hotel-booking-module",

            "hotel-manage-bed-type-all",
            "hotel-manage-bed-type-create",
            "hotel-manage-bed-type-edit",
            "hotel-manage-bed-type-delete",

            "hotel-manage-amenities-all",
            "hotel-manage-amenities-create",
            "hotel-manage-amenities-edit",
            "hotel-manage-amenities-delete",

            "hotel-manage-hotels-all",
            "hotel-manage-hotels-create",
            "hotel-manage-hotels-edit",
            "hotel-manage-hotels-delete",

            "hotel-manage-room_type-all",
            "hotel-manage-room_type-create",
            "hotel-manage-room_type-edit",
            "hotel-manage-room_type-delete",

            "hotel-manage-rooms-all",
            "hotel-manage-rooms-create",
            "hotel-manage-rooms-edit",
            "hotel-manage-rooms-delete",

            "hotel-manage-hotel-bookings-all",
            "hotel-manage-hotel-bookings-create",
            "hotel-manage-hotel-bookings-approved-all",
            "hotel-manage-hotel-bookings-cancel-requested-all",
            "hotel-manage-hotel-bookings-canceled-all",
            "hotel-manage-hotel-bookings-payment_status_updated",

            "hotel-manage-room-book-inventories-all",
            "hotel-manage-room-book-inventories-update",

            "hotel-manage-hotel-reviews-all",

            "hotel-manage-hotel-booking-report"

        ];

        if (in_array('hotel_booking',$check_feature_name)) {
            foreach ($permissions as $permission){
                \Spatie\Permission\Models\Permission::updateOrCreate(['name' => $permission,'guard_name' => 'admin']);
            }
            $demo_permissions = [];
            $role = Role::updateOrCreate(['name' => 'Super Admin','guard_name' => 'admin'],['name' => 'Super Admin','guard_name' => 'admin']);
            $role->syncPermissions($demo_permissions);
        }
    }

}


