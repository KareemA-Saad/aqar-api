@extends('tenant.admin.admin-master')
@section('title') {{__('All Room Book Inventory')}} @endsection

@section('style')
    <x-media-upload.css/>
    <x-datatable.css/>
@endsection

@section('content')
@php
  $lang_slug = request()->get('lang') ?? \App\Facades\GlobalLanguage::default_slug();
@endphp
    <div class="col-lg-12 col-ml-12 padding-bottom-30">
        <div class="row">
            <div class="col-lg-12">
                <div class="margin-top-40">
                    <x-error-msg/>
                    <x-flash-msg/>
                </div>
            </div>
            <div class="col-lg-12 mt-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title">{{__('All Room Inventories')}}</h4>
                        <form id="search_room_inventory">
                            <div class="text-left d-flex gap-2">
                                <div class="form-group">
                                    <label>From date: </label>
                                    <input class="form-control" name="from_search_inventory_date" placeholder="From date" />
                                </div>

                                <div class="form-group">
                                    <label>To date: </label>
                                    <input class="form-control" name="to_search_inventory_date" placeholder="To date" />
                                </div>

                                <div class="form-group d-flex align-items-end">
                                    <input class="btn btn-info" type="submit" value="Select" />
                                </div>
                            </div>
                        </form>

                        <div class="table-wrap table-responsive inventory-table-wrapper">
                            <x-hotelbooking::backend.inventory-table :calldays='$all_days' :callinventories="$all_inventories" :callLang="$lang_slug" :callroomtypes="$all_room_types" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modal_one" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered custom-model-size-60">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Bulk update</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <form id="search_inventory">
                        @csrf
                        <div class="d-flex gap-2">
                            <div class="form-group">
                                <label>From date: </label>
                                <input class="form-control" name="inventory_search_from_date" placeholder="From date" />
                            </div>
                            <div class="form-group">
                                <label>To date: </label>
                                <input class="form-control" name="inventory_search_to_date" placeholder="To date" />
                            </div>
                            <input value="" type="hidden" name="room_type" id="inventory_room_type_address">
                            <div class="form-group d-flex align-items-end">
                                <input class="btn btn-info" type="submit" value="Select" />
                            </div>
                        </div>
                    </form>
                    <div class="inventory_content_wrapper"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    <div class="modal fade" id="exampleModalCenter_inventory_price" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered custom-model-size-60">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Bulk Rates Update')}}</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>×</span></button>
                </div>
                <div class="modal-body">
                    <form id="search_price_inventory_price">
                        @csrf
                        <div class="d-flex gap-2">
                            <div class="form-group">
                                <label>From date: </label>
                                <input class="form-control" name="inventory_price_search_from_date" placeholder="From date" />
                            </div>
                            <div class="form-group">
                                <label>To date: </label>
                                <input class="form-control" name="inventory_price_search_to_date" placeholder="To date" />
                            </div>
                            <input value="" type="hidden" name="inventory_price_room_type" id="inventory_price_room_type_address">
                            <div class="form-group d-flex align-items-end">
                                <input class="btn btn-info" type="submit" value="Select" />
                            </div>
                        </div>
                    </form>

                    <div class="inventory_price_content_wrapper"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <x-datatable.js/>
    <x-table.btn.swal.js/>
    <script>
        $(document).on("click",".bulk-update",function (){
            let attr = $(this).attr("data-room-type-address")

            $("#inventory_room_type_address").val(attr);
        });

        $(document).on("click",".bulk-price-update",function (){
            let attr = $(this).attr("data-room-type-address")

            $("#inventory_price_room_type_address").val(attr);
            $('input[name=inventory_price_room_type]').val(attr);
        });

        $(document).on("change","select[name=bookable_select]",function (){
            $("input[name=bookable]").val($(this).val());
        });

        $(document).on("submit","#bulk_inventory_update",function (e){
            e.preventDefault();
            let form_element,form,form_id;
            form_element = $("#inventory-errors");
            form_id = document.getElementById("bulk_inventory_update");
            form = new FormData(form_id);

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.bulk.update")}}',
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{csrf_token()}}",
                },
                beforeSend:function (){
                    // form.find('.ajax-loading-wrap').addClass('show').removeClass('hide');
                },
                processData: false,
                contentType: false,
                data:form,
                success: function (data) {
                    // click close box button for remove model data
                    $(".close-box").click();

                    if(data.success == 'true'){
                        toastr.success(data.msg)
                    }else{
                        toastr.error(data.msg);
                    }
                },
                error: function (data) {
                    var errors = data.responseJSON.errors;
                    var markup = '<ul class="alert alert-danger">';
                    $.each(errors,function (index,value){
                        markup += '<li>'+value+'</li>';
                    })
                    markup += '</ul>';
                    form_element.html(markup);
                }
            });
        });

        $(document).on("submit","#bulk_price_inventory_update",function (e){
            e.preventDefault();
            let form_element,form,form_id;
            form_element = $("#price-inventory-errors");
            form_id = document.getElementById("bulk_price_inventory_update");
            form = new FormData(form_id);

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.bulk.price.update") }}',
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{csrf_token()}}",
                },
                beforeSend:function (){
                    // form.find('.ajax-loading-wrap').addClass('show').removeClass('hide');
                },
                processData: false,
                contentType: false,
                data:form,
                success: function (data) {
                    // click close box button for remove model data
                    $(".close-box").click();

                    if(data.success == 'true'){
                        toastr.success(data.msg)
                    }else{
                        toastr.error(data.msg);
                    }
                },
                error: function (data) {
                    var errors = data.responseJSON.errors;
                    var markup = '<ul class="alert alert-danger">';
                    $.each(errors,function (index,value){
                        markup += '<li>'+value+'</li>';
                    })
                    markup += '</ul>';
                    form_element.html(markup);
                }
            });
        });

        $(document).on("click",".close-box , .bulk-update",function (){
            $(".inventory_content_wrapper").html("")
            $(".inventory_price_content_wrapper").html("")
        });

        $(document).on("submit","#search_price_inventory_price",function (e){
            e.preventDefault();
            let from_date = $('input[name=inventory_price_search_from_date]').val()
            let to_date = $('input[name=inventory_price_search_to_date]').val()
            let room_type_id = $('input[name=inventory_price_room_type]').val()

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.price.search") }}',
                type: 'POST',
                data: {
                    from_date: from_date,
                    to_date: to_date,
                    room_type_id: room_type_id,
                    _token: '{{ csrf_token() }}'
                }, success: function (data) {
                    $(".inventory_price_content_wrapper").html(data);
                },
                error: function (data) {
                    var errors = data.responseJSON.error;
                    toastr.error(errors);
                }
            });
        });

        $(document).on("submit","#search_inventory",function (e){
            e.preventDefault();
            let from_date = $('input[name=inventory_search_from_date]').val()
            let to_date = $('input[name=inventory_search_to_date]').val()
            let room_type_id = $('input[name=room_type]').val()
            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.search") }}',
                type: 'POST',
                data: {
                    from_date: from_date,
                    to_date: to_date,
                    room_type_id: room_type_id,
                    _token: '{{ csrf_token() }}'
                }, success: function (data) {
                    $(".inventory_content_wrapper").html(data);
                },
                error: function (data) {
                    var errors = data.responseJSON.error;
                    toastr.error(errors);
                }
            });
        });

        $(document).on("submit","#search_inventory_price",function (e){
            e.preventDefault();
            let from_date = $('input[name=inventory_price_search_from_date]').val()
            let to_date = $('input[name=inventory_price_search_to_date]').val()
            let room_type_id = $('input[name=inventory_price_room_type]').val()

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.search") }}',
                type: 'POST',
                data: {
                    from_date: from_date,
                    to_date: to_date,
                    room_type_id: room_type_id,
                    _token: '{{ csrf_token() }}'
                }, success: function (data) {
                    $(".inventory-table-wrapper").html(data);
                },
                error: function (data) {
                    var errors = data.responseJSON.error;
                    toastr.error(errors);
                }
            });
        });

        $(document).on("submit","#search_room_inventory",function (e){
            e.preventDefault();
            let from_date = $('input[name=from_search_inventory_date]').val()
            let to_date = $('input[name=to_search_inventory_date]').val()

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.inventory_search") }}',
                type: 'POST',
                data: {
                    from_date: from_date,
                    to_date: to_date,
                    _token: '{{ csrf_token() }}'
                }, success: function (data) {
                    $(".inventory-table-wrapper").html(data);
                },
                error: function (data) {
                    console.log(data,'error')
                    var errors = "Please select correct date range";
                    toastr.error(errors);
                }
            });
        });

        flatpickr('input[name=inventory_search_from_date]', {
            "min": new Date().fp_incr(1),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=inventory_search_to_date]', {
            "min": new Date().fp_incr(2),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=from_search_inventory_date]', {
            "min": new Date().fp_incr(1),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=to_search_inventory_date]', {
            "min": new Date().fp_incr(2),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=inventory_price_search_from_date]', {
            "min": new Date().fp_incr(1),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=inventory_price_search_to_date]', {
            "min": new Date().fp_incr(2),
            "max": new Date().fp_incr(7)
        });

        $(document).on("click",".update-inventory", function (){
            let tr = $(this).parent().parent();

            let name = tr.attr("data-room-type-name");
            let id = tr.attr("id");
            console.log(id)
            let room_type_id = tr.data("room-type-address");

            let data = [];

            $("#" + id + " .date2").each(function (){
                data.push({
                    date: $(this).data("date"),
                    value: $(this).val()
                })
            });

            $.ajax({
                url: '{{ route("tenant.admin.room-book-inventories.updated") }}',
                type: 'POST',
                data: {
                    name: name,
                    id: room_type_id,
                    selected_data: data,
                    _token: '{{ csrf_token() }}'
                }, success: function (data) {
                    $('.inventoryTable').load(location.href + ' .inventoryTable');
                    toastr.success(data.msg);
                }
            });
        });
    </script>
@endsection
