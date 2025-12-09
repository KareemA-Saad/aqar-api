@extends('tenant.admin.admin-master')
@section('title') {{__('All Room Types')}} @endsection

@section('style')
    <x-media-upload.css/>
    <x-datatable.css/>
@endsection

@section('content')
    @php
        $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
         $colors = ["warning","danger","info","success","dark","secondary"];
    @endphp

    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <x-admin.header-wrapper>
                    <x-slot name="left">
                        <h4 class="card-title mb-5">{{__('All Room Types')}}</h4>
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
                        <a href="{{route('tenant.admin.room-types.create', ['lang' => $lang_slug])}}" class="btn btn-info btn-sm mb-3" >{{__('Add New Room Type')}}</a>
                    </x-slot>
                </x-admin.header-wrapper>
                <x-error-msg/>
                <x-flash-msg/>
                <x-datatable.table>
                    <x-slot name="th">
                        <th>{{__('SL NO:')}}</th>
                        <th>{{__('Name')}}</th>
                        <th>{{__('Action')}}</th>
                    </x-slot>
                    <x-slot name="tr">
                        @foreach($all_room_types as $room_type)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td> {{ $room_type?$room_type->getTranslation('name',$lang_slug): ""}}</td>
                                <td>
                                    <x-table.btn.swal.delete :route="route('tenant.admin.room-types.delete', $room_type->id)" />
                                    <x-table.btn.edit_with_lang :route="route('tenant.admin.room-types.edit',$room_type->id)" :lang="$lang_slug" />
                                </td>
                            </tr>
                        @endforeach
                    </x-slot>
                </x-datatable.table>

            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <x-datatable.js/>
    <x-table.btn.swal.js/>
    <script>
        $(document).ready(function($){
            "use strict";
            // language change
            $(document).on('change','select[name="lang"]',function (e){
                $(this).closest('form').trigger('submit');
                $('input[name="lang"]').val($(this).val());
            });
        });
    </script>
@endsection
