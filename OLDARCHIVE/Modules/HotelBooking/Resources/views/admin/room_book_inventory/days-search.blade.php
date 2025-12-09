<div class="col-md-12">
    <form id="bulk_inventory_update" method="post" action="{{ route('tenant.admin.room-book-inventories.bulk.update') }}">
        <div id="inventory-errors"></div>
        <input name="room_type_id" value="{{ request()->room_type_id }}" type="hidden" />

        <x-hotelbooking::backend.days-search :days='$all_days'/>

        <table id="table" class="table table-responsive table-bordered">
            <thead class="w-100">
                <tr>
                    <th>Update</th>
                    <th>Description</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody class="w-100">
                <tr class='disabled'>
                    <td><input type="checkbox" value="" disabled></td>
                    <td>Available Room Count</td>
                    <td><input type="text" disabled value="{{ $count_available_room }}"></td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="" disabled></td>
                    <td>Min Night</td>
                    <td><input type="text" disabled value="1"></td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="refundable" value="on"></td>
                    <td>Refundable</td>
                    <td>
                        <select name="refundable_select" id="refundable" class="form-control">
                            <option value="refundable">Refundable</option>
                            <option value="non_refundable">Non-refundable</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="bookable" value="on"></td>
                    <td>Bookable</td>
                    <td>
                        <select name="bookable_select" class="form-control">
                            <option>Bookable</option>
                            <option>Non-bookable</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary close-box" data-dismiss="modal">Close</button>
            <input type="submit" class="btn btn-sm btn-primary" value="Save changes">
        </div>
    </form>
</div>
