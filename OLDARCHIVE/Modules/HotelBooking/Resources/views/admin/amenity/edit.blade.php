@extends(route_prefix().'admin.admin-master')

@section('title')
    {{__('Update Amenity')}}
@endsection

@section('style')
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
        $icon = $amenity->icon ?? "las la-exclamation-triangle";
    @endphp
    @php
        $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
    @endphp
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <x-admin.header-wrapper>
                    <x-slot name="left">
                        <h4 class="card-title mb-1">  {{__('Update Amenity')}}</h4>
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
                        <x-link-with-popover url="{{route('tenant.admin.amenities.index')}}" extraclass="ml-3">
                            {{__('All Amenities')}}
                        </x-link-with-popover>
                    </x-slot>
                </x-admin.header-wrapper>

                <x-error-msg/>
                <x-flash-msg/>

                <form action="{{ route('tenant.admin.amenities.update',$amenity->id) }}" method="POST">
                    @csrf
                    @method('put')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body p-5">
                                    <h5 class="mb-5">{{ __('Amenity Information') }}</h5>

                                    <div class="form-group">
                                        <label for="name">{{ __('Name') }}</label>
                                        <input type="text" value="{{ $amenity->getTranslation('name',$lang_slug) }}" name="name" id="name" class="form-control">
                                        <input type="hidden" value="{{ $lang_slug }}" name="lang" id="lang" class="form-control">
                                    </div>

                                    <x-iconpicker.input :label="'Select amenities icon'" :value="$icon" :name="'icon'" :id="'amenities_icon'"/>

                                    <div class="form-group">
                                        <input type="submit" id="hotel_add" class="btn btn-primary" value="Amenity Update">
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
    <x-iconpicker.js />
    <script>
        (function ($) {
            "use strict";

            $(document).ready(function ($) {

                $(document).on('change','select[name="lang"]',function (e){
                    $(this).closest('form').trigger('submit');
                    $('input[name="lang"]').val($(this).val());
                });
            });
        })(jQuery)
    </script>
@endsection
