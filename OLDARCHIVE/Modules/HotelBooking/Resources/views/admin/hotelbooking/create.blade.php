@extends(route_prefix().'admin.admin-master')

@section('title')
    {{__('Add New Bookings')}}
@endsection

@section('style')
    <x-niceselect.css />
    <x-summernote.css />
    <style>
        .nav-pills .nav-link {
            margin: 8px 0px !important;
        }
        .col-lg-4.right-side-card {
            background: aliceblue;
        }
    </style>
@endsection

@section('content')
    @php
        $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
    @endphp
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <x-admin.header-wrapper>
                    <x-slot name="left">
                        <h4 class="card-title mb-5">  {{__('New Bookings')}}</h4>
                    </x-slot>
                    <x-slot name="right" class="d-flex">
                        <form action="" method="get">
                            <x-fields.select name="lang" title="{{__('Language')}}">
                                @foreach(\App\Facades\GlobalLanguage::all_languages() as $lang)
                                    <option value="{{$lang->slug}}" @if($lang->slug === $lang_slug) selected @endif>{{$lang->name}}</option>
                                @endforeach
                            </x-fields.select>
                        </form>
                        <p></p>
                        <x-link-with-popover url="{{route('tenant.admin.hotel-bookings.index')}}" extraclass="ml-3">
                            {{__('All Bookings')}}
                        </x-link-with-popover>
                    </x-slot>
                </x-admin.header-wrapper>

                <x-error-msg/>
                <x-flash-msg/>

                <form action="{{ route('tenant.admin.hotel-bookings.store') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Hotel')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item ">
                                    <select name="hotel_id" id="hotel" class="form-control nice-select w-100 billing_address_country">
                                        <option value="">Select Hotel</option>
                                        @foreach($hotels as $hotel)
                                            <option value="{{ $hotel->id }}" {{ $hotel->id == old("hotel") ?? 0 ? "selected" : "" }}>{{ $hotel->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Room Type')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item ">
                                    <select name="room_type_id" id="room_type_id" class="form-control w-100">
                                        <option value="">{{__('Select Room Type')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Room')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item ">
                                    <select name="room_id" id="room_id" class="form-control w-100">
                                        <option value="">{{__('Select Room')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Country')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item ">
                                    <select name="country_id" id="country" class="form-control nice-select w-100">
                                        <option value="">{{__('Select Hotel')}}</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ $country->id == old("country") ?? 0 ? "selected" : "" }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('State')}}</label>
                            <div class="col-md-12">
                                <select name="state_id" id="state_id" class="form-control w-100 billing_address_state">
                                    <option value="">{{__('Select State')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('City/Town')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="city" value="" type="text" class="form-control" placeholder="{{__('Enter City name')}}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Name')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="name" value="{{old("country")}}" type="text" class="form-control" placeholder="{{__('Enter  name')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Email')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input type="hidden" value="{{ $lang_slug }}" name="lang" id="name" class="form-control">
                                    <input name="email" value="" type="email" class="form-control" placeholder="{{__('Enter email address')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Mobile')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="mobile" value="" type="number" class="form-control" placeholder="{{__('Enter mobile number')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Postal Code')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="post_code" value="" type="number" class="form-control" placeholder="{{__('Enter state')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Checkin Date')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="booking_date" value="" type="text" class="form-control" placeholder="Enter state...">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Checkout Date')}}</label>
                            <div class="col-md-12">
                                <div class="listsearch-input-item">
                                    <input name="booking_expiry_date" value="" type="text" class="form-control" placeholder="{{__('Enter state')}}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-12">
                            <label class="col-md-12 font-weight-bold" for="country">{{__('Additional Notes')}}</label>
                            <div class="form-group">
                                <label for="description">{{ __('Note') }}</label>
                                <textarea type="text" name="notes" class="form-control summernote" id="note" cols="30" rows="10">{!! old('note') !!}</textarea>
                            </div>
                        </div>

                        <div class="col-md-12 booking-information">
                            <div  id="inventory-price-table">

                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="col-md-12">
                                <button class="btn btn-info">{{__('Create Booking')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <x-niceselect.js />
    <x-summernote.js />

    <script>

        flatpickr('input[name=booking_date]', {
            "min": new Date().fp_incr(1),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=booking_expiry_date]', {
            "min": new Date().fp_incr(2),
            "max": new Date().fp_incr(7)
        });


        // language change
        (function ($) {
            "use strict";
            $(document).ready(function ($) {
                $(document).on('change','select[name="lang"]',function (e){
                    $(this).closest('form').trigger('submit');
                    $('input[name="lang"]').val($(this).val());
                });

            });
        })(jQuery)

        // getting room_types base on hotel select
        $(document).ready(function(){
            $('select[name="hotel_id"]').on('change',function(){
                var hotel_id = $(this).val();
                if(hotel_id){
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        type:"GET",
                        dataType:"json",
                        url:"/admin-home/hotel-bookings/room_type/"+hotel_id,
                        success:function(data){
                            $('select[name="room_type_id"]').empty();
                            $('#room_type_id').html('<option value="">Select Room Type</option>');
                            $.each(data, function(key, value){
                                $('select[name="room_type_id"]').append('<option value ="'+ value.id + '">' + value.name.en_US + '</option>');
                            });
                        },
                    });
                }else{
                    alert('danger');
                }
            });
        });

        // getting all room base on room_type
        $(document).ready(function()
        {
            $('select[name="room_type_id"]').on('change',function(){
                var room_type_id = $(this).val();
                if(room_type_id){
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        type:"GET",
                        dataType:"json",
                        url:"/admin-home/hotel-bookings/rooms/"+room_type_id,
                        success:function(data){
                            $('select[name="room_id"]').empty();
                            $('#room_id').html('<option value="">Select Room</option>');
                            $.each(data, function(key, value){
                                $('select[name="room_id"]').append('<option value ="'+ value.id + '">' + value.name.en_US + '</option>');
                            });
                        },
                    });
                }else{
                    alert('danger');
                }
            });
        });

       // calculate booking ammount after selected room and dates
        $(document).on("change", 'input[name="booking_date"],input[name="booking_expiry_date"]', function ()
        {
            let from_date = $('input[name="booking_date"]').val();
            let to_date = $('input[name="booking_expiry_date"]').val();
            let room_type_id = $("#room_type_id").val();
            let symbol = "{{ site_currency_symbol() }}";
            let country_id = $('.billing_address_country :selected').val();
            let state_id = $('.billing_address_state :selected').val();

            if(room_type_id == ''){
                $("#errors").html('<div class="alert alert-danger"><p>Select hotel and room type please</p></div>');
                return ;
            }
            let diff = showDiff(new Date(from_date), new Date(to_date));
            let formData = new FormData();
            formData.append("from_date",from_date);
            formData.append("to_date",to_date);
            formData.append("room_type_id",room_type_id);
            formData.append("country_id",country_id);
            formData.append("state_id",state_id);


            $.ajax({
                url: "{{route('tenant.admin.hotel-bookings.calculate_amount')}}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{csrf_token()}}",
                },
                beforeSend:function (){

                },
                processData: false,
                contentType: false,
                data:formData,
                success: function (data) {
                    $("#errors").html('');
                    if(data.success){
                        data.amount
                        $("#total-cost").html(symbol + data.amount)
                        $("#inventory-price-table").html(data.view)
                    }
                },
                error: function (data) {

                }
            });
            // end

            function showDiff(date1, date2) {
                let type = null;

                type = date1 > date2 ? 'negative' : 'positive';

                let diff = (date2 - date1) / 1000;
                diff = Math.abs(Math.floor(diff));

                let days = Math.floor(diff / (24 * 60 * 60));
                let leftSec = diff - days * 24 * 60 * 60;

                let hrs = Math.floor(leftSec / (60 * 60));
                leftSec = leftSec - hrs * 60 * 60;

                let min = Math.floor(leftSec / (60));
                leftSec = leftSec - min * 60;

                return {
                    d: days,
                    h: hrs,
                    i: min,
                    s: leftSec,
                    type: type
                };
            }
        })
    </script>

{{--    get all city base on country select --}}
    <script>
        $(document).ready(function(){
            $('select[name="country_id"]').on('change',function(){
                var country_id = $(this).val();
                if(country_id){
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        type:"GET",
                        dataType:"json",
                        url:"/admin-home/hotels/country-state/"+country_id,
                        success:function(data){
                            if(!data.length > 0){
                                CustomSweetAlertTwo.error('{{ __("This country have no state") }}')
                            }
                            $('select[name="state_id"]').empty();
                            $('#state_id').html('<option value="">Select State</option>');
                            $.each(data, function(key, value){
                                $('select[name="state_id"]').append('<option value ="'+ value.id + '">' + value.name + '</option>');
                            });
                        },
                    });
                }else{
                    CustomSweetAlertTwo.error('{{ __("Country not found") }}')
                }
            });
        });
    </script>
@endsection
