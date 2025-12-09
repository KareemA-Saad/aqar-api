@extends(route_prefix().'admin.admin-master')

@section('title')
    {{__('Add New Hotel')}}
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
                        <h4 class="card-title mb-5">  {{__('New Hotel')}}</h4>
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
                        <x-link-with-popover url="{{route('tenant.admin.hotels.index')}}" extraclass="ml-3">
                            {{__('All Hotel')}}
                        </x-link-with-popover>
                    </x-slot>
                </x-admin.header-wrapper>

                <x-error-msg/>
                <x-flash-msg/>
                <form action="{{route('tenant.admin.hotels.store')}}" method="POST">
                    @csrf
                    <div class="row mt-3">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body p-5">
                                    <h5 class="mb-5">{{ __('Hotel Information') }}</h5>
                                    <div class="form-group">
                                        <label for="name">{{ __('Name') }}</label>
                                        <input type="text" value="{{ old("name") }}" name="name" id="name" class="form-control" placeholder="Enter hotel name">
                                        <input type="hidden" value="{{ $lang_slug  }}" name="lang" id="lang" class="form-control">
                                    </div>
                                    <x-fields.select name="country_id" title="{{__('Country')}}">
                                        <option value="">Select country</option>
                                        @foreach ($all_countries as $item)
                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                        @endforeach
                                    </x-fields.select>

                                    <x-fields.select name="state_id" title="{{__('State')}}">
                                        @foreach ($all_states as $item)
                                            <option value="{{$item->id}}">{{$item->name}}</option>
                                        @endforeach
                                    </x-fields.select>
                                    <div class="form-group">
                                        <label for="location">{{ __('Location') }}</label>
                                        <input type="text" name="location" value="{{ old("location") }}" id="location" class="form-control" placeholder="Enter location">
                                    </div>
                                    <div class="form-group">
                                        <label for="description">{{ __('About') }}</label>
                                        <textarea type="text" name="about" class="form-control summernote" id="description" cols="30" rows="10">{!! old('about') !!}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="distance">{{ __('Distance') }}</label>
                                        <input type="text" name="distance" value="{{ old("distance") }}" id="distance" class="form-control" placeholder="Enter distance">
                                    </div>
                                    <div class="form-group">
                                        <label for="restaurant_inside">{{ __('Restaurant Inside') }}</label>
                                        <input type="text" value="{{ old("restaurant_inside") }}" name="restaurant_inside" id="restaurant_inside" class="form-control" placeholder="Enter restaurant">
                                    </div>
                                    <x-fields.select name="status" title="{{__('Status')}}">
                                        <option value="{{\App\Enums\StatusEnums::PUBLISH}}">{{__('Publish')}}</option>
                                        <option value="{{\App\Enums\StatusEnums::DRAFT}}">{{__('Draft')}}</option>
                                    </x-fields.select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body p-5">
                                    <h5 class="mb-5">{{ __('Hotel Amenities') }}</h5>
                                    <div class="form-group">
                                        <select name="hotel_amenities[]" multiple id="amenity" class="form-control nice-select wide">
                                            <option value="">Select Amenity</option>

                                            @foreach($all_amenities as $amenity)
                                                <option value="{{ $amenity->id }}" {{ in_array($amenity->id,old("hotel_amenities") ?? []) ? "selected" : ""}}>{{ $amenity->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" id="hotel_add" class="btn btn-primary" value="Hotel Add">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <x-media-upload.js />
    <x-niceselect.js />
    <x-summernote.js />

    <script>
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

        // getting all state related country
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
                            $('select[name="state_id"]').empty();
                            $('#state_id').html('<option value="">Select State</option>');
                            $.each(data, function(key, value){
                                console.log(value)
                                $('select[name="state_id"]').append('<option value ="'+ value.id + '">' + value.name + '</option>');

                            });
                        },
                    });
                }else{
                    alert('danger');
                }
            });
        });
    </script>
@endsection
