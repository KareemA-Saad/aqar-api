@extends('tenant.admin.admin-master')
@section('title') {{__('All Booking Reports')}} @endsection

@section('style')
    <x-media-upload.css/>
    <x-datatable.css/>
@endsection

@section('content')
    @php
        $colors = ["warning","danger","info","success","dark","secondary"];
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
                        <h4 class="header-title">{{__('Booking Reports')}}</h4>
                        <div class="col-md-12" id="errors"></div>

                        <form method="post" id="report-export">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="start_date">{{__('Start Date')}}</label>
                                    <input class="form-control flat_date" placeholder="Starting Date" name="start_date" >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="start_date">{{__('End Date')}}</label>
                                    <input class="form-control flat_date" placeholder="Ending Date" name="end_date">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="start_date" style="opacity: 0">button </label>
                                    <div>
                                        <button type="submit" class="btn btn-info">{{__('Export')}}</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="hotel-booked-table">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @yield("custom-ajax-scripts")
    <script>
        $(document).ready(function($){
            "use strict";

            $(document).on('change','select[name="lang"]',function (e){
                $(this).closest('form').trigger('submit');
                $('input[name="lang"]').val($(this).val());
            });
        });
    </script>
    <script>
        flatpickr('input[name=start_date]', {
            "min": new Date().fp_incr(1),
            "max": new Date().fp_incr(7)
        });

        flatpickr('input[name=end_date]', {
            "min": new Date().fp_incr(2),
            "max": new Date().fp_incr(7)
        });

        $(document).on("submit","#report-export",function(e){
            e.preventDefault();
            let erContainer = $("#errors");

            $.ajax({
                url: "{{ route('tenant.admin.report.search') }}",
                type: "POST",
                data: {
                    _token : "{{csrf_token()}}",
                    start_date: $("input[name=start_date]").val(),
                    end_date: $("input[name=end_date]").val(),
                },
                error:function(data){
                    var errors = data.responseJSON;
                    erContainer.html('<div class="alert alert-danger"></div>');
                    $.each(errors.errors, function(index,value){
                        erContainer.find('.alert.alert-danger').append('<p>'+value+'</p>');
                    });
                },
                success:function (data){
                    if(data.warning_msg){
                        Swal.fire({
                            position: 'end-start',
                            icon: 'warning',
                            title: '{{ __("No Booking available on this date range") }}',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        CustomSweetAlertTwo.warning(data.warning_msg)

                    }
                    $('.alert.alert-danger').remove();
                    $(".hotel-booked-table").html(data);
                    exportTableToCSV('payment-logs-report.csv');
                }
            });
        });


        function downloadCSV(csv, filename) {
            var csvFile;
            var downloadLink;

            // CSV file
            csvFile = new Blob([csv], {type: "text/csv"});

            // Download link
            downloadLink = document.createElement("a");

            // File name
            downloadLink.download = filename;

            // Create a link to the file
            downloadLink.href = window.URL.createObjectURL(csvFile);

            // Hide download link
            downloadLink.style.display = "none";

            // Add the link to DOM
            document.body.appendChild(downloadLink);

            // Click download link
            downloadLink.click();
        }

        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#all_user_table tr");

            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");

                for (var j = 0; j < cols.length; j++)
                    row.push(cols[j].innerText);

                csv.push(row.join(","));
            }

            // Download CSV file
            downloadCSV(csv.join("\n"), filename);
        }

    </script>
    <x-datatable.js/>
    <x-table.btn.swal.js/>

@endsection
