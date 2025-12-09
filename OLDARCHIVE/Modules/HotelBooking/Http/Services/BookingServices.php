<?php

namespace Modules\HotelBooking\Http\Services;

use App\Helpers\FlashMsg;
use App\Helpers\Payment\PaymentGatewayCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\HotelBooking\Entities\BookingInformation;
use Modules\HotelBooking\Entities\HotelBookingPaymentLog;
use Modules\HotelBooking\Entities\HotelReview;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomInventory;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Entities\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Helpers\SanitizeInput;

class BookingServices
{
    private const SUCCESS_ROUTE = 'tenant.frontend.hotel-booking.payment.success';
    private const CANCEL_ROUTE = 'tenant.frontend.hotel-booking.payment.cancel';
    public static function get_room_type($id,$amount = false){
        return $amount ? RoomType::where("id",$id)->first() : optional(RoomType::where("id",$id)->first())->base_charge;
    }

    public static function diff_days($from_date,$to_date): \DateInterval {
        return Carbon::parse($from_date)->diff($to_date);
    }

    public static function calculate_amount_by_day($amount,$diff){
        return (!$diff->invert) ? $amount * $diff->d : false;
    }

    public static function get_available_room($booking_sell_info) {
        return Room::doesntHave("room_inventory","and",function ($query) use ($booking_sell_info){
            $query->whereBetween("booked_date",[self::clean_date($booking_sell_info["booking_date"]),self::clean_date($booking_sell_info["booking_expiry_date"])]);
        })->where("room_type_id",$booking_sell_info["room_type_id"])->first();
    }

    public static function all_available_room_search($search_data) {
        return Room::doesntHave("room_inventory","and",function ($query) use ($search_data){
            $query->whereBetween("booked_date",[self::clean_date($search_data["check_in"]),self::clean_date($search_data["check_out"])]);
        })->get();
    }

    public static function get_inventories_by_date_range($booking_sell_info,$select = null){
        return Inventory::whereBetween("date",[self::clean_date($booking_sell_info["booking_date"]),self::clean_date($booking_sell_info["booking_expiry_date"])])
            ->when(!empty($select) , function ($query) use ($select){
                $query->select($select);
            })
            ->orderBy("date","ASC")
            ->where("room_type_id",$booking_sell_info["room_type_id"])->get();
    }

    private static function clean_date($date)
    {
        return Carbon::parse(str_replace(" 00:00:00","",$date))->format("Y-m-d");
    }

    public static function count_available_room($data){
        return Room::doesntHave("room_inventory","and",function ($query) use ($data){
            $query->whereBetween("booked_date",[self::clean_date($data["from_date"]),self::clean_date($data["to_date"])]);
        })->where("room_type_id",$data["room_type_id"])->count();
    }

    public static function update_inventory($data,$id){
        return Inventory::where("id",$id)->update($data);
    }

    public static function get_day_wise_room_amount($request)
    {
        $info = [
            "booking_date" => $request->from_date,
            "booking_expiry_date" => Carbon::parse($request->to_date)->subDay(1)->format("Y-m-d"),
            "room_type_id" => $request->room_type_id
        ];
        $select = ["id","date","extra_base_charge"];
        $temp_price = [];

        // get all inventories with this information
        $inventories = self::get_inventories_by_date_range($info,$select);

        // get room type information
        $base_charge = self::get_room_type($request->room_type_id);
        // run a loop for calculate amount
        foreach($inventories as $inventory){
            $temp_price[] = [
                "base_price" => $base_charge,
                "extra_charge" => $inventory->extra_base_charge,
                "today_charge" => $inventory->extra_base_charge ?? $base_charge,
                "date" => Carbon::parse($inventory->date)->format("d F Y"),
            ];
        }

        return $temp_price;
    }

    public static function get_day_wise_room_sum_amount($from_date,$to_date,$room_type_id){
        $info = [
            "booking_date" => Carbon::parse($from_date)->format("Y-m-d"),
            "booking_expiry_date" => Carbon::parse($to_date)->subDay(1)->format("Y-m-d"),
            "room_type_id" => $room_type_id
        ];

        $select = ["id","date","extra_base_charge"];
        $temp_price = 0;

        // get all inventories with this information
        $inventories = self::get_inventories_by_date_range($info,$select);

        $gotted_inventory = optional($inventories->pluck("date"))->toArray();
        $period = CarbonPeriod::create($info["booking_date"], $info["booking_expiry_date"]);
        $requested_date = [];

        foreach ($period as $date) {
            $requested_date[] = $date->format('Y-m-d');
        }

        foreach($gotted_inventory as $item){
            $item = str_replace(" 00:00:00","",$item);
            // check if this date exists on requested_date than should you unset
            if(in_array($item,$requested_date)){
                unset($requested_date[array_search($item,$requested_date)]);
            }
        }

        // get room type information
        $base_charge = self::get_room_type($room_type_id);

        // run a loop for calculate amount
        foreach($inventories as $inventory){
            $temp_price +=  $inventory->extra_base_charge ?? $base_charge;
        }

        return [$temp_price,$requested_date];
    }

