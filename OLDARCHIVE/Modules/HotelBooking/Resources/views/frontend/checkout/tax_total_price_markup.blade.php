@php
    $taxed_price = ($total_amount * $total_tax) / 100;
    $total = $total_amount + $taxed_price;
@endphp
<ul class="checkout-flex-list list-style-none checkout-border-top pt-3 mt-3">
    <li class="list"> <span class="regular">  {{__('BasePrice')}}</span> <span class="strong"> {{amount_with_currency_symbol($subtotal)}}</span> </li>
    <li class="list"> <span class="regular">  {{__('Sub Total')}}</span> <span class="strong"> {{ amount_with_currency_symbol($total_amount) }} </span> </li>

    <li class="list"> <span class="regular">  {{__('Tax(inc)')}}</span> <span class="strong" id="Tax">{{$total_tax ?? 0}} % </span> </li>
</ul>
<ul class="checkout-flex-list list-style-none checkout-border-top pt-3 mt-3">
    <li class="list"> <span class="regular">  {{__('Total')}}</span> <span class="strong color-one fs-20">{{amount_with_currency_symbol($total)}} </span> </li>
</ul>
