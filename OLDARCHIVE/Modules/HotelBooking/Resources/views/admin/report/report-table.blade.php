{{--<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/jq-3.6.0/dt-1.12.1/b-2.2.3/datatables.min.css"/>--}}
@php
    $colors = ["warning","danger","info","success","dark","secondary"];
    $x = 1;
@endphp
<div class="data-tables datatable-primary content-table-wrapper dataTable">
    <table id="all_user_table" class="text-center table">
        <thead class="text-capitalize">
        <th>{{__('SL NO:')}}</th>
        <th>{{__('Customer Name')}}</th>
        <th>{{__('Customer Email')}}</th>
        <th>{{__('Customer Address')}}</th>
        <th>{{__('Booking Email')}}</th>
        <th>{{__('Hotel Name')}}</th>
        <th>{{__('Hotel Location')}}</th>
        <th>{{__('Room Type')}}</th>
        <th>{{__('Checkin Date')}}</th>
        <th>{{__('Checkout Date')}}</th>
        <th>{{__('Amount')}}</th>
        </thead>
        <tbody class="data-table-body">

        @foreach($bookingInformation as $key => $bookingInfo)
            @foreach($bookingInfo as $item)
                <tr>
                    <td>{{ $x++ }}</td>
                    <td>{{ optional($item->user)->name }}</td>
                    <td>{{ optional($item->user)->email }}</td>
                    <td>{{ optional($item->user)->address }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ optional($item->hotel)->name }}</td>
                    <td>{{ optional($item->hotel)->location }}</td>
                    <td>{{ optional($item->room_type)->name }}</td>
                    <td>{{ Carbon\Carbon::parse($item->booking_date)->format("d F Y") }}</td>
                    <td>{{ Carbon\Carbon::parse($item->booking_expity_date)->format("d F Y") }}</td>
                    <td>{{ amount_with_currency_symbol($item->amount) }}</td>
                </tr>

            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
