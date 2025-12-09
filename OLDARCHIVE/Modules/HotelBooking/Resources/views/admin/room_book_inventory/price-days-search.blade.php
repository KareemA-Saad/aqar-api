<div class="col-md-12">
    <form id="bulk_price_inventory_update" method="post" action="{{ route('tenant.admin.room-book-inventories.bulk.price.update') }}">
        <div id="price-inventory-errors"></div>
        <input name="room_type_id" value="{{ request()->room_type_id }}" type="hidden" />

        <x-hotelbooking::backend.days-search :days='$all_days'/>

        <table id="table" class="table table-responsive table-bordered">
            <thead class="w-100">
            <tr>
                <th></th>
                <th>Sell Rate</th>
                <th>Net Rate</th>
            </tr>
            </thead>
            <tbody class="w-100">
            <tr class='disabled'>
                <td>Base charge</td>
                <td><input name="base_charge" type="number"> </td>
                <td><input type="text" disabled value="0"></td>
            </tr>
            </tbody>
        </table>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary close-box" data-dismiss="modal">Close</button>
            <input type="submit" class="btn btn-sm btn-primary" value="Save changes">
        </div>
    </form>
</div>