    public static function inventoryCreateAndUpdate($inventories_dates,$available_room){

        foreach($inventories_dates as $item){
            // insert room inventory table data first
            RoomInventory::insert([
                "room_type_id" => $item["room_type_id"],
                "inventory_id" => $item["id"],
                "booked_date" => $item["date"],
                "room_id" => $available_room->id,
                "status" => 1
            ]);

            //update inventory
            self::update_inventory([
                "available_room" => $item["available_room"] - 1,
                "booked_room" => $item["booked_room"] + 1,
            ], $item["id"]);
        }
    }

    public static function createBookingInformation($booking_sell_info){

        $reservation_id = Str::random(12);
        $bookingInformations = new BookingInformation();
        $bookingInformations->room_type_id = $booking_sell_info['room_type_id'];
        $bookingInformations->hotel_id = $booking_sell_info['hotel_id'];
        $bookingInformations->reservation_id = $reservation_id;
        $bookingInformations->user_id = Auth::guard('web')->user()?Auth::guard('web')->user()->id :100;
        $bookingInformations->email = $booking_sell_info['email'];
        $bookingInformations->mobile = $booking_sell_info['mobile'];
        $bookingInformations->post_code = $booking_sell_info['post_code'];
        $bookingInformations->booking_date = self::clean_date($booking_sell_info["booking_date"]);
        $bookingInformations->booking_expiry_date = self::clean_date($booking_sell_info["booking_expiry_date"]);
        $bookingInformations->booking_status = $booking_sell_info['booking_status'];
        $bookingInformations->payment_status = $booking_sell_info['payment_status'];
        $bookingInformations->payment_gateway = $booking_sell_info['payment_gateway'];
        $bookingInformations->payment_track = $booking_sell_info['payment_track'];
        $bookingInformations->transaction_id = $booking_sell_info['transaction_id'];
        $bookingInformations->amount = $booking_sell_info['total_tax_inc'];
        $bookingInformations->country_id = $booking_sell_info['country_id'];
        $bookingInformations->state_id = $booking_sell_info['state_id'];
        $bookingInformations->setTranslation('city',$booking_sell_info['lang'], SanitizeInput::esc_html($booking_sell_info['city']));
//        $booking_sell_info['notes'] ?$bookingInformations->setTranslation('notes',$booking_sell_info['lang'], SanitizeInput::esc_html($booking_sell_info['notes'])):"";
        $bookingInformations->save();
        return $bookingInformations;
    }

