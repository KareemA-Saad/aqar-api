<?php

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;

use Spatie\Translatable\HasTranslations;
class BookingInformation extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        "id",
        "user_id",
        "room_type_id",
        "hotel_id",
        "state_id",
        "reservation_id",
        "country_id",
        "city",
        "email",
        "mobile",
        "street",
        "post_code",
        "notes",
        "booking_date",
        "booking_expiry_date",
        "booking_status",
        "payment_status",
        "payment_gateway",
        "payment_track",
        "transaction_id",
        "order_details",
        "payment_meta",
        "amount",
        "payment_type"
    ];

    protected $table = "booking_informations";

    protected $translatable = ['street','notes','city'];
    public function user(){
        return $this->belongsTo(\App\Models\User::class);
    }

    public function hotel(){
        return $this->belongsTo(Hotel::class,"hotel_id","id");
    }

    public function statee(){
        return $this->belongsTo(State::class, "state_id","id");
    }
    public function country(){
        return $this->belongsTo(Country::class,"country_id","id");
    }

    public function room_type(){
        return $this->belongsTo(RoomType::class,"room_type_id","id");
    }

    public function booking_payment_log()
    {
        return $this->hasOne(HotelBookingPaymentLog::class,"booking_information_id","id");
    }
}
