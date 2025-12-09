@extends(route_prefix().'admin.admin-master')

@section('title')
{{__('Add New Room Type')}}
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
                    <h4 class="card-title mb-5"> {{__('New Room Type')}}</h4>
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

            <x-error-msg />
            <x-flash-msg />

            <form action="{{route('tenant.admin.room-types.store')}}" method="POST">
                @csrf
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body p-5">
                                <h5 class="mb-5">{{ __('Room Type Information') }}</h5>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="name">{{ __('Name') }}</label>
                                        <input type="hidden" value="{{ $lang_slug }}" name="lang" id="name" class="form-control">
                                        <input type="text" value="{{ old('name') }}" name="name" id="name" class="form-control" placeholder="Enter room-type name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" id="bed_type_add" class="btn btn-primary" value="Hotel Room Type Add">
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
    (function($) {
        "use strict";

        $(document).ready(function($) {

            $(document).on('change', 'select[name="lang"]', function(e) {
                $(this).closest('form').trigger('submit');
                $('input[name="lang"]').val($(this).val());
            });

        });
    })(jQuery)
</script>
@endsection