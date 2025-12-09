@php
@endphp
<section class="booking-area pat-100 pab-50">
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title"> {{$data['top_title']}} </h2>
            <div class="section-title-shapes"> </div>
        </div>
        <div class="row gy-4 mt-5">
            @foreach($data['repeater_data']['title_'.get_user_lang()] ?? [] as $key => $title)
                @php
                    $repeater_image = $data['repeater_data']['icon_'.get_user_lang()][$key] ?? '';
                    $repeater_description = $data['repeater_data']['description_'.get_user_lang()][$key] ?? '';
                @endphp
            <div class="col-xl-4 col-lg-4 col-md-6 wow fadeInRight" data-wow-delay=".2s">
                <div class="single-why center-text single-why-border radius-10">
                    <div class="single-why-icon">
                        {!! render_image_markup_by_attachment_id($repeater_image) !!}
                    </div>
                    <div class="single-why-contents mt-3">
                        <h4 class="single-why-contents-title"> <a href="javascript:void(0)"> {{$title}} </a> </h4>
                        <p class="single-why-contents-para mt-3"> {{$repeater_description}} </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
