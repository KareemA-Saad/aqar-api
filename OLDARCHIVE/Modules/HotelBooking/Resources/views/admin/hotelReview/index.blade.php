@extends('tenant.admin.admin-master')
@section('title') {{__('All Hotel Reviews')}} @endsection

@section('style')
    <x-media-upload.css/>
    <x-datatable.css/>
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
                        <h4 class="card-title mb-5">{{__('All Hotel Reviews')}}</h4>
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
                    </x-slot>
                </x-admin.header-wrapper>
                <x-error-msg/>
                <x-flash-msg/>
                <x-datatable.table>
                    <x-slot name="th">
                        <th>{{__('ID')}}</th>
                        <th>{{__('Name')}}</th>
                        <th>{{__('Hotel')}}</th>
                        <th>{{__('Ratting')}}</th>
                        <th>{{__('Cleanliness')}}</th>
                        <th>{{__('Comfort')}}</th>
                        <th>{{__('Staff')}}</th>
                        <th>{{__('Facilities')}}</th>
                        <th>{{__('Description')}}</th>
                    </x-slot>
                    <x-slot name="tr">
                        @foreach($all_hotel_reviews as $data)
                            <tr>
                                <td>{{$data->id}}</td>
                                <td>
                                    {{\App\Models\User::find($data->user_id)->name}}
                                </td>
                                <td> Hotel</td>
                                <td>
                                    {{$data->ratting}}
                                </td>
                                <td>
                                    {{$data->cleanliness}}
                                </td>
                                <td>
                                    {{$data->comfort}}
                                </td>
                                <td>
                                    {{$data->staff}}
                                </td>
                                <td>
                                    {{$data->facilities}}
                                </td>
                                <td>
                                    {{$data->description}}
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
    <script>
        $(document).ready(function($){
            "use strict";

            $(document).on('change','select[name="lang"]',function (e){
                $(this).closest('form').trigger('submit');
                $('input[name="lang"]').val($(this).val());
            });
        });
    </script>
    <x-datatable.js/>
    <x-table.btn.swal.js/>

@endsection
