@extends('tenant.admin.admin-master')
@section('title') {{__('All Bed Types')}} @endsection

@section('style')
    <x-media-upload.css/>
    <x-datatable.css/>
    <x-summernote.css />
@endsection

@section('content')
    @php
        $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
    @endphp
    <div class="col-lg-12 col-ml-12 padding-bottom-30">
        <div class="row">
            <div class="col-lg-12">
                <div class="margin-top-40"></div>
                <x-error-msg/>
                <x-flash-msg/>
            </div>
            <div class="col-lg-7 mt-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title">{{__('All Newsletter Subscriber')}}</h4>
                            <div class="bulk-delete-wrapper">
                                <div class="select-box-wrap">
                                    <select name="bulk_option" id="bulk_option">
                                        <option value="">{{{__('Bulk Action')}}}</option>
                                        <option value="delete">{{{__('Delete')}}}</option>
                                    </select>
                                    <button class="btn btn-primary btn-sm" id="bulk_delete_btn">{{__('Apply')}}</button>
                                </div>
                            </div>


                        <div class="table-wrap">
                            <table class="table table-default">
                                <thead>
                                <th class="no-sort">
                                    <div class="mark-all-checkbox">
                                        <input type="checkbox" class="all-checkbox">
                                    </div>
                                </th>
                                <th>{{__('ID')}}</th>
                                <th>{{__('Email')}}</th>
                                <th>{{__('Action')}}</th>
                                </thead>
                                <tbody>
                                @foreach($all_subscriber as $data)
                                    <tr>
                                        <td>
                                            <div class="bulk-checkbox-wrapper">
                                                <input type="checkbox" class="bulk-checkbox" name="bulk_delete[]" value="{{$data->id}}">
                                            </div>
                                        </td>
                                        <td>{{$data->id}}</td>
                                        <td>{{$data->email}} @if($data->verified > 0) <i class="fas fa-check-circle"></i>@endif</td>
                                        <td>
                                                <x-delete-popover :url="route('tenant.admin.newsletters.delete',$data->id)"/>

                                                    <a href="#"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#category_create_modal"
                                                       class="btn btn-sm btn-info btn-xs mb-3 mr-1 text-light">{{__('Email')}}</a>
                                                @if($data->verified <1)
                                                    <form action="{{route('tenant.admin.newsletters.verify.mail.send')}}" method="post" enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{$data->id}}">
                                                        <button class="btn btn-secondary" type="submit">{{__('Send Verify Mail')}}</button>
                                                    </form>
                                                @endif

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mt-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title">{{__('Add New Subscriber')}}</h4>
                        <form action="{{route('tenant.admin.newsletters.new.sub')}}" method="post">
                            @csrf
                            <div class="form-group">
                                <label for="email">{{__('Email')}}</label>
                                <input type="text" class="form-control"  id="email" name="email" placeholder="{{__('Email')}}">
                            </div>
                            <button id="submit" type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="category_create_modal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Create Category')}}</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('tenant.admin.newsletters.single.mail')}}" id="send_mail_to_subscriber_edit_modal_form"  method="post">
                        <div class="modal-body">
                            @csrf
                            <div class="form-group">
                                <label for="edit_icon">{{__('Email')}}</label>
                                <input type="text" class="form-control"  id="subject" name="email" placeholder="{{__('Email')}}">
                            </div>
                            <div class="form-group">
                                <label for="edit_icon">{{__('Subject')}}</label>
                                <input type="text" class="form-control"  id="subject" name="subject" placeholder="{{__('Subject')}}">
                            </div>
                            <div class="form-group">
                                <label for="message">{{ __('Message') }}</label>
                                <textarea type="text" name="message" class="form-control summernote" id="message" cols="10" rows="5">{!! old('message') !!}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button id="submit" type="submit" class="btn btn-primary">{{__('Send Mail')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <x-datatable.js/>
    <x-table.btn.swal.js/>
    <x-summernote.js />

    <script>
        <x-bulk-action-js :url="route('admin.newsletter.bulk.action')" />
        $(document).ready(function($){
            "use strict";

                $(document).on('change','select[name="lang"]',function (e){
                    $(this).closest('form').trigger('submit');
                    $('input[name="lang"]').val($(this).val());
                });

            $(document).on('click', '.testimonial_edit_btn', function () {
                var el = $(this);
                var id = el.data('id');
                var title = el.data('title');
                var action = el.data('action');

                var form = $('#testimonial_edit_modal_form');
                form.attr('action', action);
                form.find('.donation_category_id').val(id);
                form.find('.edit_title').val(title);
                form.find('.edit_status option[value="' + el.data('status') + '"]').attr('selected', true);
            });

        });
    </script>
@endsection
