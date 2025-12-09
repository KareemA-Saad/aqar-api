@if(session()->has('msg'))
    <div class="alert alert-{{session('success') ? "success" : "danger"}}">
        {!! session('msg') !!}
    </div>
@endif
