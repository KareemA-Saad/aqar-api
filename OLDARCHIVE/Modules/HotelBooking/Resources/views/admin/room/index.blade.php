@extends('tenant.admin.admin-master')
@section('title') {{__('All Room')}} @endsection

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
                    <x-error-msg/>
                    <x-flash-msg/>
                    <x-slot name="left">
                        <h4 class="card-title mb-5">{{__('All Room')}}</h4>
                        {{--                        <x-bulk-action permissions="portfolio-category-delete"/>--}}
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
                        <a href="{{route('tenant.admin.rooms.create', ['lang' => $lang_slug])}}" class="btn btn-info btn-sm mb-3" >{{__('Add New room')}}</a>
                    </x-slot>
                </x-admin.header-wrapper>
                <x-error-msg/>
                <x-flash-msg/>
                <x-datatable.table>
                    <x-slot name="th">
                        <th>{{__('ID')}}</th>
                        <th>{{__('Name')}}</th>
                        <th>{{__('Base Cost')}}</th>
                        <th>{{__('Description')}}</th>
                        <th>{{__('Status')}}</th>
                        <th>{{__('Action')}}</th>
                    </x-slot>
                    <x-slot name="tr">
                        @foreach($all_rooms as $data)
                            <tr>
                                <td>{{$data->id}}</td>
                                <td>
                                    {{ $data->getTranslation('name',$lang_slug)}}
                                </td>
                                <td>
                                    {{$data->base_cost}}
                                </td>
                                <td>
                                    {{ \Illuminate\Support\Str::limit(\App\Helpers\SanitizeInput::esc_html($data->getTranslation('description',$lang_slug)), 50, $end='.......') }}
                                </td>
                                <td> {{ \App\Enums\StatusEnums::getText($data->status) }}</td>
                                <td>
                                    <x-table.btn.swal.delete :route="route('tenant.admin.rooms.delete', $data->id)" />
                                    <x-table.btn.edit_with_lang :route="route('tenant.admin.rooms.edit',$data->id)" :lang="$lang_slug" />
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

                $(document).on('change','select[name="lang"]',function (e){
                    $(this).closest('form').trigger('submit');
                    $('input[name="lang"]').val($(this).val());
                });
        });
    </script>
@endsection
