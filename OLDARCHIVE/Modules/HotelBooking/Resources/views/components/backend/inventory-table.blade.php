
<table class="table table-default inventoryTable">
    <thead>
    <th></th>
    @foreach($calldays as $day)
        <th class="text-center">
            <spna>{{ $day["month"] }}</spna><br>
            <spna>{{ $day["day"] }}</spna><br>
            <spna>{{ $day["day_name"] }}</spna>
        </th>
    @endforeach
    <th>{{__('Action')}}</th>
    </thead>
    <tfoot>
    <tr>
        <td colspan="8">

        </td>
    </tr>
    </tfoot>
    <tbody>
    @foreach($callroomtypes as $room_type)

        <tr data-room-type-name="{{$room_type->getTranslation('name',$callLang) }}"
            data-room-type-address="{{ $room_type->id }}"
            id="date-{{ $loop->iteration }}">
            <td>
                {{ $room_type->name }}<br />
{{--                <a href="#" data-bs-toggle="modal" data-bs-target="#modal_one" data-room-type-name="{{ $room_type->name }}" data-room-type-address="{{ $room_type->id }}"  class="badge badge-warning bulk-update">{{__('Bulk update')}}</a>--}}
{{--                <span data-room-type-name="{{ $room_type->name }}" data-room-type-address="{{ $room_type->id }}" class="badge badge-info bulk-price-update" data-bs-toggle="modal" data-bs-target="#exampleModalCenter_inventory_price">Bulk Rates</span>--}}
            </td>

            @foreach($calldays as $day)
                @php
                    $inventory = $callinventories->where("room_type_id",$room_type->id)->where("date",$day["date"] ." 00:00:00")->first();
                    $count_room = \Modules\HotelBooking\Entities\Room::with("room_inventory")->where("room_type_id",$room_type->id)->count();
                    // count booked room inventory
                    $count_booked_room = \Modules\HotelBooking\Entities\RoomInventory::whereDate("booked_date",$day["date"])->where("room_type_id", $room_type->id)->count();
                    $output_count = $count_room - $count_booked_room;
                @endphp
                <td>
                    <input readonly disabled type="number" class="date2"
                           value="{{ $output_count ?? 0 }}" min="1"
                           max="{{ optional($inventory)->rooms_count }}"
                           data-date="{{ $day["date"] }}">
                    <span>{{ $count_booked_room ?? 0 }}</span>
                </td>
            @endforeach
            <td>
                @if($inventory== null)
                    <button class="btn btn-warning btn-sm">
                        unavailable
                    </button>
                @else($item->payment_status == 1)
                    <button class="btn btn-success btn-sm">
                        available
                    </button>
                @endif
                <button class="btn btn-sm btn-info update-inventory">Update</button>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
