@php
    $default_lang = $request->lang ?? \App\Facades\GlobalLanguage::default_slug();
@endphp
<style>
    .btn-no-body {
        text-decoration: none;
        outline: none;
        color: inherit;
    }

    .btn-no-body:hover {
        /* Add hover styles if needed */
    }
    .single-attraction-two-thumb.thumb-height-420 {
        height: 420px;
        width: auto;
        border-radius: 10px;
        overflow: hidden;
    }
    .single-attraction-two-thumb.thumb-height-420 img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }
    .single-attraction-two-contents-title button {
        padding: 0;
        border: 0;
        box-shadow: none;
        font-size: unset;
        font-family: "Plus Jakarta Sans", sans-serif;
        font-weight: 700;
    }

    .single-attraction-two-contents-title:hover button {
        color: unset;
    }
</style>
    <!-- Booking Two area end -->
<section class="attraction-area pat-50 pab-50">
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title">{{$data['top_title']}} </h2>
            <div class="section-title-line"> </div>
        </div>
        <div class="row mt-5">
            <div class="col-12">
                <div class="global-slick-init attraction-slider nav-style-one nav-color-two slider-inner-margin" data-rtl="{{get_user_lang_direction_bool()}}" data-infinite="true" data-arrows="true" data-dots="false" data-slidesToShow="4" data-swipeToSlide="true" data-autoplay="true" data-autoplaySpeed="2500" data-prevArrow='<div class="prev-icon radius-parcent-50"><i class="las la-angle-left"></i></div>'
                     data-nextArrow='<div class="next-icon radius-parcent-50"><i class="las la-angle-right"></i></div>' data-responsive='[{"breakpoint": 1400,"settings": {"slidesToShow": 4}},{"breakpoint": 1200,"settings": {"slidesToShow": 3}},{"breakpoint": 992,"settings": {"slidesToShow": 2}},{"breakpoint": 768,"settings": {"slidesToShow": 2}},{"breakpoint": 576, "settings": {"slidesToShow": 1} }]'>
                    @foreach($data['all_rooms'] ?? [] as $item)
                    <div class="attraction-item">
                        <form action="{{route('tenant.frontend.room_details',$item->slug)}}" method="post">
                            @csrf

                        <div class="single-attraction-two radius-20">
                            <div class="single-attraction-two-thumb thumb-height-420">
                                <a href="{{route('tenant.frontend.room_details',$item->slug)}}" class="gallery-popup hotel-view-thumb hotel-view-grid-thumb bg-image" {!! render_background_image_markup_by_attachment_id((count($item->room_image)>0)?$item->room_image[0]->image_id:null) !!}> </a>
                            </div>
                            <input type="hidden" name="title" value="{{$item->getTranslation('name',$default_lang)}}">
                            <input type="hidden" name="image_id" value="{{(count($item->room_image)>0)?$item->room_image[0]->image_id:null}}">
                            <input type="hidden" name="description" value="{{$item->description}}">
                            <div class="single-attraction-two-contents">
                                <h4 class="single-attraction-two-contents-title">
                                    <button class="btn btn-no-body" type="submit">{{$item->getTranslation('name',$default_lang)}}</button>
                                </h4>
                                <p class="single-attraction-two-contents-para">   {{ \Illuminate\Support\Str::limit($item->description, 70, $end='.......') }}</p>
                            </div>
                        </div>
                        </form>
                    </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</section>