    public static function createHotelBookingPaymetLog($request,$bookingInformation)
    {
        $payment_details = HotelBookingPaymentLog::create([
            'booking_information_id' => $bookingInformation->id,
            'reservation_id' => $bookingInformation->reservation_id,
            'user_id' => Auth()->guard('web')->user()->id ?? 100,
            'name' => $request['name'] .''. $request['lname'],
            'email' => $request['email'],
            'phone' => $request['mobile'],
            'booking_date'=> self::clean_date($request["booking_date"]),
            'booking_expiry_date'=> self::clean_date($request["booking_expiry_date"]),
            'total_amount' => $request['total_tax_inc'],
            'coupon_type' => null,
            'coupon_code' => null,
            'coupon_discount' => null,
            'tax_amount' => $request['tax_amount'],
            'subtotal' => $request['amount'],
            'payment_gateway' => $request['selected_payment_gateway'],
            'payment_status' => 1
        ]);
        return $payment_details;
    }
    public static function paymentGateways($request,$payment_details)
    {
        if ($request['selected_payment_gateway'] === 'paypal') {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.paypal.ipn'));
            $paypal = PaymentGatewayCredential::get_paypal_credential();
            return $paypal->charge_customer($params);
        }
        elseif ($request['selected_payment_gateway'] === 'paytm') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.paytm.ipn'));
            $paytm = PaymentGatewayCredential::get_paytm_credential();
            return $paytm->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'mollie') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.mollie.ipn'));
            $mollie = PaymentGatewayCredential::get_mollie_credential();
            return $mollie->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'stripe') {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.stripe.ipn'));
            $stripe = PaymentGatewayCredential::get_stripe_credential();
            return $stripe->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'razorpay') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.razorpay.ipn'));
            $razorpay = PaymentGatewayCredential::get_razorpay_credential();
            return $razorpay->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'flutterwave') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.flutterwave.ipn'));
            $flutterwave = PaymentGatewayCredential::get_flutterwave_credential();
            return $flutterwave->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'paystack') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.paystack.ipn'));
            $paystack = PaymentGatewayCredential::get_paystack_credential();
            return $paystack->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] === 'midtrans') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.midtrans.ipn'));
            $midtrans = PaymentGatewayCredential::get_midtrans_credential();
            return $midtrans->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] == 'payfast') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.payfast.ipn'));
            $payfast = PaymentGatewayCredential::get_payfast_credential();
            return $payfast->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] == 'cashfree') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.cashfree.ipn'));
            $cashfree = PaymentGatewayCredential::get_cashfree_credential();
            return $cashfree->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] == 'instamojo') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.instamojo.ipn'));
            $instamojo = PaymentGatewayCredential::get_instamojo_credential();
            return $instamojo->charge_customer($params);

        } elseif ($request['selected_payment_gateway'] == 'marcadopago') {

            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.marcadopago.ipn'));
            $marcadopago = PaymentGatewayCredential::get_marcadopago_credential();
            return $marcadopago->charge_customer($params);

        }
        elseif($request['selected_payment_gateway'] == 'squareup')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.squareup.ipn'));
            $squareup = PaymentGatewayCredential::get_squareup_credential();
            return $squareup->charge_customer($params);
        }

        elseif($request['selected_payment_gateway'] == 'cinetpay')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.cinetpay.ipn'));
            $cinetpay = PaymentGatewayCredential::get_cinetpay_credential();
            return $cinetpay->charge_customer($params);
        }

        elseif($request['selected_payment_gateway'] == 'paytabs')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.paytabs.ipn'));
            $paytabs = PaymentGatewayCredential::get_paytabs_credential();
            return $paytabs->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'billplz')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.billplz.ipn'));
            $billplz = PaymentGatewayCredential::get_billplz_credential();
            return $billplz->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'zitopay')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.zitopay.ipn'));
            $zitopay = PaymentGatewayCredential::get_zitopay_credential();
            return $zitopay->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'toyyibpay')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.toyyibpay.ipn'));
            $toyyibpay = PaymentGatewayCredential::get_toyyibpay_credential();
            return $toyyibpay->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'pagali')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.pagali.ipn'));
            $pagali = PaymentGatewayCredential::get_pagali_credential();
            return $pagali->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'authorizenet')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.authorizenet.ipn'));
            $authorizenet = PaymentGatewayCredential::get_authorizenet_credential();
            return $authorizenet->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'sitesway')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.sitesway.ipn'));
            $sitesway = PaymentGatewayCredential::get_sitesway_credential();

            return $sitesway->charge_customer($params);
        }
        elseif($request['selected_payment_gateway'] == 'kinetic')
        {
            $params = self::common_charge_customer_data($request['total_tax_inc'],$payment_details,$request,route('tenant.frontend.hotel-booking.kinetic.ipn'));
            $kinetic = PaymentGatewayCredential::get_kinetic_credential();
            return $kinetic->charge_customer($params);
        }
        elseif ($request['selected_payment_gateway'] == 'bank_transfer')
        {
            $fileName = time().'.'.$request->manual_payment_attachment->extension();
            $request->manual_payment_attachment->move('assets/uploads/attachment/',$fileName);

            HotelBookingPaymentLog::where('id', $payment_details->id)->update([
                'status' => 'pending',
                'manual_payment_attachment' => $fileName
            ]);

            $customer_subject = __('Your Hotel Room booking payment sent and it is in admin approval stage..!').' '.get_static_option('site_'.get_user_lang().'_title');
            $admin_subject = __('You have a new Hotel Room booking with bank transfer, please check and approve..!').' '.get_static_option('site_'.get_user_lang().'_title');

            try {
//                Mail::to(get_static_option('tenant_site_global_email'))->send(new hotel-bookingMail($payment_details, $admin_subject,"admin"));
//                Mail::to($payment_details->email)->send(new hotel-bookingMail( $payment_details, $customer_subject,'user'));

            } catch (\Exception $e) {
                return redirect()->back()->with(['type' => 'danger', 'msg' => $e->getMessage()]);
            }

            $order_id = Str::random(6) .$payment_details->id . Str::random(6);

        }else if($request['selected_payment_gateway'] == 'manual_payment_')
        {

            $customer_subject = __('Your event payment sent and it is in admin approval stage..!').' '.get_static_option('site_'.get_user_lang().'_title');
            $admin_subject = __('You have a new event with manual payment, please check and approve..!').' '.get_static_option('site_'.get_user_lang().'_title');
            try {
//                Mail::to(get_static_option('tenant_site_global_email'))->send(new hotel-bookingMail($payment_details, $admin_subject,"admin"));
//                Mail::to($payment_details->email)->send(new hotel-bookingMail( $payment_details, $customer_subject,'user'));

            } catch (\Exception $e) {
                return redirect()->back()->with(['type' => 'danger', 'msg' => $e->getMessage()]);
            }

            HotelBookingPaymentLog::where('id', $payment_details->id)->update([
                'transaction_id' => $request->transaction_id,
                'status' => 'pending',
            ]);
            $order_id = Str::random(6) .$payment_details->id . Str::random(6);
            $booking_id = Str::random(6) .$payment_details->id . Str::random(6);
            return redirect()->route(self::SUCCESS_ROUTE,$order_id);

        }else{
            return self::payment_with_gateway($request['selected_payment_gateway'], $request['total_tax_inc'],$payment_details,$request);
        }
    }

    private static function common_charge_customer_data($total_amount,$payment_details,$request,$ipn_url) : array
    {
        $data = [
            'amount' => $request['total_tax_inc'],
            'title' => "Hotel Booking",
            'description' => 'Payment For hotel-booking Id: #' . $payment_details->id,
            'Payer Name: ' . $request->name . ' Payer Email:' . $request->email,
            'order_id' => $payment_details->id,
            'track' => $payment_details->id,
            'cancel_url' => route(self::CANCEL_ROUTE),
            'success_url' => route(self::SUCCESS_ROUTE, $payment_details->id),
            'email' => $payment_details->email,
            'name' => $payment_details->name,
            'payment_type' => 'hotel_booking',
            'ipn_url' => $ipn_url,
        ];

        return $data;
    }

    public static function payment_with_gateway($selected_payment_gateway, $total_amount, $payment_details, $request)
    {
        $gateway_function = 'get_' . $selected_payment_gateway . '_credential';

        if (!method_exists((new PaymentGatewayCredential()), $gateway_function))
        {
            $custom_data['request'] = $request;
            $custom_data['payment_details'] = $payment_details->toArray();
            $custom_data['total'] = $payment_details->total_tax_inc;

            //add extra param support to the shop checkout payment system
            $custom_data['payment_type'] = "event";
            $custom_data['payment_for'] = "tenant";
            $custom_data['cancel_url'] = route(self::CANCEL_ROUTE);
            $custom_data['success_url'] = route(self::SUCCESS_ROUTE, random_int(111111,999999) . $payment_details->id . random_int(111111,999999));

            $charge_customer_class_namespace = getChargeCustomerMethodNameByPaymentGatewayNameSpace($selected_payment_gateway);
            $charge_customer_method_name = getChargeCustomerMethodNameByPaymentGatewayName($selected_payment_gateway);

            $custom_charge_customer_class_object = new $charge_customer_class_namespace;
            if(class_exists($charge_customer_class_namespace) && method_exists($custom_charge_customer_class_object, $charge_customer_method_name))
            {
                try {
                    return $custom_charge_customer_class_object->$charge_customer_method_name($custom_data);
                }catch (\Exception $e){
                    return back()->with(FlashMsg::explain('danger', $e->getMessage()));
                }
            } else {
                return back()->with(FlashMsg::explain('danger', 'Incorrect Class or Method'));
            }
        } else {

            try {
                $gateway = PaymentGatewayCredential::$gateway_function();
                $params = self::common_charge_customer_data(
                    $total_amount,
                    $payment_details,
                    $request,
                    route('tenant.frontend.event.'.$selected_payment_gateway.'.ipn')
                );
                return $gateway->charge_customer($params);
            } catch (\Exception $e) {
                return back()->with(['msg' => $e->getMessage(), 'type' => 'danger']);
            }

        }


    }

    public  static function  reviewAvailability($room_type_id){
        if(Auth()->check()){
            $totoal_reservation = BookingInformation::where('user_id',Auth()->guard('web')->user()->id)->get()->count();

        $checkRoomType = BookingInformation::where('user_id',Auth()->guard('web')->user()->id)
            ->where('room_type_id',$room_type_id)->first();

        $total_review = HotelReview::where('user_id',Auth()->guard('web')->user()->id)->get()->count();

        if($total_review < $totoal_reservation && Auth()->guard('web')->check() && $checkRoomType)
        {
            return true;
        }else
        {
            return false;
        }
        }

    }
}
