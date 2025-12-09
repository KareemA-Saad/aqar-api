@extends('tenant.admin.admin-master')
@section('title') {{__('All Bookings')}} @endsection

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
                        <h4 class="card-title mb-5">{{__('All Bookings')}}</h4>
                    </x-slot>
                    <x-slot name="right">
                        <form action="" method="get">
                            <x-fields.select name="lang" title="{{__('Language')}}">
                                @foreach(\App\Facades\GlobalLanguage::all_languages() as $lang)
                                    <option value="{{$lang->slug}}" @if($lang->slug === $lang_slug) selected @endif>{{$lang->name}}</option>
                                @endforeach
                            </x-fields.select>
                        </form>
                        <p></p>
                        <a href="{{route('tenant.admin.hotel-bookings.create', ['lang' => $lang_slug])}}" class="btn btn-info btn-sm mb-3" >{{__('Add New Booking')}}</a>
                    </x-slot>
                </x-admin.header-wrapper>
                <x-error-msg/>
                <x-flash-msg/>
                <x-datatable.table>
                    <x-slot name="th">
                        <th>{{__('ID')}}</th>
                        <th>{{__('Customer Info')}}</th>
                        <th>{{__('Booking Email')}}</th>
                        <th>{{__('Hotel Info')}}</th>
                        <th>{{__('Address')}}</th>
                        <th>{{__('Booking Date')}}</th>
                        <th>{{__('Sub Total')}}</th>
                        <th>{{__('Tax(inc)')}}</th>
                        <th>{{__('Payed Amount')}}</th>
                        <th>{{__('Payment Status')}}</th>
                        <th>{{__('Action')}}</th>
                    </x-slot>
                    <x-slot name="tr">
                        @foreach($bookingInformation as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>
                                    <p><b>{{__('Name')}}:</b> {{ optional(auth("admin")->user())->name }}</p>
                                    <p><b>{{__('Email')}}:</b> {{ optional(auth("admin")->user())->email }}</p>
                                </td>
                                <td>
                                    {{ $item->email }}
                                </td>
                                <td>
                                    <p><b>{{__('Hotel Name')}}:</b> {{$item->hotel?$item->hotel->getTranslation('name',$lang_slug): ""}}</p>
                                    <p><b>{{__('Hotel Location')}}:</b> {{$item->hotel?$item->hotel->getTranslation('location',$lang_slug): ""}}</p>
                                    <p><b>{{__('Room Type')}}:</b> {{$item->room_type?$item->room_type->getTranslation('name',$lang_slug): ""}}</p>
                                </td>
                                <td>
                                    <p><b>{{__('City')}}:</b>  {{ @$item->getTranslation('city',$lang_slug)}}</p>
                                    <p><b>{{__('State')}}:</b> {{ @$item->statee->name}}</p>
                                    <p><b>{{__('Country')}}:</b>   {{ @$item->country->name}}</p>
                                </td>
                                <td>
                                    <p><b>{{__('Checkin Date')}}:</b> {{ Carbon\Carbon::parse($item->booking_date)->format("d F Y") }}</p>
                                    <p><b>{{__('Checkout Date')}}:</b> {{ Carbon\Carbon::parse($item->booking_expiry_date)->format("d F Y") }}</p>
                                </td>
                                <td>
                                    {{ amount_with_currency_symbol(@$item->booking_payment_log->subtotal) }}
                                </td>
                                <td>
                                    {{ amount_with_currency_symbol(@$item->booking_payment_log->tax_amount) }}
                                </td>
                                <td>
                                    {{ amount_with_currency_symbol(@$item->booking_payment_log->total_amount) }}
                                </td>
                                <td>
                                    <span class="badge {{ @$item->booking_payment_log->status == 'complete' ? 'bg-success' : 'bg-warning' }}">
                                        {{ $item->booking_payment_log?$item->booking_payment_log->status:'pending' }}
                                    </span>
                                </td>
                                <td>

                                    @if($item->payment_status == 0)
                                        <button class="btn btn-warning btn-sm">
                                            pending
                                        </button>
                                    @elseif($item->payment_status == 1)
                                        <button class="btn btn-success btn-sm">
                                            completed
                                        </button>
                                    @elseif($item->payment_status == 2)
                                        <button class="btn btn-info btn-sm">
                                            in-progress
                                        </button>
                                    @elseif($item->payment_status == 3)
                                        <button class="btn btn-danger btn-sm">
                                            cancled
                                        </button>
                                    @elseif($item->payment_status == 4)
                                        <button class="btn btn-danger btn-sm">
                                            cancel requested
                                        </button>
                                    @endif
                                    @if($item->payment_status != 1 || $item->payment_status == 3)
                                    <a href="#"
                                       data-bs-toggle="modal"
                                       data-item-id="{{ $item->id }}" data-payment-status="{{ $item->payment_status }}"
                                       data-bs-target="#UpdatePaymentStatus"
                                       class="btn update-status btn-sm btn-info">{{__('Update Status')}}
                                    </a>
                                     @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-slot>
                </x-datatable.table>

            </div>
        </div>
    </div>

    <div class="modal fade" id="UpdatePaymentStatus" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Update Status')}}</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('tenant.admin.hotel-bookings.payment-status-update') }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" id="booking_id" value="" required>
                            <label>Select Payment Status</label>
                            <select class="form-control form-control-sm" id="payment_status" name="payment_status">
                                <option data-payment-status="0" value="0">Pending</option>
                                <option data-payment-status="1" value="1">Complete</option>
                                <option data-payment-status="2" value="2">In-Progress</option>
                                <option data-payment-status="3" value="3">Failed</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-info btn-sm">Save changes</button>
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
    <script>
        $(document).ready(function($) {
            "use strict";
            // booking status update
            $(document).on("click", ".update-status", function () {
                let data = $(this);
                let id = data.data("item-id")
                let status = data.data("payment-status")

                $("#booking_id").val(id);

                $("#payment_status option").each(function () {
                    if (status == $(this).val()) {
                        $(this).attr("selected", true);
                    }
                });
            });
        });
    </script>

@endsection
