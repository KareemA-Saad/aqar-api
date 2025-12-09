@extends(route_prefix().'admin.admin-master')

@section('title')
    {{__('Update Room Type')}}
@endsection

@section('style')
    <x-media-upload.css />
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
                        <h4 class="card-title mb-5">  {{__('Update Room Type')}}</h4>
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
                        <x-link-with-popover url="{{route('tenant.admin.room-types.index')}}" extraclass="ml-3">
                            {{__('All RoomType')}}
                        </x-link-with-popover>
                    </x-slot>
                </x-admin.header-wrapper>

                <x-error-msg/>
                <x-flash-msg/>

                <form action="{{route('tenant.admin.room-types.update',$room_type->id)}}" method="POST">
                    @csrf
                    @method('put')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body p-5">
                                    <h5 class="mb-5">{{ __('Room Type Information') }}</h5>
                                    <input name="id" value="{{ $room_type->id }}" type="hidden">
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="name">{{ __('Name') }}</label>
                                            <input type="hidden" value="{{ $lang_slug }}" name="lang" class="form-control">
                                            <input type="text" value="{{ @$room_type->getTranslation('name',$lang_slug)}}" name="name" id="name" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <input type="submit" id="bed_type_add" class="btn btn-primary" value="{{__('Save')}}">
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
