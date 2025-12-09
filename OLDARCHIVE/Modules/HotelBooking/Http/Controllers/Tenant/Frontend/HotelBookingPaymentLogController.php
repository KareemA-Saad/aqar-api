<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Frontend;

use App\Helpers\FlashMsg;
use App\Helpers\Payment\PaymentGatewayCredential;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HotelBooking\Entities\HotelBookingPaymentLog;
use Modules\HotelBooking\Helpers\Payment\Tenant\TenantHotelBooking;
use Modules\HotelBooking\Http\Requests\StoreBookingInformationRequest;
use Modules\HotelBooking\Http\Services\BookingServices;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class HotelBookingPaymentLogController extends Controller
{
    private const SUCCESS_ROUTE = 'tenant.frontend.hotel-booking.payment.success';
    private const CANCEL_ROUTE = 'tenant.frontend.hotel-booking.payment.cancel';
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('hotelbooking::index');
    }

    public function paypal_ipn()
    {
        $paypal = PaymentGatewayCredential::get_paypal_credential();
        $payment_data = $paypal->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function paytm_ipn()
    {
        $paytm = PaymentGatewayCredential::get_paytm_credential();
        $payment_data = $paytm->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function flutterwave_ipn()
    {
        $flutterwave = PaymentGatewayCredential::get_flutterwave_credential();
        $payment_data = $flutterwave->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function stripe_ipn()
    {
        $stripe = PaymentGatewayCredential::get_stripe_credential();
        $payment_data = $stripe->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function razorpay_ipn()
    {
        $razorpay = PaymentGatewayCredential::get_razorpay_credential();
        $payment_data = $razorpay->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function paystack_ipn()
    {
        $paystack = PaymentGatewayCredential::get_paystack_credential();
        $payment_data = $paystack->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function payfast_ipn()
    {
        $payfast = PaymentGatewayCredential::get_payfast_credential();
        $payment_data = $payfast->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function mollie_ipn()
    {
        $mollie = PaymentGatewayCredential::get_mollie_credential();
        $payment_data = $mollie->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function midtrans_ipn()
    {
        $midtrans = PaymentGatewayCredential::get_midtrans_credential();
        $payment_data = $midtrans->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function cashfree_ipn()
    {
        $cashfree = PaymentGatewayCredential::get_cashfree_credential();
        $payment_data = $cashfree->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function instamojo_ipn()
    {
        $instamojo = PaymentGatewayCredential::get_instamojo_credential();
        $payment_data = $instamojo->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function marcadopago_ipn()
    {
        $marcadopago = PaymentGatewayCredential::get_marcadopago_credential();
        $payment_data = $marcadopago->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function squareup_ipn()
    {
        $squareup = PaymentGatewayCredential::get_squareup_credential();
        $payment_data = $squareup->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function cinetpay_ipn()
    {
        $cinetpay = PaymentGatewayCredential::get_cinetpay_credential();
        $payment_data = $cinetpay->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function paytabs_ipn()
    {
        $paytabs = PaymentGatewayCredential::get_paytabs_credential();
        $payment_data = $paytabs->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function billplz_ipn()
    {
        $billplz = PaymentGatewayCredential::get_billplz_credential();
        $payment_data = $billplz->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function zitopay_ipn()
    {
        $zitopay = PaymentGatewayCredential::get_zitopay_credential();
        $payment_data = $zitopay->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function toyyibpay_ipn()
    {
        $toyyibpay = PaymentGatewayCredential::get_toyyibpay_credential();
        $payment_data = $toyyibpay->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function pagali_ipn()
    {
        $pagali_ipn = PaymentGatewayCredential::get_pagali_credential();
        $payment_data = $pagali_ipn->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function authorizenet_ipn()
    {
        $authorize_ipn = PaymentGatewayCredential::get_authorizenet_credential();
        $payment_data = $authorize_ipn->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function sitesway_ipn()
    {
        $sitesway_ipn = PaymentGatewayCredential::get_sitesway_credential();
        $payment_data = $sitesway_ipn->ipn_response();
        return $this->common_ipn_data($payment_data);
    }

    public function kinetic_ipn()
    {
        $kinetic_ipn = PaymentGatewayCredential::get_kinetic_credential();
        $payment_data = $kinetic_ipn->ipn_response();
        return $this->common_ipn_data($payment_data);
    }


    private function common_ipn_data($payment_data)
    {
        if (isset($payment_data['status']) && $payment_data['status'] === 'complete') {

          TenantHotelBooking::update_database($payment_data['order_id'], $payment_data['transaction_id'] ?? Str::random(15));
//          TenantHotelBooking::send_event_mail($payment_data['order_id']);

            $order_id = wrap_random_number($payment_data['order_id']);
            return redirect()->route(self::SUCCESS_ROUTE, $order_id);
        }
            return redirect()->back()->with(ServicesHelpers::send_response(false,"create"));

    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('hotelbooking::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function hotel_booking_store(StoreBookingInformationRequest $request)
    {
        if (!Auth::guard('web')->check())
        {
            return redirect()->back()->withErrors(["msg" => "Please Login first to success your reservation"]);
        }
        $booking_details = $request->validated();
        $booking_details['total_tax_inc'] = $request->total_tax_inc;
        $booking_details['tax_amount'] = $request->tax_amount;
        $booking_details['lang'] = $request->lang;

        if(!$booking_details)
        {
            return redirect()->back()->withErrors(["msg" => "Please selecte correct data"]);
        }
        // get all available room base on selected date range
        $available_room = BookingServices::get_available_room($booking_details);

        // if room not have then user will be redirected with error message
        if($available_room == null){
            return redirect()->back()->withErrors(['msg' => "Room Not available",'type' => 'danger' ]);
        }
        // now get all inventories list that are available for book
        $inventories_date = BookingServices::get_inventories_by_date_range($booking_details)->toArray();

        // remove last booking date
        array_pop($inventories_date);

        DB::beginTransaction();
        try {
            //Store Booking Information
            $booking_information  = BookingServices::createBookingInformation($booking_details);
            //Store HotelBookingPaymentLog
            $payment_details  = BookingServices::createHotelBookingPaymetLog($request,$booking_information);

            //RoomInventory create and Inventory update
            BookingServices::inventoryCreateAndUpdate($inventories_date, $available_room);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: ' . $e->getMessage());
            return redirect()->back()->with(ServicesHelpers::send_response(false,"create"));
        }
              DB::beginTransaction();
        try {
            $payment_gateways  = BookingServices::paymentGateways($request,$payment_details);
            DB::commit();
            return $payment_gateways;
        }catch(\Exception $e)
        {
            DB::rollBack();
            Log::error('Error: ' . $e->getMessage());
            return redirect()->back()->with(ServicesHelpers::send_response(false,"create"));
        }
        session()->put(["type"=>"success"]);

        $data =  HotelBookingPaymentLog::findOrFail($payment_details->id);

        return redirect()->route("tenant.frontend.confirmation")->with('data',$payment_details);
    }

    public function payment_with_gateway($selected_payment_gateway, $total_amount, $payment_details, $request)
    {
        $gateway_function = 'get_' . $selected_payment_gateway . '_credential';

        if (!method_exists((new PaymentGatewayCredential()), $gateway_function))
        {
            $custom_data['request'] = $request;
            $custom_data['payment_details'] = $payment_details->toArray();
            $custom_data['total'] = $payment_details->amount;

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
                $params = $this->common_charge_customer_data(
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

    public function hotel_booking_payment_success($id)
    {
        $extract_id = substr($id, 6);
        $extract_id =  substr($extract_id, 0, -6);

        $payment_details = '';
        if(!empty($extract_id)){
            $payment_details = HotelBookingPaymentLog::find($extract_id);
        }
        return redirect()->route("tenant.frontend.confirmation")->with("data",$payment_details);
    }

    public function hotel_booking_payment_cancel()
    {
        return back();
//        return themeView(self::BASE_PATH.'hotel-booking-payment.cancel');
    }

    private function common_charge_customer_data($total_amount,$payment_details,$request,$ipn_url) : array
    {
        $data = [
            'amount' => $total_amount,
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

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('hotelbooking::show');
    }

    public function edit($id)
    {
        return view('hotelbooking::edit');
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
