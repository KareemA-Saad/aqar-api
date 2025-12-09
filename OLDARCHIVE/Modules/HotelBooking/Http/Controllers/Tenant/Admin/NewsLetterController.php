<?php

namespace Modules\HotelBooking\Http\Controllers\Tenant\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\HotelBooking\Emails\BasicMail;
use Modules\HotelBooking\Emails\SubscriberMessage;
use Modules\HotelBooking\Entities\Newsletter;
use Modules\HotelBooking\Http\Services\ServicesHelpers;

class NewsLetterController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $all_subscriber = Newsletter::orderBy('id','desc')->get();
        return view('hotelbooking::admin.newsletter.index',compact('all_subscriber'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('hotelbooking::create');
    }

    public function send_mail(Request $request){

        $request->validate([
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        $data = [
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        try {
            Mail::to($request->email)->send(new SubscriberMessage($data));
        } catch (\Throwable $th) {
            //throw $th;
        }

        return redirect()->back()->with([
            'msg' => __('Mail Send Success...'),
            'type' => 'success'
        ]);
    }

    public function add_new_sub(Request $request){
        $request->validate([
            'email' => 'required|email|unique:newsletters'
        ],
            [
                'email.required' => __('email field required')
            ]);

        $bool = Newsletter::create($request->all());
        return redirect(route('tenant.admin.newsletters.index'))->with(ServicesHelpers::send_response($bool,"create"));
    }

    public function verify_mail_send(Request $request){
        $subscriber_details = Newsletter::findOrFail($request->id);
        $token = $subscriber_details->token ?? Str::random(32);
        if (empty($subscriber_details->token)){
            $subscriber_details->token = $token;
            $subscriber_details->save();
        }
        $message = __('Verify your email to get all news from '). get_static_option('site_title') . '<div class="btn-wrap"> <a class="anchor-btn" href="' . route('tenant.admin.newsletters.subscriber.verify', ['token' => $token]) . '">' . __('verify email') . '</a></div>';
        $data = [
            'message' => $message,
            'subject' => __('verify your email')
        ];

        try {
            //send verify mail to newsletter subscriber
            Mail::to($subscriber_details->email)->send(new BasicMail($data));
        } catch (\Throwable $th) {
            //throw $th;
        }

        return redirect()->back()->with([
            'msg' => __('verify mail send success'),
            'type' => 'success'
        ]);
    }

    public function subscriber_verify(Request $request)
    {
        $newsletter = Newsletter::where('token', $request->token)->first();
        $title = __('Sorry');
        $description = __('your token is expired');
        if (!empty($newsletter)) {
            Newsletter::where('token', $request->token)->update([
                'verified' => 1
            ]);
            $title = __('Thanks');
            $description = __('we are really thankful to you for subscribe our newsletter');
        }
        return view('frontend.thankyou', compact('title', 'description'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' =>'required|email|unique:newsletters,email'
        ]);
        $bool= Newsletter::create($request->all());
       return back()->with(ServicesHelpers::send_response($bool,"created"));;
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

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('hotelbooking::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        Newsletter::findOrFail($id)->delete();
        return redirect()->back()->with(['msg' => __('Subscriber Delete Success....'),'type' => 'danger']);
    }
}
