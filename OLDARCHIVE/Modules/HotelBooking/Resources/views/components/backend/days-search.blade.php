
<div style="gap: 10px" class="wrapper d-flex align-items-center">
    @foreach($days as $day)
        <div class="">
            <input id="date-{{ $day["day"] }}" name="date[]" value="{{ $day["date"] }}" type="checkbox">
            <label for="date-{{ $day["day"] }}">{{ $day["day_name"] }}</label>
        </div>
    @endforeach
</div>