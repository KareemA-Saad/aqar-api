<table class="table table-sm table-responsive w-100 inventory-today-price-table" style="border-collapse: collapse;width: 100%" border="1px">
    <thead>
        <tr>
            <th>Date</th>
            <th>Rate</th>
        </tr>
    </thead>
    <tbody>
        @foreach($all_info as $item)
            <tr>
                <td>{{ $item["date"] }}</td>
                <td>{{ amount_with_currency_symbol($item["today_charge"]) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="1" class="text-right">Sub Total</td>
            <td>{{ amount_with_currency_symbol(array_sum(array_column($all_info,"today_charge"))) }}</td>
        </tr>
        <tr>
            <td colspan="1" class="text-right">Tax(inc)</td>
            <td>{{ amount_with_currency_symbol($total_tax_percentance) }} %</td>
        </tr>
        <tr>
            <td colspan="1" class="text-right">Total Amount</td>
            <td>{{ amount_with_currency_symbol(($total_amount_tax_inc)) }}</td>
        </tr>
    </tfoot>
</table>

